<?php

namespace App\Services;

use App\Enums\ReplacementStatus;
use App\Models\DutyClaim;
use App\Models\Notification;
use App\Models\ReplacementDuty;
use App\Models\StudentProfile;
use App\Models\SystemConfig;
use App\Services\Contracts\PenaltyServiceInterface;
use App\Services\Contracts\StatusServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PenaltyService implements PenaltyServiceInterface
{
    public function __construct(
        private readonly StatusServiceInterface $statusService,
    ) {}

    /**
     * Entry point for a failed duty: either offer a replacement (if enabled)
     * or immediately deduct a life.
     */
    public function triggerFailureFlow(DutyClaim $claim, bool $isFinalRejection = false): void
    {
        $profile = $claim->student;

        if (! $profile) {
            Log::error('triggerFailureFlow: klaim tanpa student profile', ['claim_id' => $claim->id]);

            return;
        }

        // Mark any existing offered replacement as EXPIRED so it doesn't linger on dashboard
        ReplacementDuty::where('original_claim_id', $claim->id)
            ->where('status', \App\Enums\ReplacementStatus::OFFERED)
            ->update(['status' => \App\Enums\ReplacementStatus::EXPIRED]);

        // If this is a final rejection, skip replacement and directly deduct lives.
        if ($isFinalRejection) {
            $claim->update(['status' => \App\Enums\ClaimStatus::Failed]);
            $this->reduceLives($profile, 'piket_rejected_final');

            return;
        }

        // If a replacement duty was already offered for this claim, the student
        // failed that too — deduct lives instead of offering another replacement.
        $hasExistingReplacement = ReplacementDuty::where('original_claim_id', $claim->id)->exists();
        if ($hasExistingReplacement) {
            $claim->update(['status' => \App\Enums\ClaimStatus::Failed]);
            $this->reduceLives($profile, 'replacement_failed');

            return;
        }

        $replacementEnabled = filter_var(
            SystemConfig::get('replacement_duty_enabled'),
            FILTER_VALIDATE_BOOLEAN,
        );

        if ($replacementEnabled) {
            $this->offerReplacement($claim);

            return;
        }

        $claim->update(['status' => \App\Enums\ClaimStatus::Failed]);
        $this->reduceLives($profile, 'piket_failed');
    }

    /**
     * Create a replacement-duty offer for a failed claim and notify the
     * student. Skips creation if an offer already exists for this claim.
     */
    public function offerReplacement(DutyClaim $claim): void
    {
        if (ReplacementDuty::where('original_claim_id', $claim->id)->exists()) {
            Log::info('offerReplacement: replacement sudah ada, dilewati', ['claim_id' => $claim->id]);

            return;
        }

        try {
            DB::transaction(function () use ($claim): void {
                $days = (int) (SystemConfig::get('replacement_duty_days') ?? 3);
                
                $hasSubmission = $claim->submission()->exists();
                
                // Jika pernah upload (berarti ditolak admin hari ini), hitung dari sekarang.
                // Jika tidak pernah upload (bolos), hitung mutlak dari tanggal piket asli.
                if ($hasSubmission) {
                    $deadline = now()->addDays($days)->endOfDay();
                } else {
                    $dutyDate = \Illuminate\Support\Carbon::parse($claim->dutySlot->duty_date);
                    $deadline = $dutyDate->copy()->addDays($days)->endOfDay();
                }

                // Jika batas waktu ternyata sudah lewat di masa lalu
                if ($deadline->isPast()) {
                    ReplacementDuty::create([
                        'original_claim_id' => $claim->id,
                        'deadline' => $deadline,
                        'status' => ReplacementStatus::EXPIRED,
                    ]);
                    
                    $claim->update(['status' => \App\Enums\ClaimStatus::Failed]);
                    $this->reduceLives($claim->student, 'replacement_expired');
                    
                    Log::info('Replacement duty langsung expired karena deadline dari hari piket telah lewat', [
                        'claim_id' => $claim->id,
                        'student_profile_id' => $claim->student_id,
                    ]);
                    
                    return;
                }

                ReplacementDuty::create([
                    'original_claim_id' => $claim->id,
                    'deadline' => $deadline,
                    'status' => ReplacementStatus::OFFERED,
                ]);

                Notification::create([
                    'user_id' => $claim->student->user_id,
                    'type' => 'replacement_offered',
                    'message' => 'Kamu mendapat kesempatan piket pengganti. Selesaikan sebelum '
                        . $deadline->format('d M Y') . '.',
                ]);
            });

            Log::info('Replacement duty ditawarkan', [
                'claim_id' => $claim->id,
                'student_profile_id' => $claim->student_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal menawarkan replacement duty', [
                'claim_id' => $claim->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Deduct one life (floored at 0), log it, and escalate to Convict when
     * lives hit zero.
     */
    public function reduceLives(StudentProfile $profile, string $reason = 'piket_failed'): void
    {
        try {
            DB::transaction(function () use ($profile, $reason): void {
                $livesDeduction = (int) (SystemConfig::get('lives_penalty') ?? 1);
                $xpDeduction = (int) (SystemConfig::get('xp_penalty') ?? 0);

                $newLives = max(0, $profile->lives - $livesDeduction);
                $newXp = max(0, $profile->xp - $xpDeduction);

                $profile->update([
                    'lives' => $newLives,
                    'xp' => $newXp,
                ]);

                $profile->lifeLogs()->create([
                    'change' => -$livesDeduction,
                    'reason' => $reason,
                ]);

                if ($xpDeduction > 0) {
                    $profile->xpLogs()->create([
                        'amount' => -$xpDeduction,
                        'reason' => $reason,
                    ]);
                }

                if ($newLives === 0) {
                    $this->statusService->changeToConvict($profile);
                }

                $reasonMessage = match (true) {
                    str_contains($reason, 'unclaimed_week_') => 'karena tidak memenuhi kuota piket mingguan.',
                    $reason === 'piket_rejected_final' => 'karena penyerahan bukti piket ditolak.',
                    $reason === 'replacement_failed' => 'karena gagal menyelesaikan piket pengganti.',
                    $reason === 'replacement_expired' => 'karena batas waktu piket pengganti telah habis.',
                    $reason === 'piket_failed' => 'karena tidak menyelesaikan tugas piket.',
                    default => "karena alokasi sistem ({$reason}).",
                };

                Notification::create([
                    'user_id' => $profile->user_id,
                    'type' => 'life_deducted',
                    'message' => "Nyawamu berkurang {$livesDeduction} {$reasonMessage} Sisa nyawa saat ini: {$newLives}.",
                ]);
            });

            Log::info('Nyawa siswa berkurang', [
                'student_profile_id' => $profile->id,
                'reason' => $reason,
                'lives' => $profile->fresh()?->lives,
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal mengurangi nyawa', [
                'student_profile_id' => $profile->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Mark a replacement duty expired and deduct a life from its owner.
     */
    public function handleReplacementExpired(ReplacementDuty $replacement): void
    {
        try {
            $profile = $replacement->originalClaim?->student;

            if (! $profile) {
                Log::error('handleReplacementExpired: profile tidak ditemukan', [
                    'replacement_id' => $replacement->id,
                ]);

                return;
            }

            DB::transaction(function () use ($replacement, $profile): void {
                $replacement->update(['status' => ReplacementStatus::EXPIRED]);
                $replacement->originalClaim?->update(['status' => \App\Enums\ClaimStatus::Failed]);
                $this->reduceLives($profile, 'replacement_expired');
            });
        } catch (\Throwable $e) {
            Log::error('Gagal memproses replacement expired', [
                'replacement_id' => $replacement->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
