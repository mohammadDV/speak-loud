<?php

namespace App\Http\Controllers\Api\V1\Dictionary;

use App\Domain\Dictionary\Services\DictionarySearchService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Dictionary\DictionarySearchRequest;
use App\Http\Resources\Api\V1\Dictionary\LexemeResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DictionarySearchController extends Controller
{
    public function __construct(
        private readonly DictionarySearchService $search,
    ) {}

    public function __invoke(DictionarySearchRequest $request): AnonymousResourceCollection
    {
        $results = $this->search->search($request->validated());

        return LexemeResource::collection($results);
    }
}
