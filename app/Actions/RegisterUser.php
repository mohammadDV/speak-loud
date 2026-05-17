<?php

namespace App\Actions;

use App\Models\User;
use App\Models\UserProfile;
use App\Repositories\Contracts\IUserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterUser
{
    public function __construct(private readonly IUserRepository $users) {}

    public function execute(array $data): User
    {
        $user = $this->users->create([
            'uuid'     => Str::uuid(),
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        UserProfile::create([
            'user_id'      => $user->id,
            'username'     => $data['username'],
            'display_name' => $data['display_name'] ?? $data['username'],
        ]);

        return $user;
    }
}
