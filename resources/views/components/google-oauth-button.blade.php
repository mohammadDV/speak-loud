@props([
    'label' => 'Continue with Google',
])

<div {{ $attributes->class(['space-y-5']) }}>
    <div class="relative flex items-center gap-3">
        <div class="h-px flex-1 bg-[#3D2B1F]/15"></div>
        <span class="text-xs uppercase tracking-wide text-[#3D2B1F]/45">or</span>
        <div class="h-px flex-1 bg-[#3D2B1F]/15"></div>
    </div>

    <a
        href="{{ route('auth.google.redirect') }}"
        class="inline-flex w-full items-center justify-center gap-3 rounded-lg border border-[#3D2B1F]/20 bg-white px-4 py-2.5 text-sm font-medium text-[#3D2B1F] shadow-sm transition hover:bg-[#FFF8F0] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#FF8C42] focus-visible:ring-offset-2"
    >
        <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="#EA4335" d="M12 10.2v3.6h5.1c-.2 1.2-.9 2.3-1.9 3l3.1 2.4c1.8-1.7 2.9-4.1 2.9-7 0-.7-.1-1.3-.2-1.9H12z"/>
            <path fill="#34A853" d="M6.6 14.3 5.7 15l-2.5 1.9C4.8 20.1 8.1 22 12 22c2.7 0 4.9-.9 6.6-2.4l-3.1-2.4c-.9.6-2 .9-3.5.9-2.7 0-5-1.8-5.8-4.3z"/>
            <path fill="#4A90E2" d="M3.2 7.1C2.4 8.6 2 10.2 2 12s.4 3.4 1.2 4.9l3.4-2.6C6.2 13.5 6 12.8 6 12s.2-1.5.6-2.2L3.2 7.1z"/>
            <path fill="#FBBC05" d="M12 5.9c1.5 0 2.8.5 3.8 1.5l2.8-2.8C16.9 2.9 14.7 2 12 2 8.1 2 4.8 3.9 3.2 7.1l3.4 2.6C7 7.7 9.3 5.9 12 5.9z"/>
        </svg>
        <span>{{ $label }}</span>
    </a>
</div>
