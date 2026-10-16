<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

uses(RefreshDatabase::class)->in('Feature');

function actingAsUser(): User
{
    $user = User::factory()->create();
    test()->actingAs($user);

    return $user;
}
