<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
function conversationBetween(User $first, User $second, array $attributes = []): Conversation
{
    $userAId = min($first->id, $second->id);
    $userBId = max($first->id, $second->id);

    return Conversation::create(array_merge([
        'type'      => 'direct',
        'user_a_id' => $userAId,
        'user_b_id' => $userBId,
    ], $attributes));
}

describe('Conversation model', function () {
    it('persists user pair and optional last_message_at', function () {
        $alice = User::factory()->create();
        $bob   = User::factory()->create();
        $lastAt = now()->startOfSecond();

        $conversation = conversationBetween($alice, $bob, [
            'last_message_at' => $lastAt,
        ]);

        expect($conversation->exists)->toBeTrue()
            ->and($conversation->user_a_id)->toBe(min($alice->id, $bob->id))
            ->and($conversation->user_b_id)->toBe(max($alice->id, $bob->id))
            ->and($conversation->last_message_at?->eq($lastAt))->toBeTrue()
            ->and($conversation->created_at)->not->toBeNull()
            ->and($conversation->getAttributes())->not->toHaveKey('updated_at');
    });

    it('reuses the same direct conversation for a user pair via repository', function () {
        $alice = User::factory()->create();
        $bob   = User::factory()->create();

        $repo = app(\App\Repositories\Contracts\IConversationRepository::class);

        $first  = $repo->findOrCreateBetweenUsers($alice->id, $bob->id);
        $second = $repo->findOrCreateBetweenUsers($bob->id, $alice->id);

        expect($second->is($first))->toBeTrue()
            ->and(Conversation::where('type', 'direct')->count())->toBe(1);
    });

    it('cascades delete when a participant is force-deleted', function () {
        $alice = User::factory()->create();
        $bob   = User::factory()->create();

        $conversation = conversationBetween($alice, $bob);
        $conversationId = $conversation->id;

        $bob->forceDelete();

        expect(Conversation::find($conversationId))->toBeNull();
    });
});

describe('Conversation relationships', function () {
    it('belongs to userA and userB', function () {
        $lowerIdUser = User::factory()->create();
        $higherIdUser = User::factory()->create();

        $conversation = conversationBetween($lowerIdUser, $higherIdUser);
        $conversation->load(['userA', 'userB']);

        expect($conversation->userA)->toBeInstanceOf(User::class)
            ->and($conversation->userA->is($lowerIdUser->id < $higherIdUser->id ? $lowerIdUser : $higherIdUser))->toBeTrue()
            ->and($conversation->userB)->toBeInstanceOf(User::class)
            ->and($conversation->userB->is($lowerIdUser->id < $higherIdUser->id ? $higherIdUser : $lowerIdUser))->toBeTrue();
    });

    it('has many messages ordered by repository usage', function () {
        $alice = User::factory()->create();
        $bob   = User::factory()->create();

        $conversation = conversationBetween($alice, $bob);

        $first = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $alice->id,
            'body'            => 'Hello',
            'type'            => 'text',
        ]);

        $second = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $bob->id,
            'body'            => 'Hi back',
            'type'            => 'text',
        ]);

        $conversation->load('messages');

        expect($conversation->messages)->toHaveCount(2)
            ->and($conversation->messages->pluck('id')->all())->toBe([$first->id, $second->id]);
    });

    it('cascades delete to messages when conversation is removed', function () {
        $alice = User::factory()->create();
        $bob   = User::factory()->create();

        $conversation = conversationBetween($alice, $bob);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $alice->id,
            'body'            => 'Gone soon',
            'type'            => 'text',
        ]);

        $conversation->delete();

        expect(Message::withTrashed()->find($message->id))->toBeNull();
    });
});

describe('Message inverse relationship', function () {
    it('belongs to a conversation and sender', function () {
        $alice = User::factory()->create();
        $bob   = User::factory()->create();

        $conversation = conversationBetween($alice, $bob);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $alice->id,
            'body'            => 'Test',
            'type'            => 'text',
        ]);

        $message->load(['conversation', 'sender']);

        expect($message->conversation->is($conversation))->toBeTrue()
            ->and($message->sender->is($alice))->toBeTrue();
    });
});

describe('User conversation relationships', function () {
    it('exposes conversationsAsA and conversationsAsB', function () {
        $alice = User::factory()->create();
        $bob   = User::factory()->create();
        $carol = User::factory()->create();

        $withBob = conversationBetween($alice, $bob);
        $withCarol = conversationBetween($alice, $carol);

        $alice->load(['conversationsAsA', 'conversationsAsB']);

        $allForAlice = $alice->conversationsAsA
            ->merge($alice->conversationsAsB)
            ->unique('id')
            ->values();

        expect($allForAlice)->toHaveCount(2)
            ->and($allForAlice->pluck('id')->all())->toContain($withBob->id, $withCarol->id);
    });
});
