<?php

namespace App\Livewire\Admin;

use App\Enums\ClaimStatus;
use App\Enums\VerifyStatus;
use App\Models\Notification;
use App\Models\Submission;
use App\Services\Contracts\PenaltyServiceInterface;
use App\Services\Contracts\RewardServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Verifikasi Bukti')]
class SubmissionVerification extends Component
{
    use WithPagination;

    public string $filter = 'pending';

    public ?int $selectedSubmissionId = null;

    public string $rejectionReason = '';

    public bool $showModal = false;
    
    public bool $showConfirmModal = false;
    public string $confirmType = ''; // 'approve', 'reject', 'reject_final'

    public bool $showCleanupModal = false;
    public string $cleanupTimeframe = '1_month'; // 1_month, 2_months, 3_months, before_date, all_old
    public string $cleanupBeforeDate = '';
    public string $cleanupStatus = 'approved'; // approved, rejected, all

    public function openCleanupModal(): void
    {
        $this->cleanupBeforeDate = now()->subMonth()->format('Y-m-d');
        $this->showCleanupModal = true;
    }

    public function closeCleanupModal(): void
    {
        $this->showCleanupModal = false;
    }

    private function getCleanupQuery()
    {
        $query = Submission::query();

        match ($this->cleanupTimeframe) {
            '1_month' => $query->where('uploaded_at', '<=', now()->subMonth()),
            '2_months' => $query->where('uploaded_at', '<=', now()->subMonths(2)),
            '3_months' => $query->where('uploaded_at', '<=', now()->subMonths(3)),
            'before_date' => $query->when($this->cleanupBeforeDate, fn ($q) => $q->whereDate('uploaded_at', '<=', $this->cleanupBeforeDate)),
            'all_old' => $query->where('uploaded_at', '<', today()),
            default => $query->where('uploaded_at', '<=', now()->subMonth()),
        };

        if ($this->cleanupStatus === 'approved') {
            $query->where('verify_status', VerifyStatus::Approved->value);
        } elseif ($this->cleanupStatus === 'rejected') {
            $query->whereIn('verify_status', [VerifyStatus::Rejected->value, VerifyStatus::RejectedFinal->value]);
        }

        return $query->where(function ($q) {
            $q->whereNotNull('proof_url')
              ->orWhereHas('histories', fn ($h) => $h->whereNotNull('proof_url'));
        });
    }

    #[Computed]
    public function cleanupStats(): array
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $submissions = $this->getCleanupQuery()->with('histories')->get();

        $fileCount = 0;
        $totalBytes = 0;
        $trackedFiles = [];

        foreach ($submissions as $sub) {
            if ($sub->proof_url && $disk->exists($sub->proof_url)) {
                $fileCount++;
                $totalBytes += $disk->size($sub->proof_url);
                $trackedFiles[$sub->proof_url] = true;
            }

            foreach ($sub->histories as $history) {
                if ($history->proof_url && $disk->exists($history->proof_url)) {
                    $fileCount++;
                    $totalBytes += $disk->size($history->proof_url);
                    $trackedFiles[$history->proof_url] = true;
                }
            }
        }

        // Count orphaned files on disk (files in storage without valid active DB record)
        $allFiles = $disk->allFiles('submissions');
        $activeUrls = Submission::whereNotNull('proof_url')->pluck('proof_url')->all();
        $historyUrls = \App\Models\SubmissionHistory::whereNotNull('proof_url')->pluck('proof_url')->all();
        $validUrls = array_flip(array_merge($activeUrls, $historyUrls));

        foreach ($allFiles as $file) {
            if (! isset($validUrls[$file]) && ! isset($trackedFiles[$file])) {
                if ($disk->exists($file)) {
                    $fileCount++;
                    $totalBytes += $disk->size($file);
                }
            }
        }

        return [
            'submissions_count' => $submissions->count(),
            'file_count' => $fileCount,
            'formatted_size' => $this->formatBytes($totalBytes),
            'raw_bytes' => $totalBytes,
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    public function purgeOldProofs(): void
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $submissions = $this->getCleanupQuery()->with('histories')->get();

        $deletedFilesCount = 0;
        $freedBytes = 0;
        $trackedFiles = [];

        foreach ($submissions as $sub) {
            if ($sub->proof_url && $disk->exists($sub->proof_url)) {
                $freedBytes += $disk->size($sub->proof_url);
                $disk->delete($sub->proof_url);
                $trackedFiles[$sub->proof_url] = true;
                $deletedFilesCount++;
            }

            foreach ($sub->histories as $history) {
                if ($history->proof_url && $disk->exists($history->proof_url)) {
                    $freedBytes += $disk->size($history->proof_url);
                    $disk->delete($history->proof_url);
                    $trackedFiles[$history->proof_url] = true;
                    $deletedFilesCount++;
                }
                $history->delete();
            }

            $sub->update(['proof_url' => null]);
        }

        // Clean up any orphaned files on disk (files without active DB record)
        $allFiles = $disk->allFiles('submissions');
        $activeUrls = Submission::whereNotNull('proof_url')->pluck('proof_url')->all();
        $historyUrls = \App\Models\SubmissionHistory::whereNotNull('proof_url')->pluck('proof_url')->all();
        $validUrls = array_flip(array_merge($activeUrls, $historyUrls));

        foreach ($allFiles as $file) {
            if (! isset($validUrls[$file]) && ! isset($trackedFiles[$file])) {
                if ($disk->exists($file)) {
                    $freedBytes += $disk->size($file);
                    $disk->delete($file);
                    $deletedFilesCount++;
                }
            }
        }

        // Clean up empty directories in submissions folder
        $allDirectories = $disk->allDirectories('submissions');
        foreach (array_reverse($allDirectories) as $dir) {
            if (empty($disk->allFiles($dir)) && empty($disk->directories($dir))) {
                $disk->deleteDirectory($dir);
            }
        }

        $formattedFreed = $this->formatBytes($freedBytes);

        $this->showCleanupModal = false;
        unset($this->submissions, $this->cleanupStats);

        $this->dispatch('notify',
            message: "Berhasil menghapus {$deletedFilesCount} file foto bukti ({$formattedFreed} penyimpanan dihemat)!",
            type: 'success'
        );
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function submissions()
    {
        return Submission::query()
            ->when($this->filter === 'pending', fn ($q) => $q->where('verify_status', VerifyStatus::Pending->value))
            ->when($this->filter === 'approved', fn ($q) => $q->where('verify_status', VerifyStatus::Approved->value))
            ->when($this->filter === 'rejected', fn ($q) => $q->whereIn('verify_status', [
                VerifyStatus::Rejected->value,
                VerifyStatus::RejectedFinal->value,
            ]))
            ->with(['dutyClaim.student.user', 'dutyClaim.dutySlot'])
            ->latest('uploaded_at')
            ->paginate(15);
    }

    #[Computed]
    public function pendingCount(): int
    {
        return Submission::where('verify_status', VerifyStatus::Pending->value)->count();
    }

    #[Computed]
    public function selectedSubmission(): ?Submission
    {
        if (! $this->selectedSubmissionId) {
            return null;
        }

        return Submission::with(['dutyClaim.student.user', 'dutyClaim.dutySlot', 'histories'])
            ->find($this->selectedSubmissionId);
    }

    public function openDetail(int $id): void
    {
        $this->selectedSubmissionId = $id;
        $this->rejectionReason = '';
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->reset('selectedSubmissionId', 'rejectionReason', 'showModal', 'showConfirmModal', 'confirmType');
    }
    
    public function openConfirmModal(string $type): void
    {
        $this->confirmType = $type;
        $this->showConfirmModal = true;
    }
    
    public function closeConfirmModal(): void
    {
        $this->showConfirmModal = false;
        $this->confirmType = '';
    }

    public function approve(): void
    {
        $submission = Submission::with(['dutyClaim', 'histories'])->find($this->selectedSubmissionId);
        $claim = $submission?->dutyClaim;

        if (! $submission || ! $claim) {
            $this->dispatch('notify', message: 'Submission tidak ditemukan.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($submission, $claim): void {
                $submission->approve(); // also cleans history files

                $claim->update(['status' => ClaimStatus::Approved]);

                app(RewardServiceInterface::class)->grantReward($claim);
            });
        } catch (\Throwable $e) {
            Log::error('Gagal approve submission', ['id' => $submission->id, 'error' => $e->getMessage()]);
            $this->dispatch('notify', message: 'Terjadi kesalahan saat menyetujui.', type: 'error');

            return;
        }

        $this->closeModal();
        unset($this->submissions, $this->pendingCount);
        $this->dispatch('notify', message: 'Bukti disetujui & reward diberikan!', type: 'success');
    }

    public function reject(bool $isFinal = false): void
    {
        $this->validate(
            ['rejectionReason' => ['required', 'string', 'max:500']],
            ['rejectionReason.required' => 'Alasan penolakan wajib diisi.'],
        );

        $submission = Submission::with('dutyClaim.student.user')->find($this->selectedSubmissionId);
        $claim = $submission?->dutyClaim;

        if (! $submission || ! $claim) {
            $this->dispatch('notify', message: 'Submission tidak ditemukan.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($submission, $claim, $isFinal): void {
                $dutyDate = $claim->dutySlot?->duty_date;
                $isPast = $dutyDate && \Carbon\Carbon::parse($dutyDate)->isPast() && ! \Carbon\Carbon::parse($dutyDate)->isToday();

                // Always archive to history via the model's reject() method.
                $submission->reject($this->rejectionReason, $isFinal);

                if ($isFinal) {
                    // Final rejection → immediate penalty, no replacement offered.
                    app(PenaltyServiceInterface::class)->triggerFailureFlow($claim, true);
                } elseif ($isPast && ! $submission->replacement_id) {
                    // Regular rejection on a past scheduled date pushes to replacement flow.
                    app(PenaltyServiceInterface::class)->triggerFailureFlow($claim, false);
                } else {
                    // Normal rejection — student must resubmit.
                    Notification::create([
                        'user_id' => $claim->student?->user_id,
                        'type'    => 'resubmit_required',
                        'message' => 'Bukti piket kamu ditolak: ' . $this->rejectionReason . '. Silakan upload ulang.',
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Gagal reject submission', ['id' => $submission->id, 'error' => $e->getMessage()]);
            $this->dispatch('notify', message: 'Terjadi kesalahan saat menolak.', type: 'error');

            return;
        }

        $this->closeModal();
        unset($this->submissions, $this->pendingCount);
        $this->dispatch('notify', message: $isFinal ? 'Bukti ditolak final & penalti diterapkan.' : 'Bukti ditolak, siswa diminta upload ulang.', type: 'success');
    }

    public function render()
    {
        return view('livewire.admin.submission-verification');
    }
}
