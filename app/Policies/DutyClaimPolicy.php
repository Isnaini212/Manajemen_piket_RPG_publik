<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\DutyClaim;
use App\Models\User;

class DutyClaimPolicy
{
    public function create(User $user): bool
    {
        return $user->role === UserRole::Siswa;
    }

    public function view(User $user, DutyClaim $claim): bool
    {
        return $user->id === $claim->student?->user_id;
    }
}
