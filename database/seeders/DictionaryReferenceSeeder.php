<?php

namespace Database\Seeders;

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
    }
}
