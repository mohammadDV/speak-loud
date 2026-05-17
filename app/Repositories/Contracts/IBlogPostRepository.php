<?php

namespace App\Repositories\Contracts;

use App\Models\BlogPost;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IBlogPostRepository
{
    public function findBySlug(string $slug): ?BlogPost;

    public function published(int $page = 1): LengthAwarePaginator;

    public function create(array $data): BlogPost;

    public function update(int $id, array $data): BlogPost;
}
