<?php

namespace App\Repositories;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Schedule;
use App\Repositories\Contracts\IConversationRepository;
use Illuminate\Support\Collection;

class ConversationRepository implements IConversationRepository
{
    public function findById(int $id): ?Conversation
    {
        return Conversation::find($id);
    }

    public function findBetweenUsers(int $userId1, int $userId2): ?Conversation
    {
        ['user_a_id' => $userAId, 'user_b_id' => $userBId] = $this->normalizePair($userId1, $userId2);

        return Conversation::query()
            ->where('type', 'direct')
            ->where('user_a_id', $userAId)
            ->where('user_b_id', $userBId)
            ->first();
    }

    public function findOrCreateBetweenUsers(int $userId1, int $userId2): Conversation
    {
        return $this->findBetweenUsers($userId1, $userId2)
            ?? $this->create([
                'type'      => 'direct',
                ...$this->normalizePair($userId1, $userId2),
            ]);
    }

    public function findOrCreateForSchedule(Schedule $schedule): Conversation
    {
        return Conversation::query()
            ->where('type', 'schedule_group')
            ->where('schedule_id', $schedule->id)
            ->first()
            ?? Conversation::create([
                'type'        => 'schedule_group',
                'schedule_id' => $schedule->id,
            ]);
    }

    public function syncScheduleGroupMembers(Schedule $schedule): Conversation
    {
        $schedule->loadMissing([
            'claims' => fn ($q) => $q->where('status', 'accepted'),
        ]);

        $conversation = $this->findOrCreateForSchedule($schedule);

        $memberIds = collect([$schedule->user_id])
            ->merge($schedule->claims->pluck('sender_id'))
            ->unique()
            ->values();

        foreach ($memberIds as $userId) {
            ConversationParticipant::firstOrCreate([
                'conversation_id' => $conversation->id,
                'user_id'         => $userId,
            ]);
        }

        return $conversation->load(['participants.profile', 'schedule.language']);
    }

    public function userCanAccess(int $conversationId, int $userId): bool
    {
        $conversation = $this->findById($conversationId);

        return $conversation?->userCanAccess($userId) ?? false;
    }

    public function forUser(int $userId): Collection
    {
        return Conversation::query()
            ->where(function ($query) use ($userId) {
                $query->where(function ($direct) use ($userId) {
                    $direct->where('type', 'direct')
                        ->where(fn ($q) => $q->where('user_a_id', $userId)->orWhere('user_b_id', $userId));
                })->orWhere(function ($group) use ($userId) {
                    $group->where('type', 'schedule_group')
                        ->whereHas('participants', fn ($q) => $q->where('users.id', $userId));
                });
            })
            ->with([
                'userA.profile',
                'userB.profile',
                'schedule.language',
                'participants.profile',
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(array $data): Conversation
    {
        if (($data['type'] ?? 'direct') === 'direct' && isset($data['user_a_id'], $data['user_b_id'])) {
            $data = array_merge($data, $this->normalizePair($data['user_a_id'], $data['user_b_id']));
        }

        return Conversation::create($data);
    }

    /**
     * @return array{user_a_id: int, user_b_id: int}
     */
    private function normalizePair(int $userId1, int $userId2): array
    {
        return $userId1 < $userId2
            ? ['user_a_id' => $userId1, 'user_b_id' => $userId2]
            : ['user_a_id' => $userId2, 'user_b_id' => $userId1];
    }
}
