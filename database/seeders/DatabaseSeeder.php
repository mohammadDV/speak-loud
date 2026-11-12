<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LanguageSeeder::class,
            InterestSeeder::class,
            ContentSeeder::class,
            DictionaryReferenceSeeder::class,
        ]);

        if (app()->environment(['local', 'testing'])) {
            $this->call(DemoDataSeeder::class);
            $this->call(DictionaryFixtureSeeder::class);
        }
    }
}
