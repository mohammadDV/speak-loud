<?php

namespace Database\Seeders;

use App\Domain\Dictionary\Models\Category;
use App\Domain\Dictionary\Models\DifficultyLevel;
use App\Domain\Dictionary\Models\PartOfSpeech;
use Illuminate\Database\Seeder;

class DictionaryReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $partsOfSpeech = [
            ['code' => 'noun', 'display_name' => 'Noun', 'sort_order' => 1],
            ['code' => 'verb', 'display_name' => 'Verb', 'sort_order' => 2],
            ['code' => 'adjective', 'display_name' => 'Adjective', 'sort_order' => 3],
            ['code' => 'adverb', 'display_name' => 'Adverb', 'sort_order' => 4],
            ['code' => 'preposition', 'display_name' => 'Preposition', 'sort_order' => 5],
            ['code' => 'pronoun', 'display_name' => 'Pronoun', 'sort_order' => 6],
            ['code' => 'conjunction', 'display_name' => 'Conjunction', 'sort_order' => 7],
            ['code' => 'determiner', 'display_name' => 'Determiner', 'sort_order' => 8],
            ['code' => 'particle', 'display_name' => 'Particle', 'sort_order' => 9],
            ['code' => 'numeral', 'display_name' => 'Numeral', 'sort_order' => 10],
            ['code' => 'interjection', 'display_name' => 'Interjection', 'sort_order' => 11],
        ];

        foreach ($partsOfSpeech as $pos) {
            PartOfSpeech::query()->updateOrCreate(
                ['code' => $pos['code']],
                $pos,
            );
        }

        $levels = [
            ['code' => 'A1', 'name' => 'Beginner', 'sort_order' => 1],
            ['code' => 'A2', 'name' => 'Elementary', 'sort_order' => 2],
            ['code' => 'B1', 'name' => 'Intermediate', 'sort_order' => 3],
            ['code' => 'B2', 'name' => 'Upper Intermediate', 'sort_order' => 4],
            ['code' => 'C1', 'name' => 'Advanced', 'sort_order' => 5],
            ['code' => 'C2', 'name' => 'Proficiency', 'sort_order' => 6],
        ];

        foreach ($levels as $level) {
            DifficultyLevel::query()->updateOrCreate(
                ['code' => $level['code']],
                $level,
            );
        }

        $categories = [
            ['code' => 'family', 'display_name' => 'Family', 'sort_order' => 1],
            ['code' => 'time_and_numbers', 'display_name' => 'Time and numbers', 'sort_order' => 2],
            ['code' => 'colors', 'display_name' => 'Colors', 'sort_order' => 3],
            ['code' => 'food', 'display_name' => 'Food', 'sort_order' => 4],
            ['code' => 'travel', 'display_name' => 'Travel', 'sort_order' => 5],
            ['code' => 'body', 'display_name' => 'Body', 'sort_order' => 6],
            ['code' => 'clothing', 'display_name' => 'Clothing', 'sort_order' => 7],
            ['code' => 'home', 'display_name' => 'Home', 'sort_order' => 8],
            ['code' => 'work', 'display_name' => 'Work', 'sort_order' => 9],
            ['code' => 'nature', 'display_name' => 'Nature', 'sort_order' => 10],
            ['code' => 'animals', 'display_name' => 'Animals', 'sort_order' => 11],
            ['code' => 'emotions', 'display_name' => 'Emotions', 'sort_order' => 12],
            ['code' => 'greetings', 'display_name' => 'Greetings', 'sort_order' => 13],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['code' => $category['code']],
                [...$category, 'is_active' => true],
            );
        }
    }
}
