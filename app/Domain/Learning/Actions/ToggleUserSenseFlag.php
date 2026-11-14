<?php

namespace App\Domain\Learning\Actions;

use App\Domain\Dictionary\Models\Sense;
use App\Domain\Learning\Models\UserSenseFlag;
use App\Models\User;
use InvalidArgumentException;

class ToggleUserSenseFlag
{
    /**
     * @param  'important'|'learned'  $flag
     */
    public function execute(User $user, int $senseId, string $flag): UserSenseFlag
    {
        if (! in_array($flag, ['important', 'learned'], true)) {
            throw new InvalidArgumentException('Flag must be important or learned.');
        }

        if (! Sense::query()->whereKey($senseId)->exists()) {
            throw new InvalidArgumentException('Sense not found.');
        }

        $record = UserSenseFlag::query()->firstOrNew([
            'user_id' => $user->id,
            'sense_id' => $senseId,
        ]);

        if ($flag === 'important') {
            $record->is_important = ! (bool) $record->is_important;
        } else {
            $record->is_learned = ! (bool) $record->is_learned;
        }

        $record->save();

        return $record;
    }
}
