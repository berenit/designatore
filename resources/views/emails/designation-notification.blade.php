<x-mail::message>
# Sei stato designato come arbitro

Ciao **{{ $designation->referee->name }}**,

sei stato designato per il seguente incontro di rugby:

<x-mail::table>
| | |
|:--|:--|
| **Incontro** | {{ $designation->match->label }} |
| **Ruolo** | {{ $designation->role }} |
| **Data e ora** | {{ \Carbon\Carbon::parse($designation->match->date_time)->format('d/m/Y \a\l\l\e H:i') }} |
| **Campo** | {{ $designation->match->venue_label }} |
| **Competizione** | {{ $designation->match->competition_type }} |
@if($designation->notes)
| **Note** | {{ $designation->notes }} |
@endif
</x-mail::table>

Conferma la tua disponibilità entro 48 ore:

<x-mail::button :url="$acceptUrl" color="success">
✅ Accetta la designazione
</x-mail::button>

<x-mail::button :url="$declineUrl" color="error">
❌ Rifiuta la designazione
</x-mail::button>

Se hai domande, contatta il designatore.

Grazie,<br>
{{ config('app.name') }}
</x-mail::message>
