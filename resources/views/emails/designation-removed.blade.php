<x-mail::message>
# Designazione rimossa

Ciao **{{ $designation->referee->name }}**,

la tua designazione per il seguente incontro è stata rimossa e non è più valida:

<x-mail::table>
| | |
|:--|:--|
| **Incontro** | {{ $designation->match->label }} |
| **Ruolo** | {{ $designation->role }} |
| **Data e ora** | {{ \Carbon\Carbon::parse($designation->match->date_time)->format('d/m/Y \a\l\l\e H:i') }} |
| **Campo** | {{ $designation->match->venue_label }} |
</x-mail::table>

Non è richiesta alcuna azione da parte tua. Se hai domande, contatta il designatore.

Grazie!
</x-mail::message>
