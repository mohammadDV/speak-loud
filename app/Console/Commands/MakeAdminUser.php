<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('admin:create {--email= : Email for the admin user} {--password= : Password for the admin user (min 8)}')]
#[Description('Create or promote a user to admin for the Filament panel')]
class MakeAdminUser extends Command
{
    public function handle(): int
    {
        $email = $this->option('email') ?: $this->ask('Admin email');
        $password = $this->option('password') ?: $this->secret('Admin password (min 8 chars)');

        if (! $email || ! $password || strlen($password) < 8) {
            $this->error('Email is required and password must be at least 8 characters.');

            return self::FAILURE;
        }

        $user = User::withTrashed()->firstOrNew(['email' => $email]);

        $user->fill([
            'uuid' => $user->uuid ?: (string) Str::uuid(),
            'password' => $password,
            'role' => 'admin',
            'status' => 'active',
        ]);

        if ($user->trashed()) {
            $user->restore();
        }

        if (! $user->email_verified_at) {
            $user->email_verified_at = now();
        }

        $user->save();

        $this->info("Admin user ready: {$user->email}");

        return self::SUCCESS;
    }
}
