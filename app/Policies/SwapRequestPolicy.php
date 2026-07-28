<?php

namespace App\Policies;

use App\Models\DutyClaim;
use App\Models\SwapRequest;
use App\Models\User;

class SwapRequestPolicy
{
    /**
     * A student may open a swap only from a claim they own.
     */
    public function create(User $user, DutyClaim $claim): bool
    {
        return $user->id === $claim->student?->user_id;
    }

    /**
     * Only the targeted student may respond to a swap request.
     *
     * Note: swap_requests.to_student_id references student_profiles.id, so we
     * compare against the user's profile id (not the user id).
     */
    public function respond(User $user, SwapRequest $swapRequest): bool
    {
        return $swapRequest->to_student_id === $user->studentProfile?->id;
    }
}
