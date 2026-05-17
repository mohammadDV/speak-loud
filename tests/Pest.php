<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->in('Feature');

function actingAsUser(): \App\Models\User
{
    $user = \App\Models\User::factory()->create();
    test()->actingAs($user);
    return $user;
}
