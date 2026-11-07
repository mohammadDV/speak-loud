@props([
    'title',
    'description',
    'heading' => null,
])

@php
    use App\Support\Seo;

    Seo::share([
        'seoTitle'       => $title,
        'seoDescription' => $description,
        'seoUrl'         => url()->current(),
    ]);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo-head :title="$title" :description="$description" />
    @stack('seo-extra')
    <x-viewer-timezone />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#FFF8F0] text-[#3D2B1F] antialiased min-h-screen flex flex-col pb-20 md:pb-0">
    <x-app-navbar />

    <main class="flex-1 max-w-3xl mx-auto px-5 py-12 w-full">
        <h1 class="text-3xl font-black text-[#3D2B1F] tracking-tight mb-6">{{ $heading ?? $title }}</h1>
        <div class="prose prose-stone max-w-none text-[#3D2B1F]/80 prose-p:leading-relaxed prose-headings:text-[#3D2B1F] prose-h2:text-xl prose-h2:font-bold prose-h2:mt-10 prose-h2:mb-3">
            {{ $slot }}
        </div>
    </main>

    <x-site-footer class="mt-12" />
</body>
</html>
