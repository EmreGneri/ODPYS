<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            {{-- Faz 6: iki sistem paralel calisiyor. Bu bant, kullanicinin
                 hangi arayuzde oldugunu net gostersin ve yeni arayuzle
                 karsilastirabilsin diye. Eski sistem tamamen kaldirildiginda
                 bu bant da silinecek. --}}
            <div class="bg-amber-50 border-b border-amber-200 text-amber-900 text-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex items-center justify-between gap-4">
                    <span>Klasik arayüzdesiniz. Yeni arayüz (SPA) yayında.</span>
                    <a href="/app" class="underline whitespace-nowrap hover:text-amber-950">
                        Yeni arayüze geç &rarr;
                    </a>
                </div>
            </div>

            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
