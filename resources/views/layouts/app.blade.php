<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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
<body class="bg-[#FFF8F0] text-[#3D2B1F] antialiased min-h-screen flex flex-col">
    <x-app-navbar />

    <main @class([
        'flex-1',
        'pb-20 md:pb-0' => request()->routeIs('schedules.show'),
    ])>
        {{ $slot }}
    </main>

    @unless (request()->routeIs('messages', 'messages.*', 'schedules.show'))
        <x-site-footer class="pb-20 md:pb-0" />
    @endunless

    @livewireScripts
</body>
</html>
