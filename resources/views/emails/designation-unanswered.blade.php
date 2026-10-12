<x-mail::message>
# Designazione senza risposta

@php
    $reminders = match (true) {
        $designation->reminders_sent === 1 => ', nonostante il sollecito inviato',
        $designation->reminders_sent > 1 => ", nonostante i {$designation->reminders_sent} solleciti inviati",
        default => '',
    };
@endphp
L'arbitro **{{ $designation->referee->name }}** non ha ancora accettato né rifiutato la designazione per il seguente incontro{{ $reminders }}:

<x-mail::table>
| | |
|:--|:--|
| **Incontro** | {{ $designation->match->label }} |
| **Ruolo** | {{ $designation->role }} |
| **Data e ora** | {{ \Carbon\Carbon::parse($designation->match->date_time)->format('d/m/Y \a\l\l\e H:i') }} |
| **Campo** | {{ $designation->match->venue_label }} |
| **Competizione** | {{ $designation->match->competition_type }} |
| **Richiesta inviata il** | {{ $designation->notified_at->format('d/m/Y \a\l\l\e H:i') }} |
</x-mail::table>

Contatta l'arbitro o assegna un altro arbitro appena possibile.

<x-mail::button :url="route('designations.index', ['week' => \Carbon\Carbon::parse($designation->match->date_time)->toDateString()])">
Vai alle designazioni
</x-mail::button>

Grazie!
</x-mail::message>
