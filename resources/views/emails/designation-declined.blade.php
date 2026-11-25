<x-mail::message>
# Designazione rifiutata

L'arbitro **{{ $designation->referee->name }}** ha rifiutato la designazione per il seguente incontro:

<x-mail::table>
| | |
|:--|:--|
| **Incontro** | {{ \App\Support\MailTable::cell($designation->match->label) }} |
| **Ruolo** | {{ $designation->role }} |
| **Data e ora** | {{ \Carbon\Carbon::parse($designation->match->date_time)->format('d/m/Y \a\l\l\e H:i') }} |
| **Campo** | {{ \App\Support\MailTable::cell($designation->match->venue_label) }} |
| **Competizione** | {{ $designation->match->competition_type }} |
@if($designation->match->category_label)
| **Categoria** | {{ \App\Support\MailTable::cell($designation->match->category_label) }} |
@endif
@if($designation->notes)
| **Note** | {{ \App\Support\MailTable::cell($designation->notes) }} |
@endif
</x-mail::table>

Assegna un nuovo arbitro appena possibile.

<x-mail::button :url="route('designations.index')">
Vai alle designazioni
</x-mail::button>

Grazie!
</x-mail::message>
