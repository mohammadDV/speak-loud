<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\UserProfile;
use Livewire\Volt\Volt;

function messagesPageUser(string $email, string $username): User
{
    $user = User::factory()->create([
        'email' => $email,
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id' => $user->id,
        'username' => $username,
        'profile_slug' => $username,
        'display_name' => $username,
    ]);

    return $user;
}

test('messages index lists conversations and opens a chat with back control', function () {
    $alice = messagesPageUser('alice-messages@speakloudmate.test', 'alicemessages');
    $bob = messagesPageUser('bob-messages@speakloudmate.test', 'bobmessages');

    $conversation = Conversation::create([
        'type' => 'direct',
        'user_a_id' => min($alice->id, $bob->id),
        'user_b_id' => max($alice->id, $bob->id),
    ]);

    Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $bob->id,
        'body' => 'Hello from Bob',
    ]);

    Volt::actingAs($alice)
        ->test('messages.index')
        ->assertOk()
        ->assertSee('bobmessages')
        ->assertDontSee('No conversations yet.')
        ->assertSeeHtml('h-[calc(100dvh-52px-3.5rem-env(safe-area-inset-bottom,0px))]')
        ->assertSeeHtml('x-data="{ chatOpen:')
        ->call('selectConversation', $conversation->id)
        ->assertSet('activeConversation', $conversation->id)
        ->assertSee('Hello from Bob')
        ->assertSee('← Chats')
        ->assertSeeHtml('x-on:click="chatOpen = false"')
        ->assertSee('Type a message...')
        ->call('clearConversation')
        ->assertSet('activeConversation', null)
        ->assertDontSee('Hello from Bob');
});

test('messages show page renders chat with mobile-safe height and back link', function () {
    $alice = messagesPageUser('alice-show@speakloudmate.test', 'aliceshow');
    $bob = messagesPageUser('bob-show@speakloudmate.test', 'bobshow');

    $conversation = Conversation::create([
        'type' => 'direct',
        'user_a_id' => min($alice->id, $bob->id),
        'user_b_id' => max($alice->id, $bob->id),
    ]);

    Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $bob->id,
        'body' => 'Show page hello',
    ]);

    $this->actingAs($alice)
        ->get(route('messages.show', $conversation->id))
        ->assertOk()
        ->assertSee('Show page hello')
        ->assertSee('← Chats')
        ->assertSee('bobshow')
        ->assertSee('Type a message...')
        ->assertSee('h-[calc(100dvh-52px-3.5rem-env(safe-area-inset-bottom,0px))]', false)
        ->assertDontSee('-mb-20', false);
});
