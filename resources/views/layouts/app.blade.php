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
                                ['url' => '/dashboard',    'label' => 'Dashboard', 'match' => 'dashboard'],
                                ['url' => '/referees',     'label' => 'Arbitri',   'match' => 'referees*'],
                                ['url' => '/teams',        'label' => 'Squadre',   'match' => 'teams*'],
                                ['url' => '/rugby-matches','label' => 'Partite',   'match' => 'rugby-matches*'],
                                ['url' => '/designations', 'label' => 'Designazioni', 'match' => 'designations*'],
                                ['url' => '/reports',      'label' => 'Report',        'match' => 'reports*'],
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
                        <span class="hidden md:block text-sm text-gray-500">
                            {{ auth()->user()->name }}
                            @if(auth()->user()->role)
                                <span class="inline-flex items-center ml-1 px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                    {{ ucfirst(auth()->user()->role) }}
                                </span>
                            @endif
                        </span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md hover:bg-gray-50 transition">
                                Esci
                            </button>
                        </form>
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
