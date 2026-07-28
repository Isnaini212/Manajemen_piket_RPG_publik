<?php

namespace App\Livewire\Student;

use App\Models\Badge;
use App\Models\StudentBadge;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Katalog Badge')]
class Badges extends Component
{
    #[Computed]
    public function profile(): ?StudentProfile
    {
        return StudentProfile::where('user_id', auth()->id())->first();
    }

    #[Computed]
    public function badges(): Collection
    {
        $profile = $this->profile;

        $ownedBadges = $profile
            ? StudentBadge::where('student_profile_id', $profile->id)
                ->get()
                ->keyBy('badge_id')
            : collect();

        return Badge::orderBy('name')->get()->map(function (Badge $badge) use ($ownedBadges) {
            $studentBadge = $ownedBadges->get($badge->id);
            $badge->owned = $studentBadge !== null;
            $badge->earned_at = $studentBadge?->created_at;

            return $badge;
        });
    }

    public function render()
    {
        $allBadges = $this->badges;
        $ownedBadges = $allBadges->where('owned', true);
        $lockedBadges = $allBadges->where('owned', false);

        return view('livewire.student.badges', [
            'ownedBadges' => $ownedBadges,
            'lockedBadges' => $lockedBadges,
            'totalCount' => $allBadges->count(),
            'ownedCount' => $ownedBadges->count(),
        ]);
    }
}
