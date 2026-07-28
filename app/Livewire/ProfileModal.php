<?php

namespace App\Livewire;

use App\Enums\ClaimStatus;
use App\Models\Badge;
use App\Models\DutyClaim;
use App\Models\StudentBadge;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ProfileModal extends Component
{
    public ?int $userId = null;
    public bool $show = false;

    #[On('openProfileModal')]
    public function openModal(int $userId): void
    {
        $this->userId = $userId;
        $this->show = true;
    }

    public function closeModal(): void
    {
        $this->show = false;
        $this->userId = null;
    }

    #[Computed]
    public function profile(): ?StudentProfile
    {
        if (! $this->userId) {
            return null;
        }
        return StudentProfile::with(['user', 'studentBadges.badge'])
            ->where('user_id', $this->userId)
            ->first();
    }

    #[Computed]
    public function user(): ?User
    {
        return $this->profile?->user;
    }

    #[Computed]
    public function level(): int
    {
        return intdiv((int) ($this->profile?->xp ?? 0), 500) + 1;
    }

    #[Computed]
    public function ownedBadges(): Collection
    {
        $profile = $this->profile;
        if (! $profile) {
            return collect();
        }
        $ownedIds = StudentBadge::where('student_profile_id', $profile->id)->pluck('badge_id')->all();
        return Badge::whereIn('id', $ownedIds)->orderBy('name')->get();
    }

    #[Computed]
    public function leaderboardRank(): ?int
    {
        $profile = $this->profile;
        if (! $profile) {
            return null;
        }
        $leaderboard = StudentProfile::orderByLeaderboard()
            ->limit(10)
            ->pluck('id')
            ->values();

        $rank = $leaderboard->search($profile->id);
        return $rank !== false ? $rank + 1 : null;
    }

    #[Computed]
    public function stats(): array
    {
        $profile = $this->profile;
        if (! $profile) {
            return ['total_piket' => 0, 'total_xp' => 0];
        }

        return [
            'total_piket' => DutyClaim::where('student_id', $profile->id)
                ->where('status', ClaimStatus::Approved->value)
                ->count(),
            'total_xp' => (int) $profile->xp,
        ];
    }

    #[Computed]
    public function statusVisible(): bool
    {
        return filter_var(\App\Models\SystemConfig::get('convict_status_visible', true), FILTER_VALIDATE_BOOLEAN);
    }

    public function render()
    {
        return view('livewire.profile-modal');
    }
}
