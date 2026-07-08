<?php

use App\Support\PasswordRules;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

test('password rules accept a strong password', function () {
    $validator = Validator::make(
        [
            'password' => 'SecurePass1!',
            'password_confirmation' => 'SecurePass1!',
        ],
        ['password' => PasswordRules::validationRules()],
    );

    expect($validator->passes())->toBeTrue();
});

test('password rules reject passwords missing uppercase letters', function () {
    $validator = Validator::make(
        ['password' => 'securepass1!'],
        ['password' => PasswordRules::validationRules()],
    );

    expect($validator->fails())->toBeTrue();
});

test('password rules reject passwords missing symbols', function () {
    $validator = Validator::make(
        ['password' => 'SecurePass1'],
        ['password' => PasswordRules::validationRules()],
    );

    expect($validator->fails())->toBeTrue();
});
