<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\PasswordRules;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

#[Signature('admin:create {--email= : Email for the admin user} {--password= : Password for the admin user}')]
#[Description('Create or promote a user to admin for the Filament panel')]
class MakeAdminUser extends Command
{
    public function handle(): int
    {
        $email = $this->option('email') ?: $this->ask('Admin email');
        $password = $this->option('password') ?: $this->secret('Admin password');

        if (! $email || ! $password) {
            $this->error('Email and password are required.');

            return self::FAILURE;
        }

        $validator = Validator::make(
            ['password' => $password],
            ['password' => PasswordRules::validationRules(confirmed: false)],
        );

        if ($validator->fails()) {
            $this->error('Password does not meet the security requirements:');
            foreach (PasswordRules::requirements() as $requirement) {
                $this->line(" - {$requirement}");
            }

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
