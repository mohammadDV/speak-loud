<?php

namespace App\Domain\Learning\Services;

use App\Domain\Dictionary\Support\TextNormalizer;

/**
 * Grades free-text meaning answers with normalization + fuzzy similarity.
 *
 * Procedure:
 * 1. Normalize the learner answer and each accepted gloss (language-aware via TextNormalizer).
 * 2. Exact normalized match → correct (score 100).
 * 3. Also compare against parenthetical-stripped / delimited variants of accepted glosses.
 * 4. For longer glosses only: accept when similar_text >= 85 AND edit distance is within allowance,
 *    or when similar_text >= 92 (strong match).
 * 5. Gloss forms of length <= 4 require an exact normalized match (no fuzzy).
 */
class MeaningAnswerChecker
{
    public const MIN_SIMILARITY_PERCENT = 85;

    public const STRONG_SIMILARITY_PERCENT = 92;

    public const MIN_FUZZY_LENGTH = 5;

    /**
     * @param  list<string>  $acceptedMeanings
     * @return array{is_correct: bool, match_score: int, matched_meaning: string|null}
     */
    public function check(string $userAnswer, array $acceptedMeanings, string $languageCode): array
    {
        $normalizedAnswer = TextNormalizer::normalize($userAnswer, $languageCode);

        if ($normalizedAnswer === '') {
            return [
                'is_correct' => false,
                'match_score' => 0,
                'matched_meaning' => null,
            ];
        }

        $bestScore = 0;
        $matchedMeaning = null;
        $isCorrect = false;

        foreach ($acceptedMeanings as $meaning) {
            $meaning = trim((string) $meaning);
            if ($meaning === '') {
                continue;
            }

            foreach ($this->candidateForms($meaning) as $candidate) {
                $normalizedCandidate = TextNormalizer::normalize($candidate, $languageCode);
                if ($normalizedCandidate === '') {
                    continue;
                }

                if ($normalizedAnswer === $normalizedCandidate) {
                    return [
                        'is_correct' => true,
                        'match_score' => 100,
                        'matched_meaning' => $meaning,
                    ];
                }

                $maxLen = max(mb_strlen($normalizedAnswer), mb_strlen($normalizedCandidate));
                if ($maxLen < self::MIN_FUZZY_LENGTH) {
                    continue;
                }

                $score = $this->similarityPercent($normalizedAnswer, $normalizedCandidate);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $matchedMeaning = $meaning;
                }

                $withinLevenshtein = $this->withinLevenshteinAllowance($normalizedAnswer, $normalizedCandidate);
                $fuzzyOk = ($score >= self::MIN_SIMILARITY_PERCENT && $withinLevenshtein)
                    || $score >= self::STRONG_SIMILARITY_PERCENT;

                if ($fuzzyOk) {
                    $isCorrect = true;
                    $matchedMeaning = $meaning;
                    $bestScore = max($bestScore, $score);
                }
            }
        }

        return [
            'is_correct' => $isCorrect,
            'match_score' => min(100, $bestScore),
            'matched_meaning' => $matchedMeaning,
        ];
    }

    /**
     * @return list<string>
     */
    private function candidateForms(string $meaning): array
    {
        $forms = [$meaning];

        $withoutParens = trim(preg_replace('/\([^)]*\)/u', '', $meaning) ?? $meaning);
        $withoutParens = trim(preg_replace('/\s+/u', ' ', $withoutParens) ?? $withoutParens);
        if ($withoutParens !== '' && $withoutParens !== $meaning) {
            $forms[] = $withoutParens;
        }

        foreach (preg_split('/\s*[;,\/|]\s*/u', $meaning) ?: [] as $part) {
            $part = trim($part);
            if ($part !== '' && ! in_array($part, $forms, true)) {
                $forms[] = $part;
            }
        }

        return $forms;
    }

    private function similarityPercent(string $a, string $b): int
    {
        similar_text($a, $b, $percent);

        return (int) round($percent);
    }

    private function withinLevenshteinAllowance(string $a, string $b): bool
    {
        $maxLen = max(mb_strlen($a), mb_strlen($b));
        if ($maxLen === 0) {
            return false;
        }

        // levenshtein() works on bytes; cap inputs to keep it safe for short glosses.
        if (strlen($a) > 255 || strlen($b) > 255) {
            return false;
        }

        $distance = levenshtein($a, $b);

        $allowed = match (true) {
            $maxLen <= 8 => 1,
            $maxLen <= 14 => 2,
            default => 2,
        };

        return $distance <= $allowed && ($distance / $maxLen) <= 0.25;
    }
}
