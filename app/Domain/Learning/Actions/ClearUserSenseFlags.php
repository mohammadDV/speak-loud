<?php

namespace App\Domain\Learning\Actions;

use App\Domain\Learning\Enums\SenseFlagContext;
use App\Domain\Learning\Models\UserSenseFlag;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ClearUserSenseFlags
{
    public function clearOne(
        User $user,
        int $senseId,
        string $flag,
        SenseFlagContext $context = SenseFlagContext::Article,
    ): void {
        $this->assertFlag($flag);

        $record = UserSenseFlag::query()
            ->where('user_id', $user->id)
            ->where('sense_id', $senseId)
            ->where('context', $context)
            ->first();

        if ($record === null) {
            return;
        }

        if ($flag === 'important') {
            $record->is_important = false;
        } else {
            $record->is_learned = false;
        }

        if (! $record->is_important && ! $record->is_learned) {
            $record->delete();

            return;
        }

        $record->save();
    }

    public function clearAll(
        User $user,
        string $flag,
        SenseFlagContext $context = SenseFlagContext::Article,
    ): int {
        $this->assertFlag($flag);

        return (int) DB::transaction(function () use ($user, $flag, $context): int {
            $column = $flag === 'important' ? 'is_important' : 'is_learned';

            $updated = UserSenseFlag::query()
                ->where('user_id', $user->id)
                ->where('context', $context)
                ->where($column, true)
                ->update([$column => false]);

            UserSenseFlag::query()
                ->where('user_id', $user->id)
                ->where('context', $context)
                ->where('is_important', false)
                ->where('is_learned', false)
                ->delete();

            return $updated;
        });
    }

    private function assertFlag(string $flag): void
    {
        if (! in_array($flag, ['important', 'learned'], true)) {
            throw new InvalidArgumentException('Flag must be important or learned.');
        }
    }
}
