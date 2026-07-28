<?php

namespace App\Console\Commands;

use App\Enums\ClaimStatus;
use App\Enums\UserRole;
use App\Enums\VerifyStatus;
use App\Models\DutyClaim;
use App\Models\DutySlot;
use App\Models\LifeLog;
use App\Models\Notification;
use App\Models\ReplacementDuty;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\SystemConfig;
use App\Services\Contracts\PenaltyServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckMissedDuties extends Command
{
    protected $signature = 'piket:check-missed';

    protected $description = 'Terapkan penalti untuk piket yang tidak diselesaikan dan siswa yang tidak memenuhi kuota mingguan.';

    public function handle(PenaltyServiceInterface $penalty): int
    {
        $activeSemester = Semester::where('is_active', true)->first();

        if (! $activeSemester) {
            $this->info('Tidak ada semester aktif.');

            return self::SUCCESS;
        }

        $slots = DutySlot::where('semester_id', $activeSemester->id)
            ->whereDate('duty_date', '<', today()->toDateString())
            ->with('claims.submission')
            ->get();

        $count = 0;

        foreach ($slots as $slot) {
            foreach ($slot->claims as $claim) {
                // Skip claims already resolved (approved) or already failed.
                if (in_array($claim->status, [ClaimStatus::Approved, ClaimStatus::Failed], true)) {
                    continue;
                }

                // Skip if replacement duty has already been offered/exists.
                $hasReplacement = ReplacementDuty::where('original_claim_id', $claim->id)->exists();
                if ($hasReplacement) {
                    continue;
                }

                $submission = $claim->submission;

                // Skip if there is an active pending submission awaiting review.
                if ($submission && $submission->verify_status === VerifyStatus::Pending) {
                    continue;
                }

                $approved = $submission && $submission->verify_status === VerifyStatus::Approved;

                if (! $approved) {
                    $penalty->triggerFailureFlow($claim);
                    $this->info("Penalti diterapkan untuk claim ID: {$claim->id}");
                    $count++;
                }
            }
        }

        $this->info("Selesai cek harian. Total claim bolos diproses: {$count}");

        // Cek penalti kuota mingguan bagi siswa yang tidak mendaftar / kurang kuota
        $this->processUnfulfilledWeeklyDuties($activeSemester, $penalty);

        return self::SUCCESS;
    }

    /**
     * Terapkan penalti bagi siswa yang tidak mendaftar piket / tidak memenuhi kuota piket mingguan.
     */
    private function processUnfulfilledWeeklyDuties(Semester $activeSemester, PenaltyServiceInterface $penalty): void
    {
        $refDate = now()->isSunday() ? now() : now()->subWeek();

        $startOfWeek = $refDate->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $refDate->copy()->endOfWeek(Carbon::SUNDAY);

        $year = $startOfWeek->isoWeekYear;
        $weekNumber = $startOfWeek->isoWeek;
        $idempotencyKey = "unclaimed_week_{$year}_W{$weekNumber}";

        // 1. Cek apakah di minggu tersebut admin membuat slot piket. Jika tidak ada slot, skip.
        $hasSlotsInWeek = DutySlot::where('semester_id', $activeSemester->id)
            ->whereBetween('duty_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->exists();

        if (! $hasSlotsInWeek) {
            $this->info("Minggu {$year}-W{$weekNumber} tidak memiliki slot piket di sistem. Penalti kuota dilewati.");

            return;
        }

        // 2. Ambil semua profil siswa aktif
        $students = StudentProfile::whereHas('user', function ($q) {
            $q->where('role', UserRole::Siswa->value);
        })->get();

        $penaltyCount = 0;

        foreach ($students as $student) {
            // Guard A: Jika akun siswa baru dibuat setelah minggu tersebut berakhir, jangan dihukum.
            if ($student->created_at->gt($endOfWeek)) {
                continue;
            }

            // Guard B: Cek idempotency — apakah siswa sudah pernah dihukum untuk minggu ini?
            $alreadyPenalized = LifeLog::where('student_profile_id', $student->id)
                ->where('reason', $idempotencyKey)
                ->exists();

            if ($alreadyPenalized) {
                continue;
            }

            // Hitung berapa klaim sah (bukan Failed) yang dibuat siswa di minggu tersebut
            $claimsCount = DutyClaim::where('student_id', $student->id)
                ->whereHas('dutySlot', function ($q) use ($activeSemester, $startOfWeek, $endOfWeek) {
                    $q->where('semester_id', $activeSemester->id)
                        ->whereBetween('duty_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()]);
                })
                ->where('status', '!=', ClaimStatus::Failed)
                ->count();

            $requiredQuota = $student->isConvict()
                ? (int) (SystemConfig::get('convict_weekly_missions') ?? 3)
                : (int) (SystemConfig::get('citizen_weekly_missions') ?? 1);

            if ($claimsCount < $requiredQuota) {
                $penalty->reduceLives($student, $idempotencyKey);

                $this->info("Penalti kuota minggu {$year}-W{$weekNumber} diterapkan ke siswa ID: {$student->id} (Klaim: {$claimsCount}/{$requiredQuota})");
                $penaltyCount++;
            }
        }

        $this->info("Selesai cek kuota mingguan {$year}-W{$weekNumber}. Total siswa dihukum: {$penaltyCount}");
    }
}
