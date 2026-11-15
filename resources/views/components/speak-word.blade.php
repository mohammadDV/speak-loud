@props([
    'text',
    'lang',
    'label' => 'Hear pronunciation',
])

@php
    $speakText = preg_replace('/\s+/u', ' ', trim((string) $text)) ?? '';
    $speakLang = trim((string) $lang);
@endphp

@if ($speakText !== '' && $speakLang !== '')
    <button
        type="button"
        data-speak
        data-speak-lang="{{ $speakLang }}"
        data-speak-text="{{ $speakText }}"
        aria-label="{{ $label }}"
        title="{{ $label }}"
        x-data="{ text: {{ \Illuminate\Support\Js::from($speakText) }}, lang: {{ \Illuminate\Support\Js::from($speakLang) }} }"
        @click.stop="window.SpeakLoudSpeak({ text, lang })"
        {{ $attributes->class('inline-flex shrink-0 items-center justify-center rounded-lg p-1.5 text-[#FF8C42] hover:bg-[#FF8C42]/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#FF8C42]/40') }}
    >
        <flux:icon icon="speaker-wave" variant="micro" class="size-4" />
    </button>
@endif
