<?php

namespace App\Services;

use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\Semester;
use App\Models\StatusChangeLog;
use App\Models\StudentProfile;
use App\Models\SystemConfig;
use App\Models\User;
use App\Services\Contracts\SemesterServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SemesterService implements SemesterServiceInterface
{
    /**
     * Reset the game state for a new semester:
     *  - zero every student's XP, refill lives, set status back to citizen
     *  - resolve any still-open status change logs
     *  - deactivate the current semester and create the next one
     *  - notify every user
     *
     * Historical data (student_badges, xp_logs, life_logs, status_change_logs)
     * is intentionally preserved.
     */
    public function resetAll(): void
    {
        try {
            DB::transaction(function (): void {
                $livesMax = (int) (SystemConfig::get('lives_max') ?? 3);

                // 1. Reset student profiles (only users with the siswa role).
                StudentProfile::whereHas('user', fn ($q) => $q->where('role', UserRole::Siswa->value))
                    ->update([
                        'xp' => 0,
                        'lives' => $livesMax,
                        'status' => StudentStatus::CITIZEN->value,
                        'status_since' => null,
                    ]);

                // 2. Close any open status change logs.
                StatusChangeLog::whereNull('resolved_at')->update(['resolved_at' => now()]);

                // 3. Deactivate current semester, create the next one.
                $current = Semester::where('is_active', true)->first();
                $current?->update(['is_active' => false]);

                $next = $this->buildNextSemester($current);

                Semester::create([
                    'name' => $next['name'],
                    'start_date' => $next['start_date'],
                    'end_date' => $next['end_date'],
                    'is_active' => true,
                ]);

                // 4. Notify every user.
                $now = now();
                $rows = User::pluck('id')->map(fn ($id) => [
                    'user_id' => $id,
                    'type' => 'new_semester',
                    'message' => 'Semester baru telah dimulai! XP dan status telah direset. Semangat!',
                    'is_read' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                if ($rows !== []) {
                    Notification::insert($rows);
                }

                Log::info('Semester direset', [
                    'previous_semester' => $current?->name,
                    'new_semester' => $next['name'],
                    'notified_users' => count($rows),
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Gagal mereset semester', ['error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * Derive the next semester's name and date range by continuing the
     * ganjil/genap sequence of the previous semester.
     *
     * @return array{name: string, start_date: string, end_date: string}
     */
    private function buildNextSemester(?Semester $current): array
    {
        if ($current && preg_match('/(\d{4})\/(\d{4})/', $current->name, $m)) {
            $y1 = (int) $m[1];
            $y2 = (int) $m[2];

            // Ganjil -> Genap of the same academic year.
            if (stripos($current->name, 'Ganjil') !== false) {
                return [
                    'name' => "Genap {$y1}/{$y2}",
                    'start_date' => Carbon::create($y2, 1, 1)->toDateString(),
                    'end_date' => Carbon::create($y2, 6, 30)->toDateString(),
                ];
            }

            // Genap -> Ganjil of the next academic year.
            if (stripos($current->name, 'Genap') !== false) {
                $n1 = $y1 + 1;
                $n2 = $y2 + 1;

                return [
                    'name' => "Ganjil {$n1}/{$n2}",
                    'start_date' => Carbon::create($n1, 7, 1)->toDateString(),
                    'end_date' => Carbon::create($n1, 12, 31)->toDateString(),
                ];
            }
        }

        // Fallback: start a fresh ganjil semester for the current year.
        $year = (int) now()->year;

        return [
            'name' => "Ganjil {$year}/" . ($year + 1),
            'start_date' => Carbon::create($year, 7, 1)->toDateString(),
            'end_date' => Carbon::create($year, 12, 31)->toDateString(),
        ];
    }
}
