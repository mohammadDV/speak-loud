<?php

namespace App\Http\Controllers\Api\V1\Dictionary;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Models\Sense;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Dictionary\DictionaryTargetLanguageRequest;
use App\Http\Resources\Api\V1\Dictionary\SenseResource;
use App\Models\Language;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SenseController extends Controller
{
    public function show(DictionaryTargetLanguageRequest $request, int $sense): SenseResource
    {
        $targetLanguageId = null;
        $targetLanguage = $request->targetLanguage();

        if ($targetLanguage !== null) {
            $targetLanguageId = Language::query()->where('code', $targetLanguage)->value('id');
        }

        $record = Sense::query()
            ->published()
            ->whereHas('lexeme', fn ($q) => $q->published())
            ->with([
                'partOfSpeech',
                'germanNounDetail',
                'primaryLevel.difficultyLevel',
                'translations' => function (HasMany $query) use ($targetLanguageId): void {
                    $query->where('status', ContentStatus::Published)->orderBy('sort_order');
                    if ($targetLanguageId !== null) {
                        $query->where('target_language_id', $targetLanguageId);
                    }
                },
                'examples' => fn (HasMany $query) => $query->where('status', ContentStatus::Published)->orderBy('sort_order'),
                'examples.translations' => function (HasMany $query) use ($targetLanguageId): void {
                    $query->where('status', ContentStatus::Published);
                    if ($targetLanguageId !== null) {
                        $query->where('language_id', $targetLanguageId);
                    }
                },
                'collocations' => fn (HasMany $query) => $query->where('status', ContentStatus::Published)->orderBy('sort_order'),
                'collocations.translations' => function (HasMany $query) use ($targetLanguageId): void {
                    $query->where('status', ContentStatus::Published);
                    if ($targetLanguageId !== null) {
                        $query->where('language_id', $targetLanguageId);
                    }
                },
            ])
            ->find($sense);

        abort_if($record === null, 404);

        return new SenseResource($record);
    }
}
