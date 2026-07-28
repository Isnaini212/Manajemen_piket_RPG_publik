<?php

namespace App\Services\Contracts;

use App\Models\DutyClaim;

interface RewardServiceInterface
{
    /**
     * Grant XP and trigger achievement/redemption checks for an approved duty.
     */
    public function grantReward(DutyClaim $claim): void;
}
