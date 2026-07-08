<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Support\PasswordRules;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('uuid')
                    ->label('UUID')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->rules(fn (string $operation): array => $operation === 'create'
                        ? PasswordRules::validationRules(confirmed: false)
                        : ['nullable', PasswordRules::rule()])
                    ->helperText(PasswordRules::instructions()),
                Select::make('role')
                    ->options(['user' => 'User', 'admin' => 'Admin', 'moderator' => 'Moderator'])
                    ->default('user')
                    ->required(),
                Select::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive', 'banned' => 'Banned'])
                    ->default('active')
                    ->required(),
                DateTimePicker::make('banned_at'),
                Textarea::make('ban_reason')
                    ->columnSpanFull(),
                TextInput::make('banned_by')
                    ->numeric(),
                DateTimePicker::make('last_login_at'),
                DateTimePicker::make('terms_accepted_at'),
                TextInput::make('terms_version'),
            ]);
    }
}
