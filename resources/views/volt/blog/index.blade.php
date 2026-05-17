<?php

use function Livewire\Volt\{state, mount, computed, usesPagination};
use App\Repositories\Contracts\IBlogPostRepository;
use App\Models\Faq;

usesPagination();

state(['faqs' => []]);

mount(function () {
    $this->faqs = Faq::where('is_active', true)->orderBy('sort_order')->get();
});

$posts = computed(function () {
    return app(IBlogPostRepository::class)->published($this->getPage());
});

?>

<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <section>
            <h2 class="text-xl font-bold text-[#3D2B1F] mb-6">Recent posts</h2>
            @if ($this->posts->isEmpty())
                <p class="text-[#3D2B1F]/50 text-sm">No posts published yet.</p>
            @else
                <div class="space-y-4">
                    @foreach ($this->posts as $post)
                        <a href="{{ route('blog.show', $post->slug) }}" class="block">
                            <flux:card class="bg-[#FFF0E0] hover:shadow-md transition-shadow p-5">
                                <h3 class="font-semibold text-[#3D2B1F]">{{ $post->title }}</h3>
                                @if ($post->excerpt)
                                    <p class="text-sm text-[#3D2B1F]/60 mt-1">{{ Str::limit($post->excerpt, 80) }}</p>
                                @endif
                                <p class="text-xs text-[#3D2B1F]/40 mt-2">{{ $post->author->profile->display_name ?? '' }} · {{ $post->published_at?->format('M j') }}</p>
                            </flux:card>
                        </a>
                    @endforeach
                </div>
                <div class="mt-6">{{ $this->posts->links() }}</div>
            @endif
        </section>

        <section>
            <h2 class="text-xl font-bold text-[#3D2B1F] mb-6">FAQ</h2>
            <div class="space-y-2">
                @foreach ($faqs as $faq)
                    <details class="group bg-[#FFF0E0] rounded-lg">
                        <summary class="p-4 cursor-pointer font-medium text-[#3D2B1F] list-none flex justify-between items-center">
                            {{ $faq->question }}
                            <span class="text-[#FF8C42] group-open:rotate-45 transition-transform">+</span>
                        </summary>
                        <div class="px-4 pb-4 text-sm text-[#3D2B1F]/70">{{ $faq->answer }}</div>
                    </details>
                @endforeach
            </div>
        </section>
    </div>
</div>
