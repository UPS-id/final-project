<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
    @livewireStyles
</head>
<body class="min-h-screen bg-white dark:bg-zinc-950 antialiased selection:bg-purple-500 selection:text-white">
    {{ $slot }}
    @livewireScripts
</body>
</html>
