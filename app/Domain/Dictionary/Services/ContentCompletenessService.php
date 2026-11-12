<?php

namespace App\Domain\Dictionary\Services;

use App\Domain\Dictionary\Enums\GermanArticle;
use App\Domain\Dictionary\Models\Lexeme;
use App\Domain\Dictionary\Models\Sense;
use App\Domain\Dictionary\Support\EnumValue;

class ContentCompletenessService
{
    /**
     * @return array{score: int, max: int, checks: array<string, bool>}
     */
    public function forLexeme(Lexeme $lexeme): array
    {
        $lexeme->loadMissing([
            'language',
            'senses.partOfSpeech',
            'senses.translations',
            'senses.examples',
            'senses.levels',
            'senses.germanNounDetail',
        ]);

        if ($lexeme->senses->isEmpty()) {
            return [
                'score' => 0,
                'max' => 1,
                'checks' => ['has_senses' => false],
            ];
        }

        $aggregated = [];
        $score = 0;
        $max = 0;

        foreach ($lexeme->senses as $sense) {
            $result = $this->forSense($sense, $lexeme->language?->code);
            foreach ($result['checks'] as $key => $passed) {
                $checkKey = "sense_{$sense->id}_{$key}";
                $aggregated[$checkKey] = $passed;
                $max++;
                if ($passed) {
                    $score++;
                }
            }
        }

        return [
            'score' => $score,
            'max' => $max,
            'checks' => $aggregated,
        ];
    }

    /**
     * @return array{score: int, max: int, checks: array<string, bool>}
     */
    public function forSense(Sense $sense, ?string $languageCode = null): array
    {
        $sense->loadMissing([
            'partOfSpeech',
            'translations',
            'examples',
            'levels',
            'germanNounDetail',
            'lexeme.language',
        ]);

        $languageCode ??= $sense->lexeme?->language?->code;
        $isGermanNoun = $languageCode === 'de' && $sense->partOfSpeech?->code === 'noun';

        $checks = [
            'definition' => filled($sense->definition),
            'part_of_speech' => $sense->part_of_speech_id !== null,
            'translation' => $sense->translations->isNotEmpty(),
            'example' => $sense->examples->isNotEmpty(),
            'level' => $sense->levels->isNotEmpty(),
        ];

        if ($isGermanNoun) {
            $detail = $sense->germanNounDetail;
            $checks['german_article'] = $detail !== null && GermanArticle::isValid(EnumValue::string($detail->article));
            $checks['german_plural'] = $detail !== null && filled($detail->plural_form);
        }

        $score = count(array_filter($checks));

        return [
            'score' => $score,
            'max' => count($checks),
            'checks' => $checks,
        ];
    }
}
