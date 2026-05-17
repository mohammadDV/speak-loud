<?php

namespace Database\Seeders;

use App\Models\Interest;
use Illuminate\Database\Seeder;

class InterestSeeder extends Seeder
{
    public function run(): void
    {
        $interests = [
            ['slug' => 'movies',       'name_en' => 'Movies'],
            ['slug' => 'music',        'name_en' => 'Music'],
            ['slug' => 'books',        'name_en' => 'Books'],
            ['slug' => 'tech',         'name_en' => 'Tech'],
            ['slug' => 'sports',       'name_en' => 'Sports'],
            ['slug' => 'travel',       'name_en' => 'Travel'],
            ['slug' => 'cooking',      'name_en' => 'Cooking'],
            ['slug' => 'art',          'name_en' => 'Art'],
            ['slug' => 'gaming',       'name_en' => 'Gaming'],
            ['slug' => 'hiking',       'name_en' => 'Hiking'],
            ['slug' => 'photography',  'name_en' => 'Photography'],
            ['slug' => 'science',      'name_en' => 'Science'],
            ['slug' => 'business',     'name_en' => 'Business'],
            ['slug' => 'anime',        'name_en' => 'Anime'],
            ['slug' => 'history',      'name_en' => 'History'],
            ['slug' => 'yoga',         'name_en' => 'Yoga'],
            ['slug' => 'fashion',      'name_en' => 'Fashion'],
            ['slug' => 'food',         'name_en' => 'Food'],
            ['slug' => 'coding',       'name_en' => 'Coding'],
            ['slug' => 'daily',        'name_en' => 'Daily Life'],
        ];

        foreach ($interests as $interest) {
            Interest::firstOrCreate(['slug' => $interest['slug']], $interest);
        }
    }
}
