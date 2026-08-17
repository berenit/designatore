<x-mail::message>
# Designazione rifiutata

L'arbitro **{{ $designation->referee->name }}** ha rifiutato la designazione per il seguente incontro:

<x-mail::table>
| | |
|:--|:--|
| **Incontro** | {{ $designation->match->label }} |
| **Ruolo** | {{ $designation->role }} |
| **Data e ora** | {{ \Carbon\Carbon::parse($designation->match->date_time)->format('d/m/Y \a\l\l\e H:i') }} |
| **Campo** | {{ $designation->match->venue_label }} |
| **Competizione** | {{ $designation->match->competition_type }} |
</x-mail::table>

Assegna un nuovo arbitro appena possibile.

<x-mail::button :url="route('designations.index')">
Vai alle designazioni
</x-mail::button>

Grazie!
</x-mail::message>
