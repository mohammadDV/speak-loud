@php
    $links = [
        ['about', 'About'],
        ['blog.index', 'Blog'],
        ['faq.index', 'FAQ'],
        ['contact', 'Contact'],
        ['donate', 'Donate'],
        ['support', 'Support'],
        ['terms', 'Terms'],
    ];
@endphp

<footer {{ $attributes->class('border-t border-black/[0.06] py-8') }}>
    <div class="max-w-6xl mx-auto px-5 flex flex-col items-center gap-5 sm:flex-row sm:justify-between text-[12px] text-[#3D2B1F]/30">
        <nav class="flex w-full flex-wrap justify-center gap-x-5 gap-y-2.5 sm:w-auto sm:justify-end sm:order-2" aria-label="Footer">
            @foreach ($links as [$route, $label])
                <a href="{{ route($route) }}" class="hover:text-[#3D2B1F] transition-colors">{{ $label }}</a>
            @endforeach
        </nav>
        <p class="text-center sm:text-left sm:order-1">
            © {{ date('Y') }} SpeakLoudMate · Built by
            <a href="https://mohammaddv.com" class="hover:text-[#3D2B1F] transition-colors" rel="noopener noreferrer" target="_blank">mohammaddv</a>
        </p>
    </div>
</footer>
