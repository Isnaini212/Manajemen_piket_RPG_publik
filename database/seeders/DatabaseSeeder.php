<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Order matters: users must exist before their student profiles.
     */
    public function run(): void
    {
        $this->call([
            SystemConfigSeeder::class,
            SemesterSeeder::class,
            UserSeeder::class,
            StudentProfileSeeder::class,
        ]);
    }
}
