<?php

namespace App\Services\Contracts;

use App\Models\DutyClaim;
use App\Models\ReplacementDuty;
use App\Models\StudentProfile;

interface PenaltyServiceInterface
{
    /**
     * Entry point when a duty fails (final rejection or missed deadline).
     */
    public function triggerFailureFlow(DutyClaim $claim, bool $isFinalRejection = false): void;

    /**
     * Offer a replacement duty instead of immediately losing a life.
     */
    public function offerReplacement(DutyClaim $claim): void;

    /**
     * Deduct a life and escalate to convict status if it reaches zero.
     */
    public function reduceLives(StudentProfile $profile, string $reason = 'piket_failed'): void;

    /**
     * Handle a replacement duty that expired without completion.
     */
    public function handleReplacementExpired(ReplacementDuty $replacement): void;
}
