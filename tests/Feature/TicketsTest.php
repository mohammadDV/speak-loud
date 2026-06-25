<?php

use App\Actions\CreateTicket;
use App\Actions\ReplyToTicket;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketMessage;
use App\Models\User;
use App\Models\UserProfile;
use Livewire\Volt\Volt;

test('guests cannot access support tickets', function () {
    $this->get(route('tickets.index'))->assertRedirect(route('login'));
});

test('user can create a ticket and see it in the list', function () {
    $user = actingAsUser();

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => 'ticketuser',
        'display_name' => 'Ticket User',
    ]);

    $category = TicketCategory::create([
        'name' => 'Account',
        'slug' => 'account',
    ]);

    $ticket = app(CreateTicket::class)->execute(
        $user,
        $category->id,
        'Cannot update my profile',
        'The save button does nothing when I edit my bio.',
    );

    expect($ticket->status)->toBe('open')
        ->and($ticket->messages)->toHaveCount(1)
        ->and($ticket->messages->first()->body)->toBe('The save button does nothing when I edit my bio.');

    $this->get(route('tickets.index'))
        ->assertOk()
        ->assertSee('Cannot update my profile');

    $this->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee('Cannot update my profile')
        ->assertSee('The save button does nothing when I edit my bio.')
        ->assertSee('Our team is reviewing your message');
});

test('user cannot create another ticket while waiting for admin response', function () {
    $user = actingAsUser();

    app(CreateTicket::class)->execute(
        $user,
        null,
        'First ticket',
        'Need help with my account.',
    );

    expect(fn () => app(CreateTicket::class)->execute(
        $user,
        null,
        'Second ticket',
        'Another issue.',
    ))->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('user cannot reply until admin has responded', function () {
    $user = actingAsUser();

    $ticket = app(CreateTicket::class)->execute(
        $user,
        null,
        'Help needed',
        'Something is broken.',
    );

    expect(fn () => app(ReplyToTicket::class)->execute(
        $ticket->fresh(),
        $user,
        'Any update?',
    ))->toThrow(\Illuminate\Validation\ValidationException::class);

    $this->actingAs($user)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertDontSee('Write a reply...')
        ->assertSee('Our team is reviewing your message');
});

test('user cannot view another users ticket', function () {
    $owner = User::factory()->create();
    $other = actingAsUser();

    $ticket = Ticket::create([
        'user_id'  => $owner->id,
        'subject'  => 'Private ticket',
        'status'   => 'open',
        'priority' => 'normal',
    ]);

    $this->get(route('tickets.show', $ticket))->assertNotFound();
});

test('admin reply is visible to the ticket owner', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => 'supportseeker',
        'display_name' => 'Support Seeker',
    ]);

    $ticket = app(CreateTicket::class)->execute(
        $user,
        null,
        'Billing question',
        'Where can I see my invoices?',
    );

    app(ReplyToTicket::class)->execute(
        $ticket->fresh(),
        $admin,
        'You can find billing details in your account settings under Plans.',
    );

    $ticket->refresh();

    expect($ticket->status)->toBe('waiting_user')
        ->and($ticket->assigned_to)->toBe($admin->id);

    $messages = TicketMessage::where('ticket_id', $ticket->id)->orderBy('id')->get();

    expect($messages)->toHaveCount(2)
        ->and($messages[1]->body)->toBe('You can find billing details in your account settings under Plans.');

    $this->actingAs($user)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee('Support team')
        ->assertSee('You can find billing details in your account settings under Plans.')
        ->assertSee('Write a reply...');
});

test('user reply reopens a waiting ticket', function () {
    $user = actingAsUser();
    $admin = User::factory()->admin()->create();

    $ticket = Ticket::create([
        'user_id'     => $user->id,
        'subject'     => 'Need more help',
        'status'      => 'waiting_user',
        'priority'    => 'normal',
        'assigned_to' => $admin->id,
    ]);

    TicketMessage::create([
        'ticket_id'  => $ticket->id,
        'sender_id'  => $admin->id,
        'body'       => 'Can you share a screenshot?',
        'is_internal'=> false,
    ]);

    app(ReplyToTicket::class)->execute(
        $ticket->fresh(),
        $user,
        'Sure, here are the steps I followed.',
    );

    expect($ticket->fresh()->status)->toBe('in_progress');

    expect(fn () => app(ReplyToTicket::class)->execute(
        $ticket->fresh(),
        $user,
        'One more thing...',
    ))->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('user can create a ticket from the support page form', function () {
    $user = actingAsUser();

    TicketCategory::create([
        'name' => 'Bug report',
        'slug' => 'bug',
    ]);

    Volt::test('tickets.index')
        ->call('openCreateModal')
        ->set('subject', 'App crashes on login')
        ->set('body', 'It happens every time on Safari.')
        ->call('createTicket')
        ->assertRedirect();

    $ticket = Ticket::first();

    expect($ticket)->not->toBeNull()
        ->and($ticket->subject)->toBe('App crashes on login');
});
