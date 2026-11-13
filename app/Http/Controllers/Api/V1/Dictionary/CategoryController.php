<?php

namespace App\Http\Controllers\Api\V1\Dictionary;

use App\Domain\Dictionary\Services\DictionaryCache;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Dictionary\CategoryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function __construct(
        private readonly DictionaryCache $cache,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection($this->cache->activeCategories());
    }
}
