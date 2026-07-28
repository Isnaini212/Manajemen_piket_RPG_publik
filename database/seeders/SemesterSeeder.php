<?php

namespace Database\Seeders;

use App\Models\Semester;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SemesterSeeder extends Seeder
{
    /**
     * Seed one active semester (odd/ganjil semester of the current year).
     *
     * In Indonesia, the odd (ganjil) semester runs from July to December.
     */
    public function run(): void
    {
        $year = Carbon::now()->year;

        Semester::updateOrCreate(
            ['name' => "Ganjil {$year}/" . ($year + 1)],
            [
                'start_date' => Carbon::create($year, 7, 1)->toDateString(),
                'end_date' => Carbon::create($year, 12, 31)->toDateString(),
                'is_active' => true,
            ],
        );
    }
}
