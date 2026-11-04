<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Informazioni profilo
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Aggiorna le informazioni del tuo account e l'indirizzo email.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        @if ($user->isReferee())
            <div>
                <p class="text-sm font-medium text-gray-700">Nome</p>
                <p class="mt-1 text-sm text-gray-900">{{ $user->name }}</p>
                @if ($referee->license_number)
                    <p class="mt-1 text-xs text-gray-500">Tessera {{ $referee->license_number }} · {{ $referee->license_level }}</p>
                @endif
                <p class="mt-1 text-xs text-gray-500">Per correggere questi dati rivolgiti al designatore.</p>
            </div>
        @else
            <div>
                <x-input-label for="name" value="Nome" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>
        @endif

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($referee)
                <p class="mt-1 text-xs text-gray-500">È il tuo nome utente per accedere e l'indirizzo a cui ricevi le designazioni.</p>
            @endif

            @if ($user->pending_email)
                <p class="mt-2 text-sm text-amber-700">
                    In attesa di conferma: {{ $user->pending_email }}. Apri il link che ti abbiamo inviato a quell'indirizzo.
                </p>
            @endif

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        Il tuo indirizzo email non è verificato.

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Clicca qui per inviare di nuovo l'email di verifica.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            Un nuovo link di verifica è stato inviato al tuo indirizzo email.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        @if ($referee)
            <div>
                <x-input-label for="phone" value="Telefono" />
                <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $referee->phone)" maxlength="20" autocomplete="tel" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>

            <div>
                <x-input-label for="city" value="Città di domicilio" />
                <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" :value="old('city', $referee->city)" maxlength="100" />
                <x-input-error class="mt-2" :messages="$errors->get('city')" />
            </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>Salva</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >Salvato.</p>
            @endif
        </div>
    </form>
</section>
