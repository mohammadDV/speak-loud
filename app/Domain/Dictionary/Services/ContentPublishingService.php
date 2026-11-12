<?php

namespace App\Domain\Dictionary\Services;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Enums\GermanArticle;
use App\Domain\Dictionary\Enums\ReviewDecision;
use App\Domain\Dictionary\Models\ContentReview;
use App\Domain\Dictionary\Models\ContentRevision;
use App\Domain\Dictionary\Models\Lexeme;
use App\Domain\Dictionary\Models\Sense;
use App\Domain\Dictionary\Support\EnumValue;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContentPublishingService
{
    public function __construct(
        private readonly DictionaryCache $cache,
    ) {}

    public function submitForReview(Lexeme $lexeme, User $actor): Lexeme
    {
        $this->assertHasSenses($lexeme);

        return $this->transitionLexeme($lexeme, ContentStatus::PendingReview, $actor, 'Submitted for review');
    }

    public function approve(Lexeme $lexeme, User $actor, ?string $comments = null): Lexeme
    {
        $this->assertCanReview($actor);

        return DB::transaction(function () use ($lexeme, $actor, $comments): Lexeme {
            $this->recordReview($lexeme, $actor, ReviewDecision::Approved, $comments);

            return $this->transitionLexeme($lexeme, ContentStatus::Approved, $actor, 'Approved');
        });
    }

    public function reject(Lexeme $lexeme, User $actor, ?string $comments = null): Lexeme
    {
        $this->assertCanReview($actor);

        return DB::transaction(function () use ($lexeme, $actor, $comments): Lexeme {
            $this->recordReview($lexeme, $actor, ReviewDecision::Rejected, $comments);

            return $this->transitionLexeme($lexeme, ContentStatus::Rejected, $actor, 'Rejected');
        });
    }

    public function publish(Lexeme $lexeme, User $actor): Lexeme
    {
        $this->assertCanPublish($actor);
        $this->assertPublishRequirements($lexeme);

        return DB::transaction(function () use ($lexeme, $actor): Lexeme {
            $lexeme->loadMissing([
                'senses.translations',
                'senses.examples.translations',
                'senses.collocations.translations',
                'senses.germanNounDetail',
                'senses.levels',
                'senses.partOfSpeech',
                'forms',
            ]);

            foreach ($lexeme->senses as $sense) {
                $this->publishSenseTree($sense);
            }

            $lexeme = $this->transitionLexeme($lexeme, ContentStatus::Published, $actor, 'Published');
            $this->snapshot($lexeme, $actor, 'Published snapshot');
            $this->cache->forgetLexeme($lexeme);

            return $lexeme->fresh(['senses', 'forms', 'language']) ?? $lexeme;
        });
    }

    public function archive(Lexeme $lexeme, User $actor): Lexeme
    {
        $this->assertCanPublish($actor);

        return DB::transaction(function () use ($lexeme, $actor): Lexeme {
            $lexeme = $this->transitionLexeme($lexeme, ContentStatus::Archived, $actor, 'Archived');
            $this->cascadeStatus($lexeme, ContentStatus::Archived);
            $this->cache->forgetLexeme($lexeme);

            return $lexeme->fresh(['senses', 'forms', 'language']) ?? $lexeme;
        });
    }

    public function returnToDraft(Lexeme $lexeme, User $actor): Lexeme
    {
        return DB::transaction(function () use ($lexeme, $actor): Lexeme {
            $wasPublished = $lexeme->status === ContentStatus::Published;
            $lexeme = $this->transitionLexeme($lexeme, ContentStatus::Draft, $actor, 'Returned to draft');
            $this->cascadeStatus($lexeme, ContentStatus::Draft);

            if ($wasPublished) {
                $this->snapshot($lexeme, $actor, 'Unpublished to draft');
            }

            $this->cache->forgetLexeme($lexeme);

            return $lexeme->fresh(['senses', 'forms', 'language']) ?? $lexeme;
        });
    }

    /**
     * Demote a published lexeme (and children) after an editorial content change.
     */
    public function demotePublishedAfterEdit(Lexeme $lexeme, User $actor, string $summary = 'Edited published content'): Lexeme
    {
        $lexeme->loadMissing('language');

        if ($lexeme->status !== ContentStatus::Published) {
            $this->cache->forgetLexeme($lexeme);

            return $lexeme;
        }

        return DB::transaction(function () use ($lexeme, $actor, $summary): Lexeme {
            $this->snapshot($lexeme->loadMissing([
                'senses.translations',
                'senses.examples.translations',
                'senses.collocations.translations',
                'senses.germanNounDetail',
                'forms',
            ]), $actor, $summary);

            $this->cascadeStatus($lexeme, ContentStatus::Draft);
            $lexeme->update([
                'status' => ContentStatus::Draft,
                'updated_by' => $actor->id,
            ]);

            $this->cache->forgetLexeme($lexeme);

            return $lexeme->fresh(['senses', 'forms', 'language']) ?? $lexeme;
        });
    }

    private function transitionLexeme(Lexeme $lexeme, ContentStatus $next, User $actor, string $summary): Lexeme
    {
        $current = $lexeme->status;

        if (! $current->canTransitionTo($next)) {
            throw ValidationException::withMessages([
                'status' => "Cannot transition from {$current->value} to {$next->value}.",
            ]);
        }

        $lexeme->update([
            'status' => $next,
            'updated_by' => $actor->id,
        ]);

        $this->snapshot($lexeme->fresh() ?? $lexeme, $actor, $summary);

        return $lexeme->fresh() ?? $lexeme;
    }

    private function cascadeStatus(Lexeme $lexeme, ContentStatus $status): void
    {
        $lexeme->loadMissing(['senses.examples', 'senses.collocations']);

        foreach ($lexeme->senses as $sense) {
            $sense->update(['status' => $status]);
            $sense->translations()->update(['status' => $status]);
            $sense->examples()->update(['status' => $status]);
            $sense->collocations()->update(['status' => $status]);

            foreach ($sense->examples as $example) {
                $example->translations()->update(['status' => $status]);
            }

            foreach ($sense->collocations as $collocation) {
                $collocation->translations()->update(['status' => $status]);
            }
        }
    }

    private function publishSenseTree(Sense $sense): void
    {
        $sense->update(['status' => ContentStatus::Published]);

        $sense->translations()->update(['status' => ContentStatus::Published]);
        $sense->examples()->update(['status' => ContentStatus::Published]);
        $sense->collocations()->update(['status' => ContentStatus::Published]);

        foreach ($sense->examples as $example) {
            $example->translations()->update(['status' => ContentStatus::Published]);
        }

        foreach ($sense->collocations as $collocation) {
            $collocation->translations()->update(['status' => ContentStatus::Published]);
        }
    }

    private function snapshot(Lexeme $lexeme, User $actor, string $summary): void
    {
        $version = (int) ContentRevision::query()
            ->where('entity_type', $lexeme->getMorphClass())
            ->where('entity_id', $lexeme->id)
            ->max('version') + 1;

        ContentRevision::query()->create([
            'entity_type' => $lexeme->getMorphClass(),
            'entity_id' => $lexeme->id,
            'version' => $version,
            'snapshot' => $lexeme->loadMissing([
                'senses.germanNounDetail',
                'senses.translations',
                'senses.examples.translations',
                'senses.collocations.translations',
                'senses.levels.difficultyLevel',
                'forms',
            ])->toArray(),
            'created_by' => $actor->id,
            'change_summary' => $summary,
        ]);
    }

    private function recordReview(Lexeme $lexeme, User $actor, ReviewDecision $decision, ?string $comments): void
    {
        ContentReview::query()->create([
            'entity_type' => $lexeme->getMorphClass(),
            'entity_id' => $lexeme->id,
            'reviewer_id' => $actor->id,
            'decision' => $decision,
            'comments' => $comments,
        ]);
    }

    private function assertPublishRequirements(Lexeme $lexeme): void
    {
        $this->assertHasSenses($lexeme);
        $this->assertGermanNounRequirements($lexeme);

        $lexeme->loadMissing(['senses.partOfSpeech', 'senses.translations', 'language']);

        foreach ($lexeme->senses as $sense) {
            if (blank($sense->definition)) {
                throw ValidationException::withMessages([
                    'definition' => 'Each sense needs a definition before publish.',
                ]);
            }

            if ($sense->part_of_speech_id === null) {
                throw ValidationException::withMessages([
                    'part_of_speech' => 'Each sense needs a part of speech before publish.',
                ]);
            }

            if ($sense->translations->isEmpty()) {
                throw ValidationException::withMessages([
                    'translations' => 'Each sense needs at least one translation before publish.',
                ]);
            }
        }
    }

    private function assertHasSenses(Lexeme $lexeme): void
    {
        if ($lexeme->senses()->count() === 0) {
            throw ValidationException::withMessages([
                'senses' => 'A lexeme must have at least one sense.',
            ]);
        }
    }

    private function assertGermanNounRequirements(Lexeme $lexeme): void
    {
        $lexeme->loadMissing(['language', 'senses.partOfSpeech', 'senses.germanNounDetail']);

        if ($lexeme->language?->code !== 'de') {
            return;
        }

        foreach ($lexeme->senses as $sense) {
            if ($sense->partOfSpeech?->code !== 'noun') {
                continue;
            }

            $detail = $sense->germanNounDetail;

            if ($detail === null
                || ! GermanArticle::isValid(EnumValue::string($detail->article))
                || blank($detail->plural_form)
            ) {
                throw ValidationException::withMessages([
                    'grammar' => "German noun sense \"{$sense->definition}\" requires article (der/die/das) and plural form before publish.",
                ]);
            }
        }
    }

    private function assertCanReview(User $actor): void
    {
        if (! $actor->canReviewDictionary()) {
            throw ValidationException::withMessages([
                'role' => 'Only reviewers and admins can approve or reject content.',
            ]);
        }
    }

    private function assertCanPublish(User $actor): void
    {
        if (! $actor->canReviewDictionary()) {
            throw ValidationException::withMessages([
                'role' => 'Only reviewers and admins can publish or archive content.',
            ]);
        }
    }
}
