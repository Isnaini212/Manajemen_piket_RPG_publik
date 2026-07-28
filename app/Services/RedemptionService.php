<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Enums\ClaimType;
use App\Enums\StudentStatus;
use App\Models\DutyClaim;
use App\Models\Notification;
use App\Models\StatusChangeLog;
use App\Models\SystemConfig;
use App\Models\StudentProfile;
use App\Services\Contracts\RedemptionServiceInterface;
use App\Services\Contracts\StatusServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RedemptionService implements RedemptionServiceInterface
{
    public function __construct(
        private readonly StatusServiceInterface $statusService,
    ) {}

    /**
     * Evaluate a convict's redemption progress. A convict is restored to
     * Citizen once every punishment duty created since they became a convict
     * has been approved; if the redemption deadline passes first, the window
     * is marked as failed.
     *
     * Note: `duty_claims.student_id` references `student_profiles.id`, so the
     * query keys on `$profile->id` (not `$profile->user_id`).
     */
    public function checkProgress(StudentProfile $profile): void
    {
        if ($profile->status !== StudentStatus::CONVICT) {
            return;
        }

        $log = StatusChangeLog::query()
            ->where('student_profile_id', $profile->id)
            ->where('to_status', StudentStatus::CONVICT->value)
            ->whereNull('resolved_at')
            ->orderByDesc('created_at')
            ->first();

        if (! $log) {
            return;
        }

        // Redemption window expired -> penalise and stop.
        if ($log->redemption_deadline !== null && $log->redemption_deadline->isPast()) {
            $this->markRedemptionFailed($profile);

            return;
        }

        // Target misi wajib: hitung minggu yang diberikan dari selisih
        // created_at log (saat jatuh CONVICT) vs redemption_deadline yang
        // sudah terkunci di DB \u2014 bukan dari config yang bisa berubah kapan saja.
        $weeklyMissions = (int) (SystemConfig::get('convict_weekly_missions') ?? 3);

        $convictSince = $log->created_at;
        $deadline     = $log->redemption_deadline;

        if ($convictSince && $deadline && $deadline->gt($convictSince)) {
            $weeksGiven = (int) max(1, round($convictSince->floatDiffInWeeks($deadline)));
        } else {
            $weeksGiven = (int) (SystemConfig::get('redemption_period_weeks') ?? 1);
        }

        $target = $weeklyMissions * $weeksGiven;

        // Hitung misi hukuman yang sudah disetujui sejak menjadi CONVICT.
        $completed = DutyClaim::query()
            ->where('student_id', $profile->id)
            ->where('claim_type', ClaimType::PUNISHMENT->value)
            ->where('status', ClaimStatus::Approved->value)
            ->when(
                $profile->status_since !== null,
                fn ($query) => $query->where('created_at', '>=', $profile->status_since),
            )
            ->count();

        Log::info('RedemptionService progress', [
            'student_profile_id' => $profile->id,
            'target'             => $target,
            'completed'          => $completed,
            'deadline'           => optional($log->redemption_deadline)->toDateTimeString(),
        ]);

        if ($completed >= $target) {
            $this->statusService->changeBackToCitizen($profile);
        }
    }

    /**
     * Close the active redemption log as failed and notify the student.
     */
    public function markRedemptionFailed(StudentProfile $profile): void
    {
        try {
            DB::transaction(function () use ($profile): void {
                StatusChangeLog::query()
                    ->where('student_profile_id', $profile->id)
                    ->where('to_status', StudentStatus::CONVICT->value)
                    ->whereNull('resolved_at')
                    ->update(['resolved_at' => now()]);

                Notification::create([
                    'user_id' => $profile->user_id,
                    'type' => 'redemption_failed',
                    'message' => 'Waktu penebusan habis. Status Convict akan berlanjut hingga reset semester.',
                ]);
            });

            Log::warning('Redemption gagal (deadline terlewat)', [
                'student_profile_id' => $profile->id,
                'user_id' => $profile->user_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal memproses redemption failed', [
                'student_profile_id' => $profile->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
