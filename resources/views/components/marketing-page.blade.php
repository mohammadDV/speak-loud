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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#FFF8F0] text-[#3D2B1F] antialiased min-h-screen flex flex-col">
    <x-app-navbar />

    <main class="flex-1 max-w-3xl mx-auto px-5 py-12 w-full">
        <h1 class="text-3xl font-black text-[#3D2B1F] tracking-tight mb-6">{{ $heading ?? $title }}</h1>
        <div class="prose prose-stone max-w-none text-[#3D2B1F]/80 prose-p:leading-relaxed">
            {{ $slot }}
        </div>
    </main>

    <footer class="border-t border-black/[0.06] py-6 mt-12">
        <div class="max-w-6xl mx-auto px-5 flex items-center justify-between text-[12px] text-[#3D2B1F]/30">
            <span>© {{ date('Y') }} SpeakLoud</span>
            <div class="flex gap-5">
                <a href="{{ route('blog.index') }}" class="hover:text-[#3D2B1F] transition-colors">Blog</a>
                <a href="{{ route('faq.index') }}" class="hover:text-[#3D2B1F] transition-colors">FAQ</a>
                <a href="{{ route('home') }}" class="hover:text-[#3D2B1F] transition-colors">Home</a>
            </div>
        </div>
    </footer>
</body>
</html>
