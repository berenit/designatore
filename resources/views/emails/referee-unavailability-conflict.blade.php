<x-mail::message>
# Indisponibilità su gare già designate

L'arbitro **{{ $referee->name }}** si è dichiarato indisponibile dal {{ $period->start_date->format('d/m/Y') }} al {{ $period->end_date->format('d/m/Y') }}, ma risulta designato per:

<x-mail::table>
| Incontro | Ruolo | Data e ora |
|:--|:--|:--|
@foreach ($designations as $designation)
| {{ $designation->match->label }} | {{ $designation->role }} | {{ \Carbon\Carbon::parse($designation->match->date_time)->format('d/m/Y H:i') }} |
@endforeach
</x-mail::table>

Le designazioni non sono state modificate: valuta se assegnare un altro arbitro.

<x-mail::button :url="route('designations.index')">
Vai alle designazioni
</x-mail::button>

Grazie!
</x-mail::message>
