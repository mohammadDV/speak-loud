<?php

namespace App\Repositories;

use App\Models\Claim;
use App\Models\Conversation;
use App\Repositories\Contracts\IClaimRepository;
use Illuminate\Support\Collection;

class ClaimRepository implements IClaimRepository
{
    private const LIST_LIMIT = 50;

    public function findById(int $id): ?Claim
    {
        return Claim::find($id);
    }

    public function findBySenderAndSchedule(int $senderId, int $scheduleId): ?Claim
    {
        return Claim::where('sender_id', $senderId)
            ->where('schedule_id', $scheduleId)
            ->first();
    }

    public function findDirectClaimBetweenUsers(int $senderId, int $receiverId): ?Claim
    {
        return Claim::query()
            ->where('sender_id', $senderId)
            ->where('receiver_id', $receiverId)
            ->where('type', 'direct')
            ->whereNull('schedule_id')
            ->first();
    }

    public function create(array $data): Claim
    {
        return Claim::create($data);
    }

    public function updateStatus(int $id, string $status): Claim
    {
        $claim = Claim::findOrFail($id);

        $claim->update([
            'status' => $status,
            'responded_at' => now(),
        ]);

        return $claim->fresh();
    }

    public function reopen(int $id, ?string $message): Claim
    {
        $claim = Claim::findOrFail($id);

        $claim->update([
            'status' => 'pending',
            'message' => $message,
            'responded_at' => null,
        ]);

        return $claim->fresh();
    }

    public function incomingForUser(int $userId, array $filters = []): Collection
    {
        $query = Claim::query()
            ->where('receiver_id', $userId)
            ->with([
                'sender.profile',
                'schedule' => fn ($query) => $query
                    ->with('language')
                    ->withCount([
                        'claims as accepted_claims_count' => fn ($q) => $q->where('status', 'accepted'),
                    ]),
            ]);

        $this->applyListFilters($query, $filters);

        $claims = $query->limit(self::LIST_LIMIT)->get();

        return $this->attachPartnerConversations($claims);
    }

    public function outgoingForUser(int $userId, array $filters = []): Collection
    {
        $query = Claim::query()
            ->where('sender_id', $userId)
            ->with([
                'receiver.profile',
                'schedule.language',
                'schedule.user.profile',
            ]);

        $this->applyListFilters($query, $filters);

        $claims = $query->limit(self::LIST_LIMIT)->get();

        return $this->attachPartnerConversations($claims);
    }

    /**
     * @param  array{status?: string, sort?: string}  $filters
     */
    private function applyListFilters($query, array $filters): void
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (($filters['sort'] ?? 'newest') === 'oldest') {
            $query->oldest()->orderBy('id');
        } else {
            $query->latest()->orderByDesc('id');
        }
    }

    private function attachPartnerConversations(Collection $claims): Collection
    {
        if ($claims->isEmpty()) {
            return $claims;
        }

        $pairs = $claims
            ->map(function (Claim $claim) {
                $a = min($claim->sender_id, $claim->receiver_id);
                $b = max($claim->sender_id, $claim->receiver_id);

                return ['user_a_id' => $a, 'user_b_id' => $b];
            })
            ->unique(fn (array $pair) => $pair['user_a_id'].':'.$pair['user_b_id'])
            ->values();

        $conversations = Conversation::query()
            ->where('type', 'direct')
            ->where(function ($query) use ($pairs) {
                foreach ($pairs as $pair) {
                    $query->orWhere(function ($q) use ($pair) {
                        $q->where('user_a_id', $pair['user_a_id'])
                            ->where('user_b_id', $pair['user_b_id']);
                    });
                }
            })
            ->get()
            ->keyBy(fn (Conversation $conversation) => $conversation->user_a_id.':'.$conversation->user_b_id);

        $claims->each(function (Claim $claim) use ($conversations) {
            $key = min($claim->sender_id, $claim->receiver_id).':'.max($claim->sender_id, $claim->receiver_id);
            $claim->setRelation('conversation', $conversations->get($key));
        });

        return $claims;
    }
}
