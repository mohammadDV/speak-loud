<?php

use App\Support\Seo;
use function Livewire\Volt\{state, mount, title};
use App\Repositories\Contracts\IBlogPostRepository;

state(['post' => null]);

mount(function (string $slug) {
    $this->post = app(IBlogPostRepository::class)->findBySlug($slug);
    if (!$this->post || $this->post->status !== 'published') {
        abort(404);
    }

    Seo::forPost($this->post);
});

title(fn () => $this->post ? Seo::pageTitle($this->post->title) : Seo::pageTitle('Blog'));

?>

<div class="max-w-2xl mx-auto px-4 py-8">
    @if ($post)
        <a href="{{ route('blog.index') }}" class="text-sm text-[#FF8C42] mb-6 inline-block">← All posts</a>

        <x-blog-cover :post="$post" class="rounded-xl mb-6" />

        <h1 class="text-3xl font-bold text-[#3D2B1F] mt-2 mb-3">{{ $post->title }}</h1>
        <p class="text-sm text-[#3D2B1F]/50 mb-8">
            {{ $post->author->profile->display_name ?? '' }} · {{ $post->published_at?->format('M j, Y') }}
            @if ($post->category) · {{ $post->category->name }} @endif
        </p>

        <article class="prose prose-stone max-w-none text-[#3D2B1F] prose-p:text-[#3D2B1F]/80 prose-headings:text-[#3D2B1F]">
            {!! $post->body !!}
        </article>

        @if ($post->tags->isNotEmpty())
            <div class="flex flex-wrap gap-2 mt-8 pt-6 border-t border-[#3D2B1F]/10">
                @foreach ($post->tags as $tag)
                    <span class="text-xs bg-[#FFF0E0] text-[#3D2B1F] px-3 py-1 rounded-full">#{{ $tag->slug }}</span>
                @endforeach
            </div>
        @endif
    @endif
</div>
