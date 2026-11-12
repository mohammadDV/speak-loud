<?php

namespace App\Filament\Admin\Resources;

use App\Models\User;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

abstract class StaffResource extends Resource
{
    public static function canViewAny(): bool
    {
        $user = static::currentUser();

        return $user !== null && $user->isStaff() && $user->status === 'active';
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::currentUser()?->isAdmin() ?? false;
    }

    public static function canRestore(Model $record): bool
    {
        return static::canViewAny();
    }

    protected static function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
