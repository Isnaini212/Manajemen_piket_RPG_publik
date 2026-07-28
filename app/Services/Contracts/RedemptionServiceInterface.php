<?php

namespace App\Services\Contracts;

use App\Models\StudentProfile;

interface RedemptionServiceInterface
{
    /**
     * Evaluate a convict's redemption progress and either restore them to
     * Citizen or mark the redemption window as failed.
     */
    public function checkProgress(StudentProfile $profile): void;

    /**
     * Close the redemption window as failed (deadline passed).
     */
    public function markRedemptionFailed(StudentProfile $profile): void;
}
