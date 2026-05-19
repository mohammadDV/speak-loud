<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\UserProfile;
use App\Repositories\ConversationRepository;

beforeEach(function () {
    $this->repository = new ConversationRepository;
});

function makeUserWithProfile(string $suffix): User
{
    $user = User::factory()->create([
        'email' => "user-{$suffix}@speakloud.test",
    ]);

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => "user{$suffix}",
        'display_name' => "User {$suffix}",
    ]);

    return $user;
}

describe('ConversationRepository', function () {
    it('finds a conversation regardless of argument order', function () {
        $alice = User::factory()->create();
        $bob   = User::factory()->create();

        $stored = Conversation::create([
            'user_a_id' => min($alice->id, $bob->id),
            'user_b_id' => max($alice->id, $bob->id),
        ]);

        expect($this->repository->findBetweenUsers($bob->id, $alice->id)?->is($stored))->toBeTrue()
            ->and($this->repository->findBetweenUsers($alice->id, $bob->id)?->is($stored))->toBeTrue();
    });

    it('returns null when no conversation exists between users', function () {
        $alice = User::factory()->create();
        $bob   = User::factory()->create();

        expect($this->repository->findBetweenUsers($alice->id, $bob->id))->toBeNull();
    });

    it('findOrCreateBetweenUsers reuses an existing conversation', function () {
        $alice = User::factory()->create();
        $bob   = User::factory()->create();

        $existing = Conversation::create([
            'user_a_id' => min($alice->id, $bob->id),
            'user_b_id' => max($alice->id, $bob->id),
        ]);

        $result = $this->repository->findOrCreateBetweenUsers($bob->id, $alice->id);

        expect($result->is($existing))->toBeTrue()
            ->and(Conversation::count())->toBe(1);
    });

    it('findOrCreateBetweenUsers stores normalized user ids', function () {
        $higher = User::factory()->create();
        $lower  = User::factory()->create();

        if ($lower->id > $higher->id) {
            [$lower, $higher] = [$higher, $lower];
        }

        $conversation = $this->repository->findOrCreateBetweenUsers($higher->id, $lower->id);

        expect($conversation->user_a_id)->toBe($lower->id)
            ->and($conversation->user_b_id)->toBe($higher->id);
    });

    it('create normalizes user ids when passed in reverse order', function () {
        $alice = User::factory()->create();
        $bob   = User::factory()->create();

        $conversation = $this->repository->create([
            'user_a_id' => $bob->id,
            'user_b_id' => $alice->id,
        ]);

        expect($conversation->user_a_id)->toBe(min($alice->id, $bob->id))
            ->and($conversation->user_b_id)->toBe(max($alice->id, $bob->id));
    });

    it('lists conversations for a user with profiles ordered by recent activity', function () {
        $viewer = makeUserWithProfile('viewer');
        $partnerA = makeUserWithProfile('a');
        $partnerB = makeUserWithProfile('b');

        $older = Conversation::create([
            'user_a_id'       => min($viewer->id, $partnerA->id),
            'user_b_id'       => max($viewer->id, $partnerA->id),
            'last_message_at' => now()->subDay(),
        ]);

        $newer = Conversation::create([
            'user_a_id'       => min($viewer->id, $partnerB->id),
            'user_b_id'       => max($viewer->id, $partnerB->id),
            'last_message_at' => now(),
        ]);

        Conversation::create([
            'user_a_id' => min($partnerA->id, $partnerB->id),
            'user_b_id' => max($partnerA->id, $partnerB->id),
        ]);

        $results = $this->repository->forUser($viewer->id);

        expect($results)->toHaveCount(2)
            ->and($results->first()->is($newer))->toBeTrue()
            ->and($results->last()->is($older))->toBeTrue()
            ->and($results->first()->relationLoaded('userA'))->toBeTrue()
            ->and($results->first()->relationLoaded('userB'))->toBeTrue();
    });

    it('findById returns the conversation record', function () {
        $alice = User::factory()->create();
        $bob   = User::factory()->create();

        $conversation = Conversation::create([
            'user_a_id' => min($alice->id, $bob->id),
            'user_b_id' => max($alice->id, $bob->id),
        ]);

        expect($this->repository->findById($conversation->id)?->is($conversation))->toBeTrue()
            ->and($this->repository->findById(999_999))->toBeNull();
    });
});

describe('ConversationRepository with messages', function () {
    it('keeps a single thread when multiple claims share the same pair', function () {
        $host   = User::factory()->create();
        $guest  = User::factory()->create();

        $first = $this->repository->findOrCreateBetweenUsers($guest->id, $host->id);

        Message::create([
            'conversation_id' => $first->id,
            'sender_id'       => $guest->id,
            'body'            => 'First claim note',
            'type'            => 'text',
        ]);

        $second = $this->repository->findOrCreateBetweenUsers($host->id, $guest->id);

        Message::create([
            'conversation_id' => $second->id,
            'sender_id'       => $host->id,
            'body'            => 'Second claim note',
            'type'            => 'text',
        ]);

        expect($second->is($first))->toBeTrue()
            ->and(Conversation::count())->toBe(1)
            ->and($first->fresh()->messages)->toHaveCount(2);
    });
});
