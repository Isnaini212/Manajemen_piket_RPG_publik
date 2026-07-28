<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function verify(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function create(User $user, Submission $submission): bool
    {
        return $user->role === UserRole::Siswa
            && $submission->dutyClaim?->student?->user_id === $user->id;
    }
}
