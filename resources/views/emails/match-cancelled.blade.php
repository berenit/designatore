<x-mail::message>
# Partita annullata

Ciao **{{ $designation->referee->name }}**,

il seguente incontro per cui eri stato designato è stato annullato:

<x-mail::table>
| | |
|:--|:--|
| **Incontro** | {{ $match->label }} |
| **Ruolo** | {{ $designation->role }} |
| **Data e ora** | {{ \Carbon\Carbon::parse($match->date_time)->format('d/m/Y \a\l\l\e H:i') }} |
| **Campo** | {{ $match->venue_label }} |
</x-mail::table>

La tua designazione non è quindi più valida. Non è richiesta alcuna azione da parte tua. Se hai domande, contatta il designatore.

Grazie!
</x-mail::message>
