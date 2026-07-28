<?php

namespace App\Livewire;

use App\Enums\ClaimStatus;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\SystemConfig;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Hall of Fame')]
class Leaderboard extends Component
{
    #[Computed]
    public function entries(): Collection
    {
        return StudentProfile::with('user')
            ->withCount(['dutyClaims as piket_count' => fn ($q) => $q->where('status', ClaimStatus::Approved->value)])
            ->orderByLeaderboard()
            ->get();
    }

    #[Computed]
    public function myRank(): ?int
    {
        $profileId = auth()->user()?->studentProfile?->id;

        if (! $profileId) {
            return null;
        }

        $rank = $this->entries->search(fn (StudentProfile $p) => $p->id === $profileId);

        return $rank === false ? null : $rank + 1;
    }

    #[Computed]
    public function myProfile(): ?StudentProfile
    {
        $rank = $this->myRank;

        return $rank ? $this->entries[$rank - 1] : null;
    }

    #[Computed]
    public function convictVisible(): bool
    {
        return filter_var(SystemConfig::get('convict_status_visible'), FILTER_VALIDATE_BOOLEAN);
    }

    #[Computed]
    public function activeSemester(): ?Semester
    {
        return Semester::where('is_active', true)->first();
    }

    public function render()
    {
        return view('livewire.leaderboard');
    }
}
