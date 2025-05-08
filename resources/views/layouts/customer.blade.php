<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Customer Portal</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Use CDN for Tailwind instead of Vite -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine JS (if needed) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Remove Vite references -->
    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}

    <!-- Livewire Styles -->
    @livewireStyles
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        <!-- Navigation (if any) -->

        <!-- Main Content -->
        <main>
            {{ $slot }}
        </main>

        <!-- Footer (if any) -->
    </div>

    <!-- Livewire Scripts -->
    @livewireScripts
</body>
</html>
