<?php

use App\Models\Faq;
use App\Support\Seo;
use function Livewire\Volt\{state, mount, title};

state(['faqs' => []]);

mount(function () {
    $this->faqs = Faq::where('is_active', true)->orderBy('sort_order')->get();

    Seo::share([
        'seoTitle'       => 'FAQ',
        'seoDescription' => 'Answers to common questions about SpeakLoud schedules, claims, accounts, and safety.',
        'seoUrl'         => route('faq.index'),
    ]);
});

title(fn () => Seo::pageTitle('FAQ'));

?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-[#3D2B1F] mb-2">Frequently asked questions</h1>
    <p class="text-sm text-[#3D2B1F]/55 mb-8">
        Quick answers about getting started. For tips and updates, see the
        <a href="{{ route('blog.index') }}" class="text-[#FF8C42] font-semibold hover:underline">blog</a>.
    </p>

    @if ($faqs->isEmpty())
        <p class="text-[#3D2B1F]/50 text-sm">No FAQs published yet.</p>
    @else
        <div class="space-y-2">
            @foreach ($faqs as $faq)
                <details class="group bg-[#FFF0E0] rounded-lg">
                    <summary class="p-4 cursor-pointer font-medium text-[#3D2B1F] list-none flex justify-between items-center gap-4">
                        {{ $faq->question }}
                        <span class="text-[#FF8C42] group-open:rotate-45 transition-transform shrink-0">+</span>
                    </summary>
                    <div class="px-4 pb-4 text-sm text-[#3D2B1F]/70">{{ $faq->answer }}</div>
                </details>
            @endforeach
        </div>
    @endif
</div>
