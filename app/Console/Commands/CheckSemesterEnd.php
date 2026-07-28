<?php

namespace App\Console\Commands;

use App\Models\Semester;
use App\Services\Contracts\SemesterServiceInterface;
use Illuminate\Console\Command;

class CheckSemesterEnd extends Command
{
    protected $signature = 'piket:check-semester-end';

    protected $description = 'Reset otomatis ketika semester aktif sudah berakhir.';

    public function handle(SemesterServiceInterface $semesterService): int
    {
        $semester = Semester::where('is_active', true)->first();

        if (! $semester) {
            $this->info('Tidak ada semester aktif');

            return self::SUCCESS;
        }

        if ($semester->end_date < today()) {
            $this->info("Semester {$semester->name} berakhir, memulai reset...");
            $semesterService->resetAll();
            $this->info('Reset semester selesai');
        } else {
            $this->info('Semester masih aktif');
        }

        return self::SUCCESS;
    }
}
