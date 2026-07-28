<?php

namespace App\Livewire\Admin;

use App\Enums\ClaimStatus;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Enums\VerifyStatus;
use App\Models\DutyClaim;
use App\Models\DutySlot;
use App\Models\Semester;
use App\Models\StatusChangeLog;
use App\Models\StudentProfile;
use App\Models\Submission;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Dashboard Admin')]
class AdminDashboard extends Component
{
    #[Computed]
    public function totalSiswa(): int
    {
        return User::where('role', UserRole::Siswa)->count();
    }

    #[Computed]
    public function totalCitizen(): int
    {
        return StudentProfile::where('status', StudentStatus::CITIZEN->value)->count();
    }

    #[Computed]
    public function totalConvict(): int
    {
        return StudentProfile::where('status', StudentStatus::CONVICT->value)->count();
    }

    #[Computed]
    public function pendingSubmissions(): int
    {
        return Submission::where('verify_status', VerifyStatus::Pending->value)
            ->whereDate('uploaded_at', today())
            ->count();
    }

    #[Computed]
    public function weeklyCompleted(): int
    {
        [$start, $end] = $this->weekRange();

        return DutyClaim::where('status', ClaimStatus::Approved->value)
            ->whereHas('dutySlot', fn ($q) => $q->whereBetween('duty_date', [$start->toDateString(), $end->toDateString()]))
            ->count();
    }

    #[Computed]
    public function weeklyTarget(): int
    {
        [$start, $end] = $this->weekRange();

        return (int) DutySlot::whereBetween('duty_date', [$start->toDateString(), $end->toDateString()])->sum('quota');
    }

    #[Computed]
    public function leaderboard(): Collection
    {
        return StudentProfile::with('user')->orderByLeaderboard()->limit(5)->get();
    }

    #[Computed]
    public function recentSwaps(): Collection
    {
        return SwapRequest::with(['fromClaim.student.user', 'toStudent.user', 'fromClaim.dutySlot'])
            ->latest()
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function recentStatusChanges(): Collection
    {
        return StatusChangeLog::with('studentProfile.user')
            ->latest()
            ->limit(5)
            ->get();
    }

    public function runCheckMissed(): void
    {
        \Illuminate\Support\Facades\Artisan::call('piket:check-missed');
        \Illuminate\Support\Facades\Artisan::call('piket:check-replacement-expiry');
        $this->dispatch('notify', message: 'Pengecekan otomatis (bolos & kadaluarsa) berhasil dijalankan!', type: 'success');
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function weekRange(): array
    {
        return [now()->startOfWeek(Carbon::MONDAY), now()->endOfWeek(Carbon::SUNDAY)];
    }

    public function render()
    {
        return view('livewire.admin.admin-dashboard');
    }
}
