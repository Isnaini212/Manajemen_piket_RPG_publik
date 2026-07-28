<?php

namespace App\Livewire\Student;

use App\Enums\ClaimStatus;
use App\Enums\ClaimType;
use App\Enums\ReplacementStatus;
use App\Enums\StudentStatus;
use App\Models\DutyClaim;
use App\Models\ReplacementDuty;
use App\Models\Semester;
use App\Models\StatusChangeLog;
use App\Models\StudentBadge;
use App\Models\StudentProfile;
use App\Models\SystemConfig;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    /**
     * Refresh computed data whenever a child component reports a change.
     */
    #[On('proof-uploaded')]
    #[On('slot-claimed')]
    public function refreshData(): void
    {
        // Empty: the round-trip clears computed caches and re-renders.
    }

    #[Computed]
    public function profile(): ?StudentProfile
    {
        return StudentProfile::with('user')
            ->where('user_id', auth()->id())
            ->first();
    }

    private function checkAndTriggerMissedDuties(int $profileId): void
    {
        $claims = DutyClaim::where('student_id', $profileId)
            ->whereNotIn('status', [ClaimStatus::Approved, ClaimStatus::Failed])
            ->whereHas('dutySlot', function ($query) {
                $query->whereDate('duty_date', '<', today()->toDateString());
            })
            ->with(['dutySlot', 'submission'])
            ->get();

        $penalty = app(\App\Services\Contracts\PenaltyServiceInterface::class);

        foreach ($claims as $claim) {
            $hasReplacement = \App\Models\ReplacementDuty::where('original_claim_id', $claim->id)->exists();
            if ($hasReplacement) {
                continue;
            }

            $submission = $claim->submission;
            if ($submission && $submission->verify_status === \App\Enums\VerifyStatus::Pending) {
                continue;
            }

            $approved = $submission && $submission->verify_status === \App\Enums\VerifyStatus::Approved;

            if (! $approved) {
                $penalty->triggerFailureFlow($claim);
            }
        }
    }

    #[Computed]
    public function weeklyMissions(): Collection
    {
        $profile = $this->profile;

        if (! $profile) {
            return collect();
        }

        $this->checkAndTriggerMissedDuties($profile->id);

        [$start, $end] = $this->weekRange();
        $semesterId = $this->activeSemesterId();

        return DutyClaim::where('student_id', $profile->id)
            ->whereHas('dutySlot', fn ($q) => $q
                ->when($semesterId, fn ($qq) => $qq->where('semester_id', $semesterId))
                ->whereBetween('duty_date', [$start->toDateString(), $end->toDateString()]))
            ->with(['dutySlot', 'submission'])
            ->get()
            ->sortBy(fn (DutyClaim $c) => $c->dutySlot?->duty_date)
            ->values();
    }

    #[Computed]
    public function weeklyQuota(): int
    {
        $profile = $this->profile;

        return $profile && $profile->isConvict()
            ? (int) (SystemConfig::get('convict_weekly_missions') ?? 3)
            : (int) (SystemConfig::get('citizen_weekly_missions') ?? 1);
    }

    #[Computed]
    public function replacementDuty(): ?ReplacementDuty
    {
        $profile = $this->profile;

        if (! $profile) {
            return null;
        }

        return ReplacementDuty::where('status', ReplacementStatus::OFFERED->value)
            ->whereHas('originalClaim', fn ($q) => $q->where('student_id', $profile->id))
            ->with('originalClaim.dutySlot')
            ->latest()
            ->first();
    }

    #[Computed]
    public function leaderboard(): Collection
    {
        return StudentProfile::with('user')
            ->orderByLeaderboard()
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function recentBadges(): Collection
    {
        $profile = $this->profile;

        if (! $profile) {
            return collect();
        }

        return StudentBadge::where('student_profile_id', $profile->id)
            ->with('badge')
            ->latest('earned_at')
            ->limit(3)
            ->get();
    }

    /**
     * @return array{completed: int, total: int, deadline: ?Carbon}|null
     */
    #[Computed]
    public function convictProgress(): ?array
    {
        $profile = $this->profile;

        if (! $profile || ! $profile->isConvict()) {
            return null;
        }

        $log = StatusChangeLog::where('student_profile_id', $profile->id)
            ->where('to_status', StudentStatus::CONVICT->value)
            ->whereNull('resolved_at')
            ->latest('created_at')
            ->first();

        // Hitung berapa minggu yang diberikan saat siswa jatuh CONVICT
        // dengan membaca selisih antara status_since dan redemption_deadline
        // yang sudah terkunci di DB — bukan dari config yang bisa berubah.
        $weeklyMissions = (int) (SystemConfig::get('convict_weekly_missions') ?? 3);

        $statusSince = $profile->status_since;
        $deadline    = $log?->redemption_deadline;

        if ($statusSince && $deadline && $deadline->gt($statusSince)) {
            $weeksGiven = (int) max(1, round($statusSince->floatDiffInWeeks($deadline)));
        } else {
            // Fallback: pakai config saat ini jika data tidak lengkap
            $weeksGiven = (int) (SystemConfig::get('redemption_period_weeks') ?? 1);
        }

        $total = $weeklyMissions * $weeksGiven;

        $approvedCount = DutyClaim::where('student_id', $profile->id)
            ->where('claim_type', ClaimType::PUNISHMENT->value)
            ->where('status', ClaimStatus::Approved->value)
            ->when($profile->status_since, fn ($q) => $q->where('created_at', '>=', $profile->status_since))
            ->count();

        return [
            'completed' => $approvedCount,
            'total'     => $total,
            'deadline'  => $deadline,
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function weekRange(): array
    {
        return [now()->startOfWeek(Carbon::MONDAY), now()->endOfWeek(Carbon::SUNDAY)];
    }

    private function activeSemesterId(): ?int
    {
        return Semester::where('is_active', true)->value('id');
    }

    #[Computed]
    public function xpLifeBuyCost(): int
    {
        return (int) (SystemConfig::get('xp_per_life_buy') ?? 150);
    }

    #[Computed]
    public function livesMax(): int
    {
        return (int) (SystemConfig::get('lives_max') ?? 3);
    }

    public function buyLifeWithXp(): void
    {
        $profile = $this->profile;

        if (! $profile) {
            $this->dispatch('notify', message: 'Profil tidak ditemukan.', type: 'error');

            return;
        }

        if ($profile->isConvict()) {
            $this->dispatch('notify', message: 'Siswa berstatus CONVICT tidak dapat menukar XP untuk nyawa.', type: 'error');

            return;
        }

        $cost    = $this->xpLifeBuyCost;
        $maxLives = $this->livesMax;

        if ($profile->lives >= $maxLives) {
            $this->dispatch('notify', message: 'Nyawa kamu sudah maksimal!', type: 'error');

            return;
        }

        if ($profile->xp < $cost) {
            $this->dispatch('notify', message: "XP kamu tidak cukup. Butuh {$cost} XP untuk memulihkan 1 nyawa.", type: 'error');

            return;
        }

        DB::transaction(function () use ($profile, $cost): void {
            $profile->decrement('xp', $cost);
            $profile->xpLogs()->create([
                'amount' => -$cost,
                'reason' => 'tukar_xp_nyawa',
            ]);
            $profile->increaseLivesPartially(1, 'tukar_xp_nyawa');
        });

        unset($this->profile);
        $this->dispatch('notify', message: "Berhasil! Nyawa bertambah +1. (-{$cost} XP)", type: 'success');
    }

    public function render()
    {
        return view('livewire.student.dashboard');
    }
}
