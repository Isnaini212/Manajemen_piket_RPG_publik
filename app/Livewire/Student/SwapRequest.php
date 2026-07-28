<?php

namespace App\Livewire\Student;

use App\Enums\ClaimStatus;
use App\Enums\SwapStatus;
use App\Enums\UserRole;
use App\Models\DutyClaim;
use App\Models\Notification;
use App\Models\StudentProfile;
use App\Models\SwapRequest as SwapRequestModel;
use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Tukar Jadwal')]
class SwapRequest extends Component
{
    public string $activeTab = 'ajukan';

    public ?int $selectedClaimId = null; // Claim kita yang mau ditukar
    public ?int $selectedStudentId = null;
    public ?int $selectedTargetClaimId = null; // Claim siswa lain yang mau kita tukar

    #[Computed]
    public function profile(): ?StudentProfile
    {
        return StudentProfile::where('user_id', auth()->id())->first();
    }

    #[Computed]
    public function swapUsed(): int
    {
        $id = $this->profile?->id;

        return $id ? SwapRequestModel::countThisMonth($id) : 0;
    }

    #[Computed]
    public function swapMax(): int
    {
        return (int) (SystemConfig::get('swap_limit_per_month') ?? 2);
    }

    #[Computed]
    public function hasPendingSwap(): bool
    {
        $profileId = $this->profile?->id;
        if (!$profileId) return false;

        return SwapRequestModel::where('from_student_id', $profileId)
            ->where('status', SwapStatus::Pending)
            ->exists()
            ||
            SwapRequestModel::where('to_student_id', $profileId)
                ->where('status', SwapStatus::Pending)
                ->exists();
    }

    #[Computed]
    public function pendingSwapIds(): array
    {
        $profileId = $this->profile?->id;
        if (!$profileId) return [];

        $fromClaims = SwapRequestModel::where('from_student_id', $profileId)
            ->where('status', SwapStatus::Pending)
            ->pluck('from_claim_id')
            ->toArray();

        $toClaims = SwapRequestModel::where('to_student_id', $profileId)
            ->where('status', SwapStatus::Pending)
            ->pluck('to_claim_id')
            ->toArray();

        return array_merge($fromClaims, $toClaims);
    }

    /**
     * Claims from the current week that the student can still swap away
     * (claimed, i.e., still pending verification).
     */
    #[Computed]
    public function myActiveClaims(): Collection
    {
        $profileId = $this->profile?->id;

        if (! $profileId) {
            return collect();
        }

        [$start, $end] = $this->weekRange();

        return DutyClaim::where('student_id', $profileId)
            ->where('status', ClaimStatus::Pending)
            ->whereNotIn('id', $this->pendingSwapIds)
            ->whereDoesntHave('submission')
            ->whereHas('dutySlot', function ($q) use ($start, $end) {
                $q->whereBetween('duty_date', [$start->toDateString(), $end->toDateString()]);
            })
            ->with('dutySlot')
            ->get();
    }

    #[Computed]
    public function incomingRequests(): Collection
    {
        $profileId = $this->profile?->id;

        if (! $profileId) {
            return collect();
        }

        return SwapRequestModel::where('to_student_id', $profileId)
            ->where('status', SwapStatus::Pending)
            ->with(['fromClaim.dutySlot', 'fromStudent.user', 'toClaim.dutySlot'])
            ->latest()
            ->get();
    }

    #[Computed]
    public function myRequests(): Collection
    {
        $profileId = $this->profile?->id;

        if (! $profileId) {
            return collect();
        }

        return SwapRequestModel::where('from_student_id', $profileId)
            ->with(['fromClaim.dutySlot', 'toStudent.user', 'toClaim.dutySlot'])
            ->latest()
            ->get();
    }

    #[Computed]
    public function allHistory(): Collection
    {
        $profileId = $this->profile?->id;

        if (! $profileId) {
            return collect();
        }

        // Ambil semua request yang kita kirim dan yang kita terima
        $outgoing = SwapRequestModel::where('from_student_id', $profileId)
            ->with(['fromClaim.dutySlot', 'toStudent.user', 'fromStudent.user', 'toClaim.dutySlot'])
            ->get();

        $incoming = SwapRequestModel::where('to_student_id', $profileId)
            ->with(['fromClaim.dutySlot', 'toStudent.user', 'fromStudent.user', 'toClaim.dutySlot'])
            ->get();

        // Gabungkan, hapus duplikat (berdasarkan id), dan urutkan berdasarkan created_at
        return $outgoing->merge($incoming)
            ->unique('id')
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * Other students who have a claim in the same week as the selected claim,
     * and don't cause date conflict.
     */
    #[Computed]
    public function eligibleStudents(): Collection
    {
        if (! $this->selectedClaimId) {
            return collect();
        }

        $myClaim = DutyClaim::with('dutySlot')->find($this->selectedClaimId);
        $myDate = $myClaim?->dutySlot?->duty_date;

        if (! $myDate) {
            return collect();
        }

        $week = [
            Carbon::parse($myDate)->startOfWeek(Carbon::MONDAY)->toDateString(),
            Carbon::parse($myDate)->endOfWeek(Carbon::SUNDAY)->toDateString(),
        ];

        $myProfileId = $this->profile?->id;
        $myDateString = $myDate->toDateString();

        // Dapatkan semua claim saya di minggu ini untuk cek bentrok
        $myClaimsInWeek = DutyClaim::where('student_id', $myProfileId)
            ->where('status', ClaimStatus::Pending)
            ->whereHas('dutySlot', function ($q) use ($week) {
                $q->whereBetween('duty_date', $week);
            })
            ->with('dutySlot')
            ->get()
            ->keyBy(function ($claim) {
                return $claim->dutySlot->duty_date->toDateString();
            });

        // Dapatkan claim IDs yang sedang di-swap
        $pendingClaimIds = SwapRequestModel::where('status', SwapStatus::Pending)
            ->where(function ($query) {
                $query->where('from_student_id', $this->profile?->id)
                    ->orWhere('to_student_id', $this->profile?->id);
            })
            ->pluck('from_claim_id', 'to_claim_id')
            ->flatten()
            ->toArray();

        // Dapatkan siswa beserta klaimnya di minggu ini, tapi exclude yang punya claim di tanggal $myDateString
        $students = StudentProfile::with(['user', 'dutyClaims' => function ($q) use ($pendingClaimIds, $week, $myDateString) {
            $q->where('status', ClaimStatus::Pending)
                ->whereNotIn('id', $pendingClaimIds)
                ->whereDoesntHave('submission')
                ->whereHas('dutySlot', function ($s) use ($week) {
                    $s->whereBetween('duty_date', $week);
                })
                ->whereHas('dutySlot', function ($s) use ($myDateString) {
                    $s->where('duty_date', '!=', $myDateString);
                })
                ->with('dutySlot');
        }])
            ->where('id', '!=', $myProfileId)
            ->whereDoesntHave('dutyClaims', function ($q) use ($myDateString) {
                $q->where('status', ClaimStatus::Pending)
                    ->whereHas('dutySlot', function ($s) use ($myDateString) {
                        $s->where('duty_date', $myDateString);
                    });
            })
            ->whereHas('dutyClaims', function ($q) use ($pendingClaimIds, $week) {
                $q->where('status', ClaimStatus::Pending)
                    ->whereNotIn('id', $pendingClaimIds)
                    ->whereDoesntHave('submission')
                    ->whereHas('dutySlot', function ($s) use ($week) {
                        $s->whereBetween('duty_date', $week);
                    });
            })
            ->get();

        // Filter claims for each student that don't conflict with our claims
        return $students->map(function ($student) use ($myClaimsInWeek) {
            $student->dutyClaims = $student->dutyClaims->filter(function ($claim) use ($myClaimsInWeek) {
                $targetDate = $claim->dutySlot->duty_date->toDateString();
                return ! $myClaimsInWeek->has($targetDate);
            });
            return $student;
        })->filter(function ($student) {
            return $student->dutyClaims->isNotEmpty();
        })->values();
    }

    public function submitSwap(): void
    {
        if ($this->hasPendingSwap) {
            $this->dispatch('notify', message: 'Anda masih memiliki permintaan tukar jadwal yang pending. Selesaikan terlebih dahulu.', type: 'error');
            return;
        }

        $this->validate([
            'selectedClaimId' => ['required', 'integer'],
            'selectedStudentId' => ['required', 'integer'],
            'selectedTargetClaimId' => ['required', 'integer'],
        ]);

        $profile = $this->profile;
        $fromClaim = DutyClaim::with('dutySlot', 'submission')->find($this->selectedClaimId);
        $targetClaim = DutyClaim::with('dutySlot', 'submission')->find($this->selectedTargetClaimId);

        if (! $profile || ! $fromClaim || $fromClaim->student_id !== $profile->id) {
            $this->dispatch('notify', message: 'Klaim tidak valid.', type: 'error');
            return;
        }

        if (! $targetClaim || $targetClaim->student_id !== $this->selectedStudentId) {
            $this->dispatch('notify', message: 'Klaim target tidak valid.', type: 'error');
            return;
        }

        // Cek apakah claim atau targetClaim sudah punya submission
        if ($fromClaim->submission || $targetClaim->submission) {
            $this->dispatch('notify', message: 'Klaim yang sudah memiliki bukti tidak bisa ditukar.', type: 'error');
            return;
        }

        // Cek apakah claim atau targetClaim sedang di-swap
        $pendingSwap = SwapRequestModel::where(function ($query) use ($fromClaim, $targetClaim) {
            $query->where('from_claim_id', $fromClaim->id)
                ->orWhere('to_claim_id', $fromClaim->id)
                ->orWhere('from_claim_id', $targetClaim->id)
                ->orWhere('to_claim_id', $targetClaim->id);
        })->where('status', SwapStatus::Pending)->first();

        if ($pendingSwap) {
            $this->dispatch('notify', message: 'Klaim ini sedang dalam proses tukar jadwal.', type: 'error');
            return;
        }

        if ($this->swapUsed >= $this->swapMax) {
            $this->dispatch('notify', message: 'Limit tukar jadwal bulan ini sudah habis.', type: 'error');
            return;
        }

        $target = StudentProfile::with('user')->find($this->selectedStudentId);

        if (! $target || ! $target->user) {
            $this->dispatch('notify', message: 'Siswa tujuan tidak ditemukan.', type: 'error');
            return;
        }

        // Cek bentrok sebelum submit
        $targetDate = $targetClaim->dutySlot->duty_date->toDateString();
        $myClaimsInWeek = DutyClaim::where('student_id', $profile->id)
            ->where('status', ClaimStatus::Pending)
            ->whereHas('dutySlot', function ($q) use ($targetDate) {
                $q->where('duty_date', $targetDate);
            })
            ->exists();
        if ($myClaimsInWeek) {
            $this->dispatch('notify', message: 'Anda sudah punya jadwal di tanggal tersebut.', type: 'error');
            return;
        }

        DB::transaction(function () use ($fromClaim, $targetClaim, $target, $profile): void {
            SwapRequestModel::create([
                'from_student_id' => $profile->id,
                'from_claim_id' => $fromClaim->id,
                'to_claim_id' => $targetClaim->id,
                'to_student_id' => $target->id,
                'status' => SwapStatus::Pending,
            ]);

            Notification::create([
                'user_id' => $target->user->id,
                'type' => 'swap_request',
                'message' => ($profile->user?->name ?? 'Seorang siswa') . ' mengajukan tukar jadwal piket dengan kamu.',
            ]);
        });

        $this->reset('selectedClaimId', 'selectedStudentId', 'selectedTargetClaimId');
        unset($this->swapUsed, $this->myRequests, $this->hasPendingSwap, $this->pendingSwapIds);

        $this->dispatch('notify', message: 'Request tukar berhasil dikirim.', type: 'success');
    }

    public function cancelSwap(int $requestId): void
    {
        $profile = $this->profile;
        $swap = SwapRequestModel::find($requestId);

        if (! $profile || ! $swap || $swap->from_student_id !== $profile->id) {
            abort(403);
        }

        if ($swap->status !== SwapStatus::Pending) {
            $this->dispatch('notify', message: 'Request ini tidak bisa dibatalkan.', type: 'error');
            return;
        }

        $swap->update(['status' => SwapStatus::Cancelled, 'responded_at' => now()]);

        $this->dispatch('notify', message: 'Request tukar berhasil dibatalkan.', type: 'success');
        unset($this->myRequests, $this->hasPendingSwap, $this->pendingSwapIds);
    }

    public function respondRequest(int $requestId, string $decision): void
    {
        $profile = $this->profile;
        $swap = SwapRequestModel::with(['fromClaim.dutySlot', 'fromClaim.submission', 'fromStudent.user', 'toClaim.dutySlot', 'toClaim.submission'])->find($requestId);

        if (! $profile || ! $swap || $swap->to_student_id !== $profile->id) {
            abort(403);
        }

        if ($swap->status !== SwapStatus::Pending) {
            $this->dispatch('notify', message: 'Request ini sudah direspon.', type: 'error');
            return;
        }

        if (! in_array($decision, ['accepted', 'rejected'], true)) {
            return;
        }

        $fromClaim = $swap->fromClaim;
        $toClaim = $swap->toClaim;
        $fromUser = $fromClaim?->student?->user;
        $responderName = $profile->user?->name ?? 'Siswa';

        if ($decision === 'accepted') {
            // Cek apakah claim atau targetClaim sudah punya submission
            if ($fromClaim->submission || $toClaim->submission) {
                $this->dispatch('notify', message: 'Klaim yang sudah memiliki bukti tidak bisa ditukar.', type: 'error');
                return;
            }

            // Validasi bentrok sebelum accept
            // Cek apakah saya (penerima) punya claim di tanggal fromClaim
            $fromDate = $fromClaim->dutySlot->duty_date->toDateString();
            $myClaimInFromDate = DutyClaim::where('student_id', $profile->id)
                ->where('id', '!=', $toClaim->id)
                ->where('status', ClaimStatus::Pending)
                ->whereHas('dutySlot', function ($q) use ($fromDate) {
                    $q->where('duty_date', $fromDate);
                })
                ->exists();

            // Cek apakah pengirim punya claim di tanggal toClaim
            $toDate = $toClaim->dutySlot->duty_date->toDateString();
            $senderClaimInToDate = DutyClaim::where('student_id', $swap->from_student_id)
                ->where('id', '!=', $fromClaim->id)
                ->where('status', ClaimStatus::Pending)
                ->whereHas('dutySlot', function ($q) use ($toDate) {
                    $q->where('duty_date', $toDate);
                })
                ->exists();

            if ($myClaimInFromDate || $senderClaimInToDate) {
                $this->dispatch('notify', message: 'Terdapat bentrok jadwal, tidak bisa ditukar.', type: 'error');
                return;
            }

            DB::transaction(function () use ($swap, $fromClaim, $toClaim, $fromUser, $responderName, $profile, $fromDate, $toDate): void {
                // Cara aman untuk swap: gunakan temporary id atau detach dulu
                // Kita gunakan DB transaction dan update dengan cara yang aman
                
                // 1. Dapatkan student_ids
                $fromStudentId = $fromClaim->student_id;
                $toStudentId = $toClaim->student_id;
                
                // 2. Update keduanya dengan cara yang menghindari unique constraint
                // Kita pakai DB statement untuk update
                DB::statement('UPDATE duty_claims SET student_id = CASE id WHEN ? THEN ? WHEN ? THEN ? END WHERE id IN (?, ?)', [
                    $fromClaim->id,
                    $toStudentId,
                    $toClaim->id,
                    $fromStudentId,
                    $fromClaim->id,
                    $toClaim->id
                ]);

                $swap->update(['status' => SwapStatus::Accepted, 'responded_at' => now()]);

                $fromDateFormatted = Carbon::parse($fromDate)->locale('id')->translatedFormat('d M Y');
                $toDateFormatted = Carbon::parse($toDate)->locale('id')->translatedFormat('d M Y');
                $fromName = $fromUser?->name ?? 'Siswa';

                User::where('role', UserRole::Admin)->get()->each(function (User $admin) use ($fromName, $responderName, $fromDateFormatted, $toDateFormatted): void {
                    Notification::create([
                        'user_id' => $admin->id,
                        'type' => 'swap_info',
                        'message' => "{$fromName} dan {$responderName} menukar jadwal piket ({$fromDateFormatted} ↔ {$toDateFormatted}).",
                    ]);
                });

                if ($fromUser) {
                    Notification::create([
                        'user_id' => $fromUser->id,
                        'type' => 'swap_accepted',
                        'message' => "{$responderName} menerima tukar jadwal kamu!",
                    ]);
                }
            });

            $this->dispatch('notify', message: 'Tukar jadwal diterima.', type: 'success');
        } else {
            DB::transaction(function () use ($swap, $fromUser, $responderName): void {
                $swap->update(['status' => SwapStatus::Rejected, 'responded_at' => now()]);

                if ($fromUser) {
                    Notification::create([
                        'user_id' => $fromUser->id,
                        'type' => 'swap_rejected',
                        'message' => "{$responderName} menolak tukar jadwal kamu.",
                    ]);
                }
            });

            $this->dispatch('notify', message: 'Tukar jadwal ditolak.', type: 'success');
        }

        unset($this->incomingRequests, $this->myRequests, $this->hasPendingSwap, $this->pendingSwapIds);
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
        return view('livewire.student.swap-request');
    }
}
