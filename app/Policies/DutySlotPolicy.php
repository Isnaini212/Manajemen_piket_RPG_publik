<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\DutySlot;
use App\Models\User;

class DutySlotPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, DutySlot $slot): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, DutySlot $slot): bool
    {
        return $user->role === UserRole::Admin;
    }
}
