<x-app-layout>
    <div class="max-w-3xl mx-auto space-y-6">
        <h1 class="text-2xl font-bold text-gray-900">Scegli una nuova password</h1>

        <p class="text-sm text-gray-600">
            Il numero di tessera vale solo per il primo accesso: per continuare devi sostituirlo con una password personale.
            La password attuale è il tuo numero di tessera.
        </p>

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>
    </div>
</x-app-layout>
