<?php

namespace App\Repositories;

use App\Models\BlogPost;
use App\Repositories\Contracts\IBlogPostRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BlogPostRepository implements IBlogPostRepository
{
    public function findBySlug(string $slug): ?BlogPost
    {
        return BlogPost::where('slug', $slug)->first();
    }

    public function published(int $page = 1): LengthAwarePaginator
    {
        return BlogPost::query()
            ->with(['author.profile', 'category'])
            ->where('published_at', '<=', now())
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->paginate(6, ['*'], 'page', $page);
    }

    public function recentPublished(int $limit = 3): Collection
    {
        return BlogPost::query()
            ->with(['author.profile', 'category'])
            ->where('published_at', '<=', now())
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    public function create(array $data): BlogPost
    {
        return BlogPost::create($data);
    }

    public function update(int $id, array $data): BlogPost
    {
        $post = BlogPost::findOrFail($id);
        $post->update($data);
        return $post->fresh();
    }
}
