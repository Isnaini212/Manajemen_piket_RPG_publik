<?php

namespace App\Livewire\Student;

use App\Enums\ClaimStatus;
use App\Enums\ClaimType;
use App\Models\DutyClaim;
use App\Models\DutySlot;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\SystemConfig;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Misi')]
class MissionList extends Component
{
    public string $selectedWeek = '';

    public string $filter = 'all';

    /** ID claim yang sedang dilihat di modal bukti. Null = modal tertutup. */
    public ?int $viewingClaimId = null;

    public function mount(): void
    {
        // ISO year-week (o-W handles year boundaries correctly).
        $this->selectedWeek = now()->format('o-W');
    }

    #[On('proof-uploaded')]
    public function refreshData(): void
    {
        // Auto-close modal dan refresh grid setelah upload berhasil.
        $this->viewingClaimId = null;
        unset($this->slots, $this->claimedThisWeek);
    }

    public function openProofModal(int $claimId): void
    {
        $profile = $this->profile;
        if (! $profile) {
            return;
        }

        // Pastikan claim memang milik siswa ini.
        $valid = DutyClaim::where('id', $claimId)
            ->where('student_id', $profile->id)
            ->exists();

        if (! $valid) {
            return;
        }

        $this->viewingClaimId = $claimId;
    }

    public function closeProofModal(): void
    {
        $this->viewingClaimId = null;
        unset($this->slots, $this->claimedThisWeek);
    }

    #[Computed]
    public function profile(): ?StudentProfile
    {
        return StudentProfile::where('user_id', auth()->id())->first();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function weekBounds(): array
    {
        [$year, $week] = array_map('intval', explode('-', $this->selectedWeek));
        $ref = Carbon::now()->setISODate($year, $week);

        return [$ref->copy()->startOfWeek(Carbon::MONDAY), $ref->copy()->endOfWeek(Carbon::SUNDAY)];
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
    public function slots(): Collection
    {
        $semesterId = Semester::where('is_active', true)->value('id');

        if (! $semesterId) {
            return collect();
        }

        if ($this->profile) {
            $this->checkAndTriggerMissedDuties($this->profile->id);
        }

        [$start, $end] = $this->weekBounds();
        $profileId = $this->profile?->id;

        $slots = DutySlot::where('semester_id', $semesterId)
            ->whereBetween('duty_date', [$start->toDateString(), $end->toDateString()])
            ->with(['claims.submission'])
            ->orderBy('duty_date')
            ->get();

        // Decorate each slot with computed helpers.
        $slots->each(function (DutySlot $slot) use ($profileId): void {
            $taken = $slot->claims->whereNotIn('status', [ClaimStatus::Failed])->count();
            $slot->remaining_quota = max(0, $slot->quota - $taken);
            $slot->user_claim = $profileId
                ? $slot->claims->firstWhere('student_id', $profileId)
                : null;
        });

        return match ($this->filter) {
            'available' => $slots->filter(fn (DutySlot $s) => $s->remaining_quota > 0 && ! $s->user_claim)->values(),
            'mine' => $slots->filter(fn (DutySlot $s) => (bool) $s->user_claim)->values(),
            default => $slots,
        };
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
    public function claimedThisWeek(): int
    {
        $profileId = $this->profile?->id;

        if (! $profileId) {
            return 0;
        }

        [$start, $end] = $this->weekBounds();

        return DutyClaim::where('student_id', $profileId)
            ->whereHas('dutySlot', fn ($q) => $q->whereBetween('duty_date', [$start->toDateString(), $end->toDateString()]))
            ->count();
    }

    #[Computed]
    public function weekLabel(): string
    {
        [$start, $end] = $this->weekBounds();

        return $start->locale('id')->translatedFormat('d M') . ' – ' . $end->locale('id')->translatedFormat('d M Y');
    }

    #[Computed]
    public function weekOptions(): array
    {
        $semester = Semester::where('is_active', true)->first();
        if (! $semester || ! $semester->start_date || ! $semester->end_date) {
            // Fallback: 10 weeks around the current week
            $options = [];
            $current = now()->startOfWeek(\Illuminate\Support\Carbon::MONDAY)->subWeeks(4);
            for ($i = 0; $i < 10; $i++) {
                $start = $current->copy()->addWeeks($i);
                $end = $start->copy()->endOfWeek(\Illuminate\Support\Carbon::SUNDAY);
                $val = $start->format('o-W');
                $options[$val] = "Minggu " . ($i + 1) . " (" . $start->locale('id')->translatedFormat('d M') . ' - ' . $end->locale('id')->translatedFormat('d M Y') . ")";
            }
            return $options;
        }

        $options = [];
        $start = \Illuminate\Support\Carbon::parse($semester->start_date)->startOfWeek(\Illuminate\Support\Carbon::MONDAY);
        $end = \Illuminate\Support\Carbon::parse($semester->end_date)->endOfWeek(\Illuminate\Support\Carbon::SUNDAY);

        $current = $start->copy();
        $weekNum = 1;
        while ($current->lte($end)) {
            $wStart = $current->copy();
            $wEnd = $current->copy()->endOfWeek(\Illuminate\Support\Carbon::SUNDAY);
            $val = $current->format('o-W');

            $options[$val] = "Minggu " . $weekNum . " (" . $wStart->locale('id')->translatedFormat('d M') . ' - ' . $wEnd->locale('id')->translatedFormat('d M Y') . ")";

            $current->addWeek();
            $weekNum++;
        }

        return $options;
    }

    public function previousWeek(): void
    {
        [$start] = $this->weekBounds();
        $this->selectedWeek = $start->copy()->subWeek()->format('o-W');
    }

    public function nextWeek(): void
    {
        [$start] = $this->weekBounds();
        $this->selectedWeek = $start->copy()->addWeek()->format('o-W');
    }

    public function claimSlot(int $slotId): void
    {
        $profile = $this->profile;

        if (! $profile) {
            $this->dispatch('notify', message: 'Profil siswa tidak ditemukan.', type: 'error');

            return;
        }

        $semesterId = Semester::where('is_active', true)->value('id');
        $slot = DutySlot::with('claims')->find($slotId);

        if (! $slot || $slot->semester_id !== $semesterId) {
            $this->dispatch('notify', message: 'Slot tidak berada di semester aktif.', type: 'error');

            return;
        }

        // Hanya blokir hari yang SUDAH LEWAT (kemarin ke belakang), hari ini tetap bisa diklaim
        if ($slot->duty_date && $slot->duty_date->copy()->startOfDay()->lt(today())) {
            $this->dispatch('notify', message: 'Tidak bisa mengklaim jadwal yang sudah lewat.', type: 'error');

            return;
        }

        if (! $slot->isQuotaAvailable()) {
            $this->dispatch('notify', message: 'Kuota slot sudah penuh.', type: 'error');

            return;
        }

        $alreadyClaimed = DutyClaim::where('duty_slot_id', $slot->id)
            ->where('student_id', $profile->id)
            ->exists();

        if ($alreadyClaimed) {
            $this->dispatch('notify', message: 'Kamu sudah mengklaim slot ini.', type: 'error');

            return;
        }

        // Tidak ada batas atas klaim — siswa bebas mengambil jadwal tambahan (XP Farming).
        // Kuota mingguan hanya berfungsi sebagai indikator kewajiban, bukan pengunci.

        // Hard delete any existing soft-deleted claims for this student on this slot to prevent unique key constraint crashes
        DutyClaim::onlyTrashed()
            ->where('duty_slot_id', $slot->id)
            ->where('student_id', $profile->id)
            ->forceDelete();

        DutyClaim::create([
            'duty_slot_id' => $slot->id,
            'student_id' => $profile->id,
            'claim_type' => $profile->isConvict() ? ClaimType::PUNISHMENT : ClaimType::REGULAR,
            'status' => ClaimStatus::Pending,
        ]);

        // Clear computed caches so the grid reflects the new claim.
        unset($this->slots, $this->claimedThisWeek);

        $this->dispatch('slot-claimed');
        $this->dispatch('notify', message: 'Misi berhasil diklaim!', type: 'success');
    }

    public function cancelClaim(int $claimId): void
    {
        $profile = $this->profile;
        if (! $profile) {
            $this->dispatch('notify', message: 'Profil siswa tidak ditemukan.', type: 'error');
            return;
        }

        $claim = DutyClaim::with('dutySlot')->find($claimId);

        if (! $claim || $claim->student_id !== $profile->id) {
            $this->dispatch('notify', message: 'Misi tidak ditemukan atau kamu tidak memiliki akses.', type: 'error');
            return;
        }

        if ($claim->dutySlot && $claim->dutySlot->duty_date->lte(today())) {
            $this->dispatch('notify', message: 'Tidak dapat membatalkan misi untuk hari ini atau hari yang sudah berlalu.', type: 'error');
            return;
        }

        $claim->forceDelete();

        unset($this->slots, $this->claimedThisWeek);
        $this->dispatch('notify', message: 'Misi berhasil dibatalkan!', type: 'success');
    }

    public function render()
    {
        return view('livewire.student.mission-list');
    }
}
