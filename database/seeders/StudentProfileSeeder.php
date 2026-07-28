<?php

namespace Database\Seeders;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentProfileSeeder extends Seeder
{
    /**
     * Create a starting profile for every dummy student.
     *
     * Each profile starts with the default RPG stats:
     * lives = 3, status = 'citizen', xp = 0.
     */
    public function run(): void
    {
        $students = User::query()
            ->where('role', 'siswa')
            ->whereDoesntHave('studentProfile')
            ->get();

        foreach ($students as $student) {
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'xp' => 0,
                'lives' => 3,
                'status' => 'citizen',
                'status_since' => now(),
            ]);
        }
    }
}
