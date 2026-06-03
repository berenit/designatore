<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private function getDesignations(Request $request)
    {
        return Designation::with(['match.homeTeam', 'match.awayTeam', 'referee'])
            ->join('matches', 'matches.id', '=', 'designations.match_id')
            ->when($request->status,    fn($q) => $q->where('designations.status', $request->status))
            ->when($request->date_from, fn($q) => $q->whereDate('matches.date_time', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->whereDate('matches.date_time', '<=', $request->date_to))
            ->orderBy('matches.date_time', 'asc')
            ->select('designations.*')
            ->get();
    }

    public function index(Request $request)
    {
        // Default: prossimo sabato → domenica successiva
        $nextSaturday = now()->startOfDay()->next(\Carbon\Carbon::SATURDAY);
        $defaultFrom  = $request->date_from ?? $nextSaturday->format('Y-m-d');
        $defaultTo    = $request->date_to   ?? $nextSaturday->copy()->addDay()->format('Y-m-d');

        $request->mergeIfMissing(['date_from' => $defaultFrom, 'date_to' => $defaultTo]);

        $designations = $this->getDesignations($request);
        return view('reports.index', compact('designations', 'defaultFrom', 'defaultTo'));
    }

    public function pdf(Request $request)
    {
        $designations = $this->getDesignations($request);
        $generatedAt  = now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('reports.pdf', compact('designations', 'generatedAt'))
            ->setPaper('a4', 'portrait')
            ->setOption(['dpi' => 150, 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        $filename = 'designazioni_' . now()->format('Ymd_Hi') . '.pdf';

        return $pdf->download($filename);
    }

    public function markdown(Request $request)
    {
        $designations = $this->getDesignations($request);
        $content = $this->buildMarkdown($designations);

        return response($content, 200, [
            'Content-Type'        => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="designazioni_' . now()->format('Ymd_Hi') . '.md"',
        ]);
    }

    public function text(Request $request)
    {
        $designations = $this->getDesignations($request);
        $content = $this->buildText($designations);

        return response($content, 200, [
            'Content-Type'        => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="designazioni_' . now()->format('Ymd_Hi') . '.txt"',
        ]);
    }

    private function buildMarkdown($designations): string
    {
        $lines = [];
        $lines[] = '# Designazioni Arbitrali';
        $lines[] = '';
        $lines[] = '_Generato il ' . now()->format('d/m/Y alle H:i') . '_';
        $lines[] = '';

        if ($designations->isEmpty()) {
            $lines[] = '_Nessuna designazione trovata._';
            return implode("\n", $lines);
        }

        $lines[] = '| Data | Incontro | Campo | Arbitro | Stato |';
        $lines[] = '|------|----------|-------|---------|-------|';

        foreach ($designations as $d) {
            $date   = Carbon::parse($d->match->date_time)->format('d/m/Y H:i');
            $match  = "{$d->match->homeTeam->name} vs {$d->match->awayTeam->name}";
            $venue  = $d->match->venue;
            $ref    = $d->referee->name;
            $status = ucfirst($d->status);
            $lines[] = "| {$date} | {$match} | {$venue} | {$ref} | {$status} |";
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

    private function buildText($designations): string
    {
        $statusEmoji = [
            'pending'   => '⏳',
            'confirmed' => '✅',
            'completed' => '🏁',
            'cancelled' => '❌',
        ];

        $lines = [];
        $lines[] = '🏉 *DESIGNAZIONI ARBITRALI*';
        $lines[] = '📅 ' . now()->format('d/m/Y H:i');
        $lines[] = str_repeat('─', 30);

        if ($designations->isEmpty()) {
            $lines[] = 'Nessuna designazione trovata.';
            return implode("\n", $lines);
        }

        foreach ($designations as $d) {
            $emoji  = $statusEmoji[$d->status] ?? '•';
            $date   = Carbon::parse($d->match->date_time)->format('d/m/Y H:i');
            $lines[] = '';
            $lines[] = "{$emoji} *{$d->match->homeTeam->name} vs {$d->match->awayTeam->name}*";
            $lines[] = "   🗓 {$date}";
            $lines[] = "   📍 {$d->match->venue}";
            $lines[] = "   👤 {$d->referee->name} ({$d->referee->license_level})";
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
}
