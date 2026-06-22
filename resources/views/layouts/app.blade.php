<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Designatore') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100 min-h-screen">

    <nav class="bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-8">
                    <a href="{{ url('/') }}" class="flex items-center gap-2 text-indigo-700 font-bold text-lg tracking-tight">
                        <span class="text-2xl">🏉</span>
                        <span>Designatore</span>
                    </a>
                    @auth
                    <div class="hidden md:flex items-center gap-1">
                        @php
                            $navLinks = [
                                ['url' => '/dashboard',    'label' => 'Dashboard',    'match' => 'dashboard'],
                                ['url' => '/rugby-matches','label' => 'Partite',      'match' => 'rugby-matches*'],
                                ['url' => '/designations', 'label' => 'Designazioni', 'match' => 'designations*'],
                                ['url' => '/reports',      'label' => 'Report',       'match' => 'reports*'],
                            ];
                        @endphp
                        @foreach ($navLinks as $link)
                            @php $active = request()->is(ltrim($link['match'], '/')); @endphp
                            <a href="{{ url($link['url']) }}"
                               class="px-3 py-2 rounded-md text-sm font-medium transition
                                      {{ $active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </div>
                    @endauth
                </div>

                <div class="flex items-center gap-3">
                    @auth
                        @php $configActive = request()->is('referees*') || request()->is('teams*'); @endphp
                        <div x-data="{ open: false }" class="relative hidden md:block">
                            <button @click="open = !open" @keydown.escape.window="open = false"
                                    class="inline-flex items-center gap-1 px-3 py-2 rounded-md text-sm font-medium transition
                                           {{ $configActive ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Configurazioni
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                            <div x-show="open" x-transition @click.outside="open = false" x-cloak
                                 class="absolute right-0 mt-2 w-48 rounded-lg bg-white shadow-lg border border-gray-100 py-1 z-50">
                                @php
                                    $configLinks = [
                                        ['url' => '/referees', 'label' => 'Arbitri', 'match' => 'referees*'],
                                        ['url' => '/teams',    'label' => 'Squadre', 'match' => 'teams*'],
                                    ];
                                @endphp
                                @foreach ($configLinks as $link)
                                    @php $active = request()->is(ltrim($link['match'], '/')); @endphp
                                    <a href="{{ url($link['url']) }}"
                                       class="block px-4 py-2 text-sm transition
                                              {{ $active ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
                                        {{ $link['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        @php $accountActive = request()->is('profile*'); @endphp
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" @keydown.escape.window="open = false"
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium transition
                                           {{ $accountActive ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                                <span>{{ auth()->user()->name }}</span>
                                @if(auth()->user()->role)
                                    <span class="hidden md:inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                        {{ ucfirst(auth()->user()->role) }}
                                    </span>
                                @endif
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                            <div x-show="open" x-transition @click.outside="open = false" x-cloak
                                 class="absolute right-0 mt-2 w-48 rounded-lg bg-white shadow-lg border border-gray-100 py-1 z-50">
                                <a href="{{ route('profile.edit') }}"
                                   class="block px-4 py-2 text-sm transition
                                          {{ $accountActive ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
                                    Profilo e password
                                </a>
                                <div class="my-1 border-t border-gray-100"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition">
                                        Esci
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}"
                           class="text-sm font-medium text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md hover:bg-gray-50 transition">
                            Accedi
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if (session('success'))
            <div class="mb-6 flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg" role="alert">
                <svg class="w-5 h-5 flex-shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg" role="alert">
                <svg class="w-5 h-5 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
        @endif

        @yield('content')
        {{ $slot ?? '' }}
    </main>

</body>
</html>
