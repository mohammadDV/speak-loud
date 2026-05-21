<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IUserRepository
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findByUuid(string $uuid): ?User;

    public function findPublicProfileBySlug(string $profileSlug): ?User;

    public function areBlocked(int $userId1, int $userId2): bool;

    public function create(array $data): User;

    public function update(int $id, array $data): User;

    public function searchPartners(array $filters, int $excludeUserId): LengthAwarePaginator;
}
