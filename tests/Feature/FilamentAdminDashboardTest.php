<?php

use App\Models\User;

test('admin dashboard injects livewire assets and renders widgets', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Dashboard', false)
        ->assertSee('Welcome', false)
        ->assertSee('livewire', false)
        ->assertSee('AdminStatsOverview', false);
});
