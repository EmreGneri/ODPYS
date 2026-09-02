<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ODPYS') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    {{-- Eski sistemin app.js'inden AYRI bir Vite giris noktasi -
         bkz. vite.config.js ve resources/js/spa.js --}}
    @vite(['resources/css/app.css', 'resources/js/spa.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div id="app"></div>
</body>
</html>
