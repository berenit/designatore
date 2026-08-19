<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ReportController extends Controller
{
    private function getDesignations(Request $request)
    {
        return Designation::with(['match.homeTeam', 'match.awayTeam', 'match.teams', 'match.venue', 'referee'])
            ->join('matches', 'matches.id', '=', 'designations.match_id')
            ->when($request->status, fn ($q) => $q->where('designations.status', $request->status))
            ->when($request->date_from, fn ($q) => $q->whereDate('matches.date_time', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('matches.date_time', '<=', $request->date_to))
            ->orderBy('matches.date_time', 'asc')
            ->select('designations.*')
            ->get();
    }

    public function index(Request $request)
    {
        // Default: prossimo sabato → domenica successiva
        $nextSaturday = now()->startOfDay()->next(Carbon::SATURDAY);
        $defaultFrom = $request->date_from ?? $nextSaturday->format('Y-m-d');
        $defaultTo = $request->date_to ?? $nextSaturday->copy()->addDay()->format('Y-m-d');

        $request->mergeIfMissing(['date_from' => $defaultFrom, 'date_to' => $defaultTo]);

        $designations = $this->getDesignations($request);

        return view('reports.index', compact('designations', 'defaultFrom', 'defaultTo'));
    }

    public function pdf(Request $request)
    {
        $designations = $this->getDesignations($request);
        $generatedAt = now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('reports.pdf', compact('designations', 'generatedAt'))
            ->setPaper('a4', 'portrait')
            ->setOption(['dpi' => 150, 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        $filename = 'designazioni_'.now()->format('Ymd_Hi').'.pdf';

        return $pdf->download($filename);
    }

    public function markdown(Request $request)
    {
        $designations = $this->getDesignations($request);
        $content = $this->buildMarkdown($designations);

        return response($content, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="designazioni_'.now()->format('Ymd_Hi').'.md"',
        ]);
    }

    public function text(Request $request)
    {
        $designations = $this->getDesignations($request);
        $content = $this->buildText($designations, $request->date_from, $request->date_to);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="designazioni_'.now()->format('Ymd_Hi').'.txt"',
        ]);
    }

    /** Invia le designazioni filtrate al gruppo Telegram configurato in .env. */
    public function telegram(Request $request)
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (! $botToken || ! $chatId) {
            return response()->json([
                'message' => 'Bot Telegram non configurato: imposta TELEGRAM_BOT_TOKEN e TELEGRAM_CHAT_ID nel file .env.',
            ], 422);
        }

        $designations = $this->getDesignations($request);
        $text = $this->buildText($designations, $request->date_from, $request->date_to);

        $endpoint = "https://api.telegram.org/bot{$botToken}/sendMessage";

        $response = Http::asForm()->post($endpoint, [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);

        // Nomi di squadre/campi con caratteri come _ o * possono rompere il parser
        // Markdown di Telegram: se succede, ritenta in testo semplice pur di recapitare il messaggio.
        if (! $response->successful()) {
            $response = Http::asForm()->post($endpoint, [
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        }

        if (! $response->successful()) {
            report(new \RuntimeException('Invio Telegram fallito: '.$response->body()));

            return response()->json([
                'message' => 'Invio a Telegram fallito. Verifica che il token e il chat_id siano corretti e che il bot sia nel gruppo.',
            ], 502);
        }

        return response()->json([
            'message' => 'Designazioni inviate al gruppo Telegram.',
        ]);
    }

    private function buildMarkdown($designations): string
    {
        $lines = [];
        $lines[] = '# Designazioni Arbitrali';
        $lines[] = '';
        $lines[] = '_Generato il '.now()->format('d/m/Y \a\l\l\e H:i').'_';
        $lines[] = '';

        if ($designations->isEmpty()) {
            $lines[] = '_Nessuna designazione trovata._';

            return implode("\n", $lines);
        }

        $lines[] = '| Data | Incontro | Campo | Arbitro | Ruolo | Stato |';
        $lines[] = '|------|----------|-------|---------|-------|-------|';

        foreach ($designations as $d) {
            $matchDate = Carbon::parse($d->match->date_time);
            $date = $matchDate->format('d/m/Y H:i');
            if (! $matchDate->isSunday()) {
                $date .= ' **('.ucfirst($matchDate->translatedFormat('l')).')**';
            }
            $match = $d->match->label;
            $venue = $d->match->venue_label;
            $ref = $d->referee->name;
            $role = $d->role;
            $status = ucfirst($d->status);
            $lines[] = "| {$date} | {$match} | {$venue} | {$ref} | {$role} | {$status} |";
        }

        $lines[] = '';
        $lines[] = '---';
        $lines[] = '';
        $lines[] = '## Riepilogo';
        $lines[] = '';

        foreach (['pending' => 'In attesa', 'confirmed' => 'Confermate', 'completed' => 'Completate', 'cancelled' => 'Annullate'] as $key => $label) {
            $count = $designations->where('status', $key)->count();
            if ($count > 0) {
                $lines[] = "- **{$label}**: {$count}";
            }
        }

        return implode("\n", $lines);
    }

    private function buildText($designations, ?string $dateFrom = null, ?string $dateTo = null): string
    {
        $statusEmoji = [
            'pending' => '⏳',
            'confirmed' => '✅',
            'completed' => '🏁',
            'cancelled' => '❌',
        ];

        $lines = [];
        $lines[] = '🏉 *DESIGNAZIONI ARBITRALI*';
        $lines[] = '📅 '.$this->formatDateRange($designations, $dateFrom, $dateTo);
        $lines[] = str_repeat('─', 30);

        if ($designations->isEmpty()) {
            $lines[] = 'Nessuna designazione trovata.';

            return implode("\n", $lines);
        }

        foreach ($designations as $d) {
            $emoji = $statusEmoji[$d->status] ?? '•';
            $matchDate = Carbon::parse($d->match->date_time);
            $date = $matchDate->format('d/m/Y H:i');
            if (! $matchDate->isSunday()) {
                $date .= ' ⚠️ *('.ucfirst($matchDate->translatedFormat('l')).')*';
            }
            $lines[] = '';
            $lines[] = "{$emoji} *{$d->match->label}*";
            $lines[] = "   🗓 {$date}";
            $lines[] = "   📍 {$d->match->venue_label}";
            $lines[] = "   👤 {$d->referee->name} — {$d->role}";
        }

        $lines[] = '';
        $lines[] = str_repeat('─', 30);

        $totals = [];
        foreach (['confirmed' => '✅ Confermate', 'pending' => '⏳ In attesa', 'completed' => '🏁 Completate', 'cancelled' => '❌ Annullate'] as $key => $label) {
            $count = $designations->where('status', $key)->count();
            if ($count > 0) {
                $totals[] = "{$label}: {$count}";
            }
        }
        if ($totals) {
            $lines[] = implode('  |  ', $totals);
        }

        return implode("\n", $lines);
    }

    /**
     * Intervallo di date da mostrare in intestazione: usa i filtri data_from/date_to se
     * presenti, altrimenti lo ricava dalle date delle partite effettivamente incluse.
     */
    private function formatDateRange($designations, ?string $dateFrom, ?string $dateTo): string
    {
        if ($dateFrom && $dateTo) {
            return $dateFrom === $dateTo
                ? Carbon::parse($dateFrom)->format('d/m/Y')
                : Carbon::parse($dateFrom)->format('d/m/Y').' – '.Carbon::parse($dateTo)->format('d/m/Y');
        }

        if ($dateFrom) {
            return 'Dal '.Carbon::parse($dateFrom)->format('d/m/Y');
        }

        if ($dateTo) {
            return 'Fino al '.Carbon::parse($dateTo)->format('d/m/Y');
        }

        if ($designations->isEmpty()) {
            return 'Nessuna data selezionata';
        }

        $dates = $designations->map(fn ($d) => Carbon::parse($d->match->date_time));
        $min = $dates->min();
        $max = $dates->max();

        return $min->isSameDay($max)
            ? $min->format('d/m/Y')
            : $min->format('d/m/Y').' – '.$max->format('d/m/Y');
    }
}
