<x-mail::message>
# I dettagli della partita sono cambiati

Ciao **{{ $designation->referee->name }}**,

i dettagli del seguente incontro per cui sei stato designato sono stati modificati:

<x-mail::table>
| | |
|:--|:--|
| **Incontro** | {{ $designation->match->label }} |
| **Ruolo** | {{ $designation->role }} |
| **Data e ora** | {{ \Carbon\Carbon::parse($designation->match->date_time)->format('d/m/Y \a\l\l\e H:i') }} |
| **Campo** | {{ $designation->match->venue_label }} |
| **Competizione** | {{ $designation->match->competition_type }} |
@if($designation->match->category_label)
| **Categoria** | {{ $designation->match->category_label }} |
@endif
@if($designation->notes)
| **Note** | {{ $designation->notes }} |
@endif
</x-mail::table>

Ti chiediamo di confermare nuovamente la tua disponibilità con i nuovi dettagli:

<x-mail::button :url="$acceptUrl" color="success">
✅ Conferma la designazione
</x-mail::button>

<x-mail::button :url="$declineUrl" color="error">
❌ Rifiuta la designazione
</x-mail::button>

Se hai domande, contatta il designatore.

Grazie!
</x-mail::message>
