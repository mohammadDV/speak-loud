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
<body class="bg-[#FFF8F0] text-[#3D2B1F] antialiased min-h-screen flex flex-col">
    <x-app-navbar />

    <main @class([
        'flex-1',
        'pb-20 md:pb-0' => request()->routeIs('schedules.show'),
    ])>
        @if (session('oauth_status'))
            <div class="mx-auto max-w-3xl px-4 pt-4">
                <div class="p-3 bg-green-50 text-green-700 rounded-lg text-sm ring-1 ring-green-200">
                    {{ session('oauth_status') }}
                </div>
            </div>
        @endif

        {{ $slot }}
    </main>

    @unless (request()->routeIs('messages', 'messages.*', 'schedules.show'))
        <x-site-footer class="pb-20 md:pb-0" />
    @endunless

    @livewireScripts
</body>
</html>
