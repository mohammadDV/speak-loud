<?php

namespace App\Repositories;

use App\Models\BlogPost;
use App\Repositories\Contracts\IBlogPostRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BlogPostRepository implements IBlogPostRepository
{
    public function findBySlug(string $slug): ?BlogPost
    {
        return BlogPost::where('slug', $slug)->first();
    }

    public function published(int $page = 1): LengthAwarePaginator
    {
        return BlogPost::query()
            ->with(['author.profile'])
            ->where('published_at', '<=', now())
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->paginate(20, ['*'], 'page', $page);
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
