<?php

use App\Http\Controllers\Api\V1\Dictionary\CategoryController;
use App\Http\Controllers\Api\V1\Dictionary\DictionarySearchController;
use App\Http\Controllers\Api\V1\Dictionary\LanguageController;
use App\Http\Controllers\Api\V1\Dictionary\LexemeController;
use App\Http\Controllers\Api\V1\Dictionary\SenseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:120,1')->group(function (): void {
    Route::get('languages', [LanguageController::class, 'index']);
    Route::get('categories', [CategoryController::class, 'index']);

    Route::get('dictionary/search', DictionarySearchController::class)
        ->middleware('throttle:60,1');

    Route::get('lexemes/{lexeme}', [LexemeController::class, 'show']);
    Route::get('lexemes/{lexeme}/senses', [LexemeController::class, 'senses']);
    Route::get('senses/{sense}', [SenseController::class, 'show']);
});
