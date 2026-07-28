<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's users: 1 admin and 10 dummy students.
     */
    public function run(): void
    {
        // Admin account (fixed credentials for login).
        User::factory()->admin()->create([
            'name' => 'Administrator',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
        ]);

        // 10 dummy student accounts.
        for ($i = 1; $i <= 10; $i++) {
            User::factory()->siswa()->create([
                'name' => "Siswa $i",
                'email' => "siswa$i@gmail.com",
                'password' => Hash::make('password'),
            ]);
        }
    }
}
