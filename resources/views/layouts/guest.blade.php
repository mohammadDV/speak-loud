<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="color-scheme: light only;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-google-tag-manager />
    <x-seo-head :title="$title ?? null" />
    @stack('seo-extra')
    <x-viewer-timezone />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#FFF8F0] text-[#3D2B1F] antialiased">
    <flux:main>
        {{ $slot }}
    </flux:main>
    @livewireScripts
</body>
</html>
