<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\IUserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserRepository implements IUserRepository
{
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findByUuid(string $uuid): ?User
    {
        return User::where('uuid', $uuid)->first();
    }

    public function findPublicProfileBySlug(string $profileSlug): ?User
    {
        return User::query()
            ->where('status', 'active')
            ->whereHas('profile', fn ($q) => $q->where('profile_slug', $profileSlug))
            ->with([
                'profile',
                'languages.language',
                'interests',
                'tags',
            ])
            ->first();
    }

    public function areBlocked(int $userId1, int $userId2): bool
    {
        return DB::table('user_blocks')
            ->where(function ($q) use ($userId1, $userId2) {
                $q->where('blocker_id', $userId1)->where('blocked_id', $userId2);
            })
            ->orWhere(function ($q) use ($userId1, $userId2) {
                $q->where('blocker_id', $userId2)->where('blocked_id', $userId1);
            })
            ->exists();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(int $id, array $data): User
    {
        $user = User::findOrFail($id);
        $user->update($data);
        return $user->fresh();
    }

    public function searchPartners(array $filters, int $excludeUserId): LengthAwarePaginator
    {
        $blockedIds = DB::table('user_blocks')
            ->where('blocker_id', $excludeUserId)
            ->orWhere('blocked_id', $excludeUserId)
            ->pluck(DB::raw("CASE WHEN blocker_id = {$excludeUserId} THEN blocked_id ELSE blocker_id END"))
            ->all();

        $query = User::query()
            ->with('profile')
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->where('users.id', '!=', $excludeUserId)
            ->whereNotIn('users.id', $blockedIds)
            ->select('users.*', 'user_profiles.display_name', 'user_profiles.bio', 'user_profiles.country_code', 'user_profiles.profile_image_path');

        if (!empty($filters['language_id'])) {
            $query->whereExists(function ($sub) use ($filters) {
                $sub->select(DB::raw(1))
                    ->from('user_languages')
                    ->whereColumn('user_languages.user_id', 'users.id')
                    ->where('user_languages.language_id', $filters['language_id']);
            });
        }

        if (!empty($filters['level'])) {
            $query->whereExists(function ($sub) use ($filters) {
                $sub->select(DB::raw(1))
                    ->from('user_languages')
                    ->whereColumn('user_languages.user_id', 'users.id')
                    ->where('user_languages.level', $filters['level']);
            });
        }

        if (!empty($filters['country_code'])) {
            $query->where('user_profiles.country_code', $filters['country_code']);
        }

        if (!empty($filters['tags']) && is_array($filters['tags'])) {
            foreach ($filters['tags'] as $tag) {
                $query->whereExists(function ($sub) use ($tag) {
                    $sub->select(DB::raw(1))
                        ->from('user_tags')
                        ->whereColumn('user_tags.user_id', 'users.id')
                        ->where('user_tags.tag', $tag);
                });
            }
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('user_profiles.display_name', 'like', $search)
                  ->orWhere('user_profiles.bio', 'like', $search);
            });
        }

        return $query->paginate(20, ['*'], 'page', $filters['page'] ?? 1);
    }
}
