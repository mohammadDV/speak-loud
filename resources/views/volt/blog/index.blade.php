<?php

use App\Support\Seo;
use function Livewire\Volt\{state, mount, title};
use App\Repositories\Contracts\IBlogPostRepository;

state([
    'postsPage'    => 1,
    'loadedPosts'  => [],
    'hasMorePosts' => false,
    'scrollLoadEnabled' => false,
]);

$loadPosts = function (bool $append = false) {
    $paginator = app(IBlogPostRepository::class)->published($this->postsPage);

    if ($append) {
        $this->loadedPosts = [...$this->loadedPosts, ...$paginator->items()];
    } else {
        $this->loadedPosts = $paginator->items();
    }

    $this->hasMorePosts = $paginator->hasMorePages();
};

$loadMorePosts = function () {
    if (! $this->hasMorePosts) {
        return;
    }

    $this->postsPage++;
    $this->loadPosts(append: true);
};

$enableScrollLoad = function () {
    $this->scrollLoadEnabled = true;
};

$loadMorePostsFromScroll = function () {
    if (! $this->scrollLoadEnabled) {
        return;
    }

    $this->loadMorePosts();
};

mount(function () {
    Seo::share([
        'seoTitle'       => 'Blog',
        'seoDescription' => 'Tips for language practice, community stories, and product updates from SpeakLoud.',
        'seoUrl'         => route('blog.index'),
    ]);

    $this->loadPosts();
});

title(fn () => Seo::pageTitle('Blog'));

?>

<div class="max-w-6xl mx-auto px-4 py-8" @scroll.window.once="$wire.enableScrollLoad()">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-[#3D2B1F]">Blog</h1>
            <p class="text-sm text-[#3D2B1F]/55 mt-1">Tips, community stories, and product news.</p>
        </div>
        <a href="{{ route('faq.index') }}" class="text-sm font-semibold text-[#FF8C42] hover:underline shrink-0">
            View FAQ →
        </a>
    </div>

    @if ($loadedPosts === [])
        <p class="text-[#3D2B1F]/50 text-sm">No posts published yet.</p>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($loadedPosts as $post)
                <a href="{{ route('blog.show', $post->slug) }}" wire:key="blog-post-{{ $post->id }}" class="block overflow-hidden rounded-xl">
                    <flux:card class="bg-[#FFF0E0] hover:shadow-md transition-shadow p-0 overflow-hidden h-full">
                        <x-blog-cover :post="$post" class="rounded-none" />
                        <div class="p-5">
                            @if ($post->category)
                                <span class="text-[10px] font-semibold uppercase tracking-wide text-[#FF8C42]">{{ $post->category->name }}</span>
                            @endif
                            <h2 class="font-semibold text-[#3D2B1F] mt-1">{{ $post->title }}</h2>
                            @if ($post->excerpt)
                                <p class="text-sm text-[#3D2B1F]/60 mt-1 line-clamp-2">{{ Str::limit(strip_tags($post->excerpt), 100) }}</p>
                            @endif
                            <p class="text-xs text-[#3D2B1F]/40 mt-3">
                                {{ $post->author->profile->display_name ?? 'SpeakLoud' }}
                                · {{ $post->published_at?->format('M j, Y') }}
                            </p>
                        </div>
                    </flux:card>
                </a>
            @endforeach
        </div>

        @if ($hasMorePosts)
            <div
                wire:intersect="loadMorePostsFromScroll"
                wire:intersect:margin.400px
                wire:key="blog-scroll-sentinel"
                class="h-px"
                aria-hidden="true"
            ></div>

            <div class="mt-8 flex justify-center">
                <flux:button
                    type="button"
                    wire:click="loadMorePosts"
                    wire:loading.attr="disabled"
                    wire:target="loadMorePosts"
                    variant="ghost"
                >
                    <span wire:loading.remove wire:target="loadMorePosts">Load more</span>
                    <span wire:loading wire:target="loadMorePosts">Loading…</span>
                </flux:button>
            </div>
        @endif
    @endif
</div>
