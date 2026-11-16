<?php

namespace App\Domain\Learning\Actions;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Models\Sense;
use App\Domain\Learning\Enums\SenseFlagContext;
use App\Domain\Learning\Models\UserSenseFlag;
use App\Models\User;
use InvalidArgumentException;

class ToggleUserSenseFlag
{
    public function execute(
        User $user,
        int $senseId,
        string $flag,
        SenseFlagContext $context = SenseFlagContext::Article,
    ): UserSenseFlag {
        if (! in_array($flag, ['important', 'learned'], true)) {
            throw new InvalidArgumentException('Flag must be important or learned.');
        }

        if (! $this->senseExistsForContext($senseId, $context)) {
            throw new InvalidArgumentException('Sense not found.');
        }

        $record = UserSenseFlag::query()->firstOrNew([
            'user_id' => $user->id,
            'sense_id' => $senseId,
            'context' => $context,
        ]);

        if ($flag === 'important') {
            $record->is_important = ! (bool) $record->is_important;
        } else {
            $record->is_learned = ! (bool) $record->is_learned;
        }

        $record->save();

        return $record;
    }

    private function senseExistsForContext(int $senseId, SenseFlagContext $context): bool
    {
        $query = Sense::query()
            ->whereKey($senseId)
            ->where('status', ContentStatus::Published->value)
            ->whereNull('deleted_at')
            ->whereHas('lexeme', function ($query): void {
                $query->where('status', ContentStatus::Published->value)
                    ->whereNull('deleted_at')
                    ->whereHas('language', fn ($language) => $language->where('code', 'de'));
            });

        if ($context === SenseFlagContext::Article) {
            $query->whereHas('germanNounDetail', fn ($detail) => $detail->whereNotNull('article'));
        } else {
            $query->whereHas('translations', fn ($translations) => $translations
                ->where('status', ContentStatus::Published->value));
        }

        return $query->exists();
    }
}
