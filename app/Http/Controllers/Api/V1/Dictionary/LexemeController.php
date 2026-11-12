<?php

namespace App\Http\Controllers\Api\V1\Dictionary;

use App\Domain\Dictionary\Services\DictionarySearchService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Dictionary\DictionaryTargetLanguageRequest;
use App\Http\Resources\Api\V1\Dictionary\LexemeResource;
use App\Http\Resources\Api\V1\Dictionary\SenseResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LexemeController extends Controller
{
    public function __construct(
        private readonly DictionarySearchService $search,
    ) {}

    public function show(DictionaryTargetLanguageRequest $request, int $lexeme): LexemeResource
    {
        $record = $this->search->findPublishedLexeme(
            $lexeme,
            $request->targetLanguage(),
        );

        abort_if($record === null, 404);

        return new LexemeResource($record);
    }

    public function senses(DictionaryTargetLanguageRequest $request, int $lexeme): AnonymousResourceCollection
    {
        $record = $this->search->findPublishedLexeme(
            $lexeme,
            $request->targetLanguage(),
        );

        abort_if($record === null, 404);

        $senses = $this->search->publishedSensesForLexeme(
            $record,
            $request->targetLanguage(),
        );

        return SenseResource::collection($senses);
    }
}
