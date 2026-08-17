<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <style>
        /* DomPDF ignora @page: i margini si ottengono con padding sul wrapper. */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.6;
            color: #1f2937;
        }

        /* Simula i margini A4: 20mm lat., 18mm top/bottom (1mm ≈ 3.78px a 96dpi) */
        .wrap {
            padding-top:    68px;   /* ≈ 18mm */
            padding-bottom: 68px;
            padding-left:   76px;   /* ≈ 20mm */
            padding-right:  76px;
        }

        /* ── Intestazione ─────────────────────────────────────────────── */
        .header {
            width: 100%;
            border-bottom: 2.5px solid #4f46e5;
            padding-bottom: 14px;
            margin-bottom: 22px;
        }
        .header-inner {
            display: table;
            width: 100%;
        }
        .header-left  { display: table-cell; vertical-align: bottom; }
        .header-right { display: table-cell; vertical-align: bottom; text-align: right; }

        .header h1 {
            font-size: 20px;
            color: #4f46e5;
            font-weight: bold;
        }
        .header .subtitle {
            font-size: 9.5px;
            color: #6b7280;
            margin-top: 3px;
        }
        .header .meta {
            font-size: 9px;
            color: #9ca3af;
            line-height: 1.7;
        }

        /* ── Tabella ──────────────────────────────────────────────────── */
        table { width: 100%; border-collapse: collapse; }

        thead tr { background-color: #f3f4f6; }

        th {
            padding: 9px 12px;
            text-align: left;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6b7280;
            font-weight: bold;
            border-top: 1px solid #d1d5db;
            border-bottom: 1px solid #d1d5db;
        }

        td {
            padding: 10px 12px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }

        tbody tr:last-child td { border-bottom: none; }

        .col-date   { width: 12%; }
        .col-match  { width: 38%; }
        .col-ref    { width: 23%; }
        .col-role   { width: 14%; }
        .col-status { width: 13%; }

        .role-name { font-size: 10px; color: #374151; }

        .date-day  { font-size: 11px; font-weight: bold; color: #374151; white-space: nowrap; }
        .date-time { font-size: 9.5px; color: #9ca3af; margin-top: 1px; }

        .match-name { font-size: 11px; font-weight: bold; color: #111827; }
        .match-meta { font-size: 9px; color: #6b7280; margin-top: 3px; }

        .ref-name  { font-size: 11px; color: #374151; }
        .ref-level { font-size: 9px; color: #9ca3af; margin-top: 2px; }

        .badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 9999px;
            font-size: 8.5px;
            font-weight: bold;
        }
        .badge-pending   { background-color: #fef3c7; color: #92400e; }
        .badge-confirmed { background-color: #d1fae5; color: #065f46; }
        .badge-completed { background-color: #dbeafe; color: #1e40af; }
        .badge-cancelled { background-color: #fee2e2; color: #991b1b; }

        /* ── Riepilogo ────────────────────────────────────────────────── */
        .summary {
            margin-top: 24px;
            padding: 14px 18px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            page-break-inside: avoid;
        }
        .summary-title {
            font-size: 9px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 12px;
        }
        .summary-grid { display: table; width: 100%; }
        .summary-cell {
            display: table-cell;
            text-align: center;
            padding: 4px 8px;
            border-right: 1px solid #e5e7eb;
        }
        .summary-cell:last-child { border-right: none; }
        .summary-num {
            font-size: 22px;
            font-weight: bold;
            color: #4f46e5;
            line-height: 1;
        }
        .summary-label { font-size: 8.5px; color: #6b7280; margin-top: 4px; }

        /* ── Piè di pagina (non fixed — in fondo al contenuto) ─────────── */
        .footer {
            margin-top: 28px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            font-size: 8.5px;
            color: #9ca3af;
            display: table;
            width: 100%;
        }
        .footer-left  { display: table-cell; }
        .footer-right { display: table-cell; text-align: right; }
    </style>
</head>
<body>
<div class="wrap">

    <div class="header">
        <div class="header-inner">
            <div class="header-left">
                <h1>Designazioni Arbitrali</h1>
                <p class="subtitle">
                    {{ $designations->count() }} {{ $designations->count() === 1 ? 'designazione' : 'designazioni' }}
                </p>
            </div>
            <div class="header-right">
                <p class="meta">
                    {{ config('app.name') }}<br>
                    Generato il {{ $generatedAt }}
                </p>
            </div>
        </div>
    </div>

    @if ($designations->isEmpty())
        <p style="color:#6b7280; font-style:italic;">Nessuna designazione trovata.</p>
    @else

        <table>
            <thead>
                <tr>
                    <th class="col-date">Data</th>
                    <th class="col-match">Incontro</th>
                    <th class="col-ref">Arbitro</th>
                    <th class="col-role">Ruolo</th>
                    <th class="col-status">Stato</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($designations as $d)
                    <tr>
                        <td class="col-date">
                            <div class="date-day">{{ \Carbon\Carbon::parse($d->match->date_time)->format('d/m/Y') }}</div>
                            <div class="date-time">{{ \Carbon\Carbon::parse($d->match->date_time)->format('H:i') }}</div>
                        </td>
                        <td class="col-match">
                            <div class="match-name">{{ $d->match->label }}</div>
                            <div class="match-meta">{{ $d->match->venue_label }} &nbsp;·&nbsp; {{ $d->match->competition_type }}@if($d->match->category_label)
                                    &nbsp;·&nbsp; {{ $d->match->category_label }}
                                @endif</div>
                        </td>
                        <td class="col-ref">
                            <div class="ref-name">{{ $d->referee->name }}</div>
                        </td>
                        <td class="col-role">
                            <div class="role-name">{{ $d->role }}</div>
                        </td>
                        <td class="col-status">
                            <span class="badge badge-{{ $d->status }}">{{ ucfirst($d->status) }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <div class="summary-title">Riepilogo</div>
            <div class="summary-grid">
                @foreach (['pending' => 'In attesa', 'confirmed' => 'Confermate', 'completed' => 'Completate', 'cancelled' => 'Annullate'] as $key => $label)
                    @php $count = $designations->where('status', $key)->count(); @endphp
                    <div class="summary-cell">
                        <div class="summary-num">{{ $count }}</div>
                        <div class="summary-label">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
        </div>

    @endif

    <div class="footer">
        <span class="footer-left">{{ config('app.name') }} &nbsp;·&nbsp; Designazioni Arbitrali</span>
        <span class="footer-right">{{ $generatedAt }}</span>
    </div>

</div><!-- /.wrap -->
</body>
</html>
