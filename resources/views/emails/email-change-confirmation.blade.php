<x-mail::message>
# Conferma la nuova email

Ciao {{ $user->name }}, hai chiesto di usare **{{ $newEmail }}** per accedere e per ricevere le designazioni.

<x-mail::button :url="$url">
Conferma la nuova email
</x-mail::button>

Il link vale {{ $hours }} ore. Finché non lo apri, resta valido l'indirizzo attuale.

Se non hai chiesto tu questa modifica, ignora questa email.

Grazie!
</x-mail::message>
