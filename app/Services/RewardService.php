<?php

namespace App\Services;

use App\Enums\ClaimType;
use App\Models\DutyClaim;
use App\Models\SystemConfig;
use App\Services\Contracts\BadgeEngineInterface;
use App\Services\Contracts\RedemptionServiceInterface;
use App\Services\Contracts\RewardServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RewardService implements RewardServiceInterface
{
    public function __construct(
        private readonly BadgeEngineInterface $badgeEngine,
        private readonly RedemptionServiceInterface $redemptionService,
    ) {}

    /**
     * Reward an approved duty: grant XP, log it, evaluate badges, and — for a
     * punishment duty — advance the student's redemption progress.
     */
    public function grantReward(DutyClaim $claim): void
    {
        $profile = $claim->student;

        if (! $profile) {
            Log::error('grantReward: klaim tanpa student profile', ['claim_id' => $claim->id]);

            return;
        }

        try {
            DB::transaction(function () use ($claim, $profile): void {
                $isReplacement = \App\Models\Submission::where('duty_claim_id', $claim->id)
                    ->whereNotNull('replacement_id')
                    ->exists();

                $xpConfigKey = $isReplacement ? 'xp_per_replacement_mission' : 'xp_per_mission';
                $xpAmount = (int) (SystemConfig::get($xpConfigKey) ?? ($isReplacement ? 50 : 100));

                $profile->increment('xp', $xpAmount);

                $profile->xpLogs()->create([
                    'amount' => $xpAmount,
                    'reason' => $isReplacement ? 'piket_replacement_approved' : 'piket_approved',
                ]);

                // Evaluate achievements after the XP change.
                $this->badgeEngine->checkAll($profile->id, 'piket_approved');

                // A successful punishment duty counts toward redemption.
                if ($claim->claim_type === ClaimType::PUNISHMENT) {
                    $this->redemptionService->checkProgress($profile);
                }
            });

            Log::info('Reward diberikan untuk piket disetujui', [
                'claim_id' => $claim->id,
                'student_profile_id' => $profile->id,
                'claim_type' => $claim->claim_type?->value,
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal memberikan reward', [
                'claim_id' => $claim->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
