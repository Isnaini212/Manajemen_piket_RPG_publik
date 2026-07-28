<?php

namespace App\Livewire\Admin;

use App\Enums\DutySlotStatus;
use App\Models\DutySlot;
use App\Models\Semester;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\StudentProfile;
use App\Models\DutyClaim;
use App\Enums\ClaimStatus;
use App\Enums\ClaimType;

#[Layout('layouts.admin')]
#[Title('Jadwal Piket')]
class DutySchedule extends Component
{
    public string $selectedWeek = '';

    public bool $showAddForm = false;
    public bool $showAutoAssignForm = false;

    // Menghapus state newSlotDate dan newSlotQuota karena form SATU HARI SAJA dihapus
    public array $batchDays = []; // Struktur: ['Senin' => 2, 'Selasa' => 3, ...]

    public ?int $editingSlotId = null;
    public int $editQuota = 0;

    public bool $showModal = false;
    public string $modalTitle = '';
    public string $modalMessage = '';
    public string $modalType = 'success'; // 'success' or 'error'
    
    // State untuk modal konfirmasi delete
    public bool $showDeleteConfirm = false;
    public ?int $slotToDelete = null;

    // State untuk modal konfirmasi bagi jadwal otomatis
    public bool $showAutoAssignConfirm = false;
    
    // State untuk modal konfirmasi looping jadwal
    public bool $showLoopConfirm = false;
    public bool $loopWithStudents = false;
    
    public int $loopWeeksCount = 4;

    // Slot Detail & Re-assignment properties
    public ?int $selectedSlotId = null;
    public string $editSlotDate = '';
    public int $editSlotQuota = 0;
    public array $slotClaimsData = [];
    public ?int $newClaimStudentId = null;
    public string $newClaimType = 'regular';

    public function mount(): void
    {
        $this->selectedWeek = now()->format('o-W');
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

    private function activeSemesterId(): ?int
    {
        return Semester::where('is_active', true)->value('id');
    }

    #[Computed]
    public function weekLabel(): string
    {
        [$start, $end] = $this->weekBounds();

        return $start->locale('id')->translatedFormat('d M') . ' – ' . $end->locale('id')->translatedFormat('d M Y');
    }

    #[Computed]
    public function slots(): Collection
    {
        $semesterId = $this->activeSemesterId();

        if (! $semesterId) {
            return collect();
        }

        [$start, $end] = $this->weekBounds();

        return DutySlot::where('semester_id', $semesterId)
            ->whereBetween('duty_date', [$start->toDateString(), $end->toDateString()])
            ->with('claims.student.user')
            ->orderBy('duty_date')
            ->get();
    }

    #[Computed]
    public function weekOptions(): array
    {
        $semester = Semester::where('is_active', true)->first();
        if (! $semester || ! $semester->start_date || ! $semester->end_date) {
            // Fallback: 10 weeks around the current week
            $options = [];
            $current = now()->startOfWeek(Carbon::MONDAY)->subWeeks(4);
            for ($i = 0; $i < 10; $i++) {
                $start = $current->copy()->addWeeks($i);
                $end = $start->copy()->endOfWeek(Carbon::SUNDAY);
                $val = $start->format('o-W');
                $options[$val] = "Minggu " . ($i + 1) . " (" . $start->locale('id')->translatedFormat('d M') . ' - ' . $end->locale('id')->translatedFormat('d M Y') . ")";
            }
            return $options;
        }

        $options = [];
        $start = Carbon::parse($semester->start_date)->startOfWeek(Carbon::MONDAY);
        $end = Carbon::parse($semester->end_date)->endOfWeek(Carbon::SUNDAY);

        $current = $start->copy();
        $weekNum = 1;
        while ($current->lte($end)) {
            $wStart = $current->copy();
            $wEnd = $current->copy()->endOfWeek(Carbon::SUNDAY);
            $val = $current->format('o-W');

            $options[$val] = "Minggu " . $weekNum . " (" . $wStart->locale('id')->translatedFormat('d M') . ' - ' . $wEnd->locale('id')->translatedFormat('d M Y') . ")";

            $current->addWeek();
            $weekNum++;
        }

        return $options;
    }

    #[Computed]
    public function allStudents(): Collection
    {
        return StudentProfile::with('user')
            ->join('users', 'student_profiles.user_id', '=', 'users.id')
            ->orderBy('users.name', 'asc')
            ->select('student_profiles.*')
            ->get();
    }

    public function selectSlot(int $slotId): void
    {
        $this->selectedSlotId = $slotId;
        $slot = DutySlot::with('claims.student.user')->find($slotId);
        if ($slot) {
            $this->editSlotDate = $slot->duty_date->format('Y-m-d');
            $this->editSlotQuota = $slot->quota;
            $this->slotClaimsData = [];
            foreach ($slot->claims as $claim) {
                $this->slotClaimsData[] = [
                    'id' => $claim->id,
                    'student_id' => $claim->student_id,
                    'claim_type' => $claim->claim_type->value ?? $claim->claim_type,
                    'status' => $claim->status->value ?? $claim->status,
                ];
            }
            $this->newClaimStudentId = null;
            $this->newClaimType = 'regular';
        }
    }

    public function addClaimToSlot(): void
    {
        if (!$this->newClaimStudentId) {
            $this->modalType = 'error';
            $this->modalTitle = 'GAGAL';
            $this->modalMessage = 'Pilih siswa terlebih dahulu.';
            $this->showModal = true;
            return;
        }
        
        // Cek apakah jumlah klaim sudah mencapai kuota
        if (count($this->slotClaimsData) >= $this->editSlotQuota) {
            $this->modalType = 'error';
            $this->modalTitle = 'GAGAL';
            $this->modalMessage = 'Kuota tidak mencukupi.';
            $this->showModal = true;
            return;
        }
        
        // Cek apakah siswa sudah terdaftar di slot ini
        foreach ($this->slotClaimsData as $data) {
            if ($data['student_id'] == $this->newClaimStudentId) {
                $this->modalType = 'error';
                $this->modalTitle = 'GAGAL';
                $this->modalMessage = 'Siswa ini sudah ada di daftar klaim.';
                $this->showModal = true;
                return;
            }
        }

        $student = StudentProfile::find($this->newClaimStudentId);
        $claimType = ($student && $student->isConvict())
            ? ClaimType::PUNISHMENT->value
            : ClaimType::REGULAR->value;

        $this->slotClaimsData[] = [
            'id' => null, // null means it's a new claim
            'student_id' => $this->newClaimStudentId,
            'claim_type' => $claimType,
            'status' => ClaimStatus::Pending->value, // Default to pending for admin assignment
        ];
        
        $this->newClaimStudentId = null;
    }

    public function removeClaimFromSlot(int $index): void
    {
        unset($this->slotClaimsData[$index]);
        $this->slotClaimsData = array_values($this->slotClaimsData);
    }

    public function saveSlotDetails(): void
    {
        $slot = DutySlot::find($this->selectedSlotId);
        if (!$slot) {
            $this->selectedSlotId = null;
            return;
        }

        $this->validate([
            'editSlotDate' => ['required', 'date'],
            'editSlotQuota' => ['required', 'integer', 'min:1'],
        ]);
        
        // Cek apakah jumlah klaim melebihi kuota
        if (count($this->slotClaimsData) > $this->editSlotQuota) {
            $this->modalType = 'error';
            $this->modalTitle = 'GAGAL';
            $this->modalMessage = 'Kuota tidak mencukupi.';
            $this->showModal = true;
            return;
        }
        
        // Cek apakah tanggal yang dipilih sudah ada slot lain di semester yang sama
        $semesterId = $slot->semester_id;
        $existingSlot = DutySlot::where('semester_id', $semesterId)
            ->where('duty_date', $this->editSlotDate)
            ->where('id', '!=', $this->selectedSlotId)
            ->first();
            
        if ($existingSlot) {
            $this->modalType = 'error';
            $this->modalTitle = 'GAGAL';
            $this->modalMessage = 'Slot untuk tanggal ini sudah ada.';
            $this->showModal = true;
            return;
        }

        // Cek apakah ada klaim yang sudah Approved yang akan dihapus
        $currentApprovedClaimIds = $slot->claims()
            ->where('status', ClaimStatus::Approved)
            ->pluck('id')
            ->toArray();
        $inputClaimIds = collect($this->slotClaimsData)->pluck('id')->filter()->toArray();
        $deletedApprovedClaims = array_diff($currentApprovedClaimIds, $inputClaimIds);

        if (!empty($deletedApprovedClaims)) {
            $this->modalType = 'error';
            $this->modalTitle = 'GAGAL';
            $this->modalMessage = 'Tidak bisa menghapus klaim yang sudah disetujui (Approved).';
            $this->showModal = true;
            return;
        }

        DB::transaction(function () use ($slot) {
            // Update slot details
            $slot->update([
                'duty_date' => $this->editSlotDate,
                'quota' => $this->editSlotQuota,
            ]);

            // Sync claims:
            // Get IDs of existing claims from input
            $inputClaimIds = collect($this->slotClaimsData)->pluck('id')->filter()->toArray();

            // Delete claims that are not in the input anymore (force delete to avoid clogging unique constraints)
            // Tapi hanya klaim yang belum Approved
            $slot->claims()
                ->whereNotIn('id', $inputClaimIds)
                ->where('status', '!=', ClaimStatus::Approved)
                ->forceDelete();

            // Create or update claims
            foreach ($this->slotClaimsData as $data) {
                // Clean up any soft-deleted claims for this student in this slot to prevent unique constraint crashes
                DutyClaim::onlyTrashed()
                    ->where('duty_slot_id', $slot->id)
                    ->where('student_id', $data['student_id'])
                    ->forceDelete();

                // Tentukan claim_type secara otomatis berdasarkan status siswa (kecuali jika tipe lamanya adalah replacement)
                $student = StudentProfile::find($data['student_id']);
                $claimType = $data['claim_type'] ?? 'regular';
                if ($claimType !== ClaimType::REPLACEMENT->value) {
                    $claimType = ($student && $student->isConvict())
                        ? ClaimType::PUNISHMENT->value
                        : ClaimType::REGULAR->value;
                }

                if ($data['id']) {
                    // Update existing
                    $claim = DutyClaim::find($data['id']);
                    if ($claim) {
                        // Jika status lama adalah Approved, tidak bisa diubah statusnya atau student_id
                        if ($claim->status === ClaimStatus::Approved) {
                            $claim->update([
                                'claim_type' => $claimType,
                            ]);
                        } else {
                            $claim->update([
                                'student_id' => $data['student_id'],
                                'claim_type' => $claimType,
                            ]);
                        }
                    }
                } else {
                    // Create new, status is always Pending
                    $slot->claims()->create([
                        'student_id' => $data['student_id'],
                        'claim_type' => $claimType,
                        'status' => ClaimStatus::Pending->value,
                    ]);
                }
            }
        });

        $this->selectedSlotId = null;
        unset($this->slots);
        
        $this->modalType = 'success';
        $this->modalTitle = 'DATA DIPERBARUI!';
        $this->modalMessage = 'Detail slot piket dan daftar klaim siswa berhasil disimpan!';
        $this->showModal = true;

        $this->dispatch('notify', message: 'Jadwal piket berhasil diperbarui.', type: 'success');
    }

    public function closeSlotDetails(): void
    {
        $this->selectedSlotId = null;
        $this->slotClaimsData = [];
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



    /**
     * Membuat beberapa slot piket sekaligus di minggu aktif berdasarkan pilihan hari.
     */
    public function addBatchSlots(): void
    {
        $semesterId = $this->activeSemesterId();
        if (!$semesterId) {
            $this->modalType = 'error';
            $this->modalTitle = 'GAGAL';
            $this->modalMessage = 'Tidak ada semester aktif.';
            $this->showModal = true;
            return;
        }

        if (empty($this->batchDays)) {
            $this->modalType = 'error';
            $this->modalTitle = 'GAGAL';
            $this->modalMessage = 'Pilih minimal satu hari.';
            $this->showModal = true;
            return;
        }

        // Validasi: semua hari yang dipilih harus memiliki kuota minimal 1
        foreach ($this->batchDays as $dayName => $quota) {
            if ($quota < 1) {
                $this->modalType = 'error';
                $this->modalTitle = 'GAGAL';
                $this->modalMessage = "Kuota untuk hari {$dayName} harus minimal 1.";
                $this->showModal = true;
                return;
            }
        }

        [$start] = $this->weekBounds();

        $created = 0;
        $skipped = 0;

        $dayOffsets = [
            'Senin' => 0,
            'Selasa' => 1,
            'Rabu' => 2,
            'Kamis' => 3,
            'Jumat' => 4,
            'Sabtu' => 5,
            'Minggu' => 6,
        ];

        DB::transaction(function () use ($semesterId, $start, $dayOffsets, &$created, &$skipped) {
            foreach ($this->batchDays as $dayName => $quota) {
                if (!isset($dayOffsets[$dayName])) continue;
                $targetDate = $start->copy()->addDays($dayOffsets[$dayName])->toDateString();

                // Hapus data yang sudah di-softdelete terlebih dahulu untuk mencegah unique constraint violation
                DutySlot::onlyTrashed()
                    ->where('semester_id', $semesterId)
                    ->whereDate('duty_date', $targetDate)
                    ->forceDelete();

                $exists = DutySlot::where('semester_id', $semesterId)
                    ->whereDate('duty_date', $targetDate)
                    ->lockForUpdate()
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                try {
                    DutySlot::create([
                        'semester_id' => $semesterId,
                        'duty_date'   => $targetDate,
                        'quota'       => $quota,
                        'status'      => DutySlotStatus::Open,
                    ]);
                    $created++;
                } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                    $skipped++;
                }
            }
        });

        $this->batchDays = [];
        $this->showAddForm = false;
        unset($this->slots);

        if ($created > 0) {
            $this->modalType = 'success';
            $this->modalTitle = 'SLOT BERHASIL DIBUAT!';
            $this->modalMessage = "Berhasil membuat {$created} slot piket baru di minggu ini." . ($skipped > 0 ? " (Serta {$skipped} hari dilewati karena sudah ada slotnya)" : "");
            $this->showModal = true;
        } else {
            $this->modalType = 'warning';
            $this->modalTitle = 'PERHATIAN';
            $this->modalMessage = 'Semua hari yang dipilih sudah memiliki slot.';
            $this->showModal = true;
        }
    }

    public function startEditQuota(int $slotId, int $currentQuota): void
    {
        $this->editingSlotId = $slotId;
        $this->editQuota     = $currentQuota;
    }

    public function cancelEdit(): void
    {
        $this->reset('editingSlotId', 'editQuota');
    }

    public function saveQuota(int $slotId): void
    {
        $slot = DutySlot::withCount('claims')->find($slotId);

        if (! $slot) {
            return;
        }

        $minQuota = $slot->claims_count;

        if ($this->editQuota < 1 || $this->editQuota < $minQuota) {
            $this->dispatch('notify', message: "Kuota tidak boleh kurang dari jumlah klaim ({$minQuota}).", type: 'error');

            return;
        }

        $slot->update(['quota' => $this->editQuota]);

        $this->reset('editingSlotId', 'editQuota');
        unset($this->slots);

        $this->dispatch('notify', message: 'Kuota berhasil diperbarui.', type: 'success');
    }

    // Method untuk menampilkan modal konfirmasi delete
    public function confirmDeleteSlot(int $slotId): void
    {
        $slot = DutySlot::with('claims.student.user', 'claims.submission')->find($slotId);

        if (! $slot) {
            return;
        }

        // Check if any approved claims or claims with approved submissions
        $hasApprovedClaims = $slot->claims->contains(function ($claim) {
            return $claim->status === \App\Enums\ClaimStatus::Approved || 
                   ($claim->submission && $claim->submission->verify_status === \App\Enums\VerifyStatus::Approved);
        });

        if ($hasApprovedClaims) {
            $this->modalType = 'error';
            $this->modalTitle = 'GAGAL MENGHAPUS SLOT';
            $this->modalMessage = 'Slot tidak bisa dihapus karena sudah ada siswa yang menyelesaikan piket dan disetujui.';
            $this->showModal = true;
            return;
        }

        if ($slot->claims->count() > 0) {
            $this->modalType = 'error';
            $this->modalTitle = 'GAGAL MENGHAPUS SLOT';
            $this->modalMessage = 'Slot tidak bisa dihapus karena sudah ada klaim siswa.';
            $this->showModal = true;
            return;
        }

        $this->slotToDelete = $slotId;
        $this->showDeleteConfirm = true;
    }

    // Method untuk mengeksekusi delete setelah konfirmasi
    public function deleteSlot(): void
    {
        if (! $this->slotToDelete) {
            return;
        }

        $slot = DutySlot::find($this->slotToDelete);

        if (! $slot) {
            $this->showDeleteConfirm = false;
            return;
        }

        $slot->forceDelete();
        unset($this->slots);

        $this->showDeleteConfirm = false;
        $this->slotToDelete = null;
        
        $this->modalType = 'success';
        $this->modalTitle = '🎉 SLOT BERHASIL DIHAPUS';
        $this->modalMessage = 'Slot piket telah dihapus secara permanen.';
        $this->showModal = true;
    }

    /**
     * Import jadwal piket dari file Excel.
     * Kolom Excel: tanggal | jam_mulai | jam_selesai | kuota
     */

    public function confirmLoop(bool $withStudents): void
    {
        $this->loopWithStudents = $withStudents;
        $this->showLoopConfirm = true;
    }

    /**
     * Duplikasi semua slot piket minggu ini ke beberapa minggu berikutnya.
     * Skip tanggal yang sudah ada slotnya untuk mencegah duplikasi.
     */
    public function loopWeekly(bool $withStudents = false): void
    {
        $semesterId = $this->activeSemesterId();

        if (! $semesterId) {
            $this->modalType = 'error';
            $this->modalTitle = ' TIDAK ADA SEMESTER AKTIF';
            $this->modalMessage = 'Tidak ada semester aktif. Buat semester terlebih dahulu di panel manajemen semester.';
            $this->showModal = true;
            return;
        }

        [$currentStart, $currentEnd] = $this->weekBounds();

        // Ambil slot minggu aktif beserta claims-nya jika $withStudents true
        $currentSlotsQuery = DutySlot::where('semester_id', $semesterId)
            ->whereBetween('duty_date', [$currentStart->toDateString(), $currentEnd->toDateString()]);
            
        if ($withStudents) {
            $currentSlotsQuery->with('claims');
        }
        
        $currentSlots = $currentSlotsQuery->get();

        if ($currentSlots->isEmpty()) {
            $this->modalType = 'error';
            $this->modalTitle = ' MINGGU KOSONG';
            $this->modalMessage = 'Tidak ada slot piket di minggu ini untuk diduplikasi.';
            $this->showModal = true;
            return;
        }

        $created = 0;
        $skippedDates = [];

        DB::transaction(function () use ($currentSlots, $semesterId, $withStudents, &$created, &$skippedDates) {
            for ($i = 1; $i <= $this->loopWeeksCount; $i++) {
                foreach ($currentSlots as $slot) {
                    $newDate = Carbon::parse($slot->duty_date)->addWeeks($i);
                    
                    // Hapus data yang sudah di-softdelete terlebih dahulu untuk mencegah unique constraint violation
                    DutySlot::onlyTrashed()
                        ->where('semester_id', $semesterId)
                        ->whereDate('duty_date', $newDate->toDateString())
                        ->forceDelete();

                    // Cek apakah tanggal target sudah memiliki slot
                    $exists = DutySlot::where('semester_id', $semesterId)
                        ->whereDate('duty_date', $newDate->toDateString())
                        ->exists();

                    if ($exists) {
                        $skippedDates[] = $newDate->format('d-m-Y');
                        continue;
                    }

                    $newSlot = DutySlot::create([
                        'semester_id' => $semesterId,
                        'duty_date'   => $newDate->toDateString(),
                        'quota'       => $slot->quota,
                        'status'      => DutySlotStatus::Open,
                    ]);

                    // Copy claims jika diminta
                    if ($withStudents && $slot->claims->isNotEmpty()) {
                        foreach ($slot->claims as $claim) {
                            DutyClaim::create([
                                'duty_slot_id' => $newSlot->id,
                                'student_id'   => $claim->student_id,
                                'claim_type'   => $claim->claim_type,
                                'status'       => ClaimStatus::Pending->value, // Selalu pending untuk minggu depan
                            ]);
                        }
                    }

                    $created++;
                }
            }
        });

        // Pindah tampilan ke minggu depan
        $nextStart = $currentStart->copy()->addWeek();
        $this->selectedWeek = $nextStart->format('o-W');
        unset($this->slots);

        if ($created === 0) {
            $this->modalType = 'error';
            $this->modalTitle = ' DUPLIKASI BATAL';
            $this->modalMessage = "Semua tanggal target sudah terisi slot jadwal untuk {$this->loopWeeksCount} minggu ke depan.";
            $this->showModal = true;
        } else {
            $this->modalType = 'success';
            $this->modalTitle = '🎉 DUPLIKASI BERHASIL!';
            $msg = "⚔️ Berhasil menduplikasi {$created} slot piket untuk {$this->loopWeeksCount} minggu ke depan!";
            if (!empty($skippedDates)) {
                $msg .= "\n\n Beberapa tanggal dilewati karena sudah memiliki slot: " . implode(', ', array_unique($skippedDates));
            }
            $this->modalMessage = $msg;
            $this->showModal = true;
        }

        $this->showLoopConfirm = false;

        $this->dispatch('notify', [
            'type'    => 'success',
            'message' => "⚔️ {$created} slot berhasil digandakan!",
        ]);
    }

    #[Computed]
    public function totalStudentsCount(): int
    {
        return StudentProfile::count();
    }

    #[Computed]
    public function recommendedQuota(): int
    {
        $slotsCount = $this->slots->count();
        if ($slotsCount === 0) {
            return 0;
        }
        return (int) ceil($this->totalStudentsCount / $slotsCount);
    }
    
    // Method untuk toggle pilihan hari
    public function toggleBatchDay(string $day): void
    {
        if (isset($this->batchDays[$day])) {
            unset($this->batchDays[$day]);
        } else {
            // Set kuota default ke 0 ketika pertama kali mencentang hari
            $this->batchDays[$day] = 0;
        }
    }
    
    // Method untuk update kuota per hari
    public function updateBatchDayQuota(string $day, int $quota): void
    {
        if (isset($this->batchDays[$day])) {
            $this->batchDays[$day] = max(0, min(50, $quota));
        }
    }
    
    #[Computed]
    public function recommendedBatchQuota(): int
    {
        $selectedDaysCount = count($this->batchDays);
        if ($selectedDaysCount === 0 || $this->totalStudentsCount === 0) {
            return 0;
        }
        // Pastikan pembagian menggunakan floating point agar ceil bekerja benar
        return (int) ceil((float) $this->totalStudentsCount / (float) $selectedDaysCount);
    }

    /**
     * Otomatis membagi siswa aktif yang belum terjadwal di minggu ini
     * ke slot piket yang masih memiliki sisa kuota.
     * Menggunakan algoritma round-robin agar pembagian merata per hari.
     */
    public function autoAssign(): void
    {
        $semesterId = $this->activeSemesterId();
        if (!$semesterId) {
            $this->modalType = 'error';
            $this->modalTitle = ' TIDAK ADA SEMESTER AKTIF';
            $this->modalMessage = 'Tidak ada semester aktif. Silakan buat semester terlebih dahulu.';
            $this->showModal = true;
            return;
        }

        [$start, $end] = $this->weekBounds();

        // Ambil semua slot di minggu aktif beserta klaim-klaimnya
        $weekSlots = DutySlot::where('semester_id', $semesterId)
            ->whereBetween('duty_date', [$start->toDateString(), $end->toDateString()])
            ->with('claims')
            ->orderBy('duty_date')
            ->get();

        if ($weekSlots->isEmpty()) {
            $this->modalType = 'error';
            $this->modalTitle = ' TIDAK ADA SLOT';
            $this->modalMessage = 'Tidak ada slot piket yang tersedia di minggu ini untuk dibagikan. Silakan buat slot jadwal harian terlebih dahulu.';
            $this->showModal = true;
            return;
        }

        // FIX: Exclude siswa yang punya klaim dengan STATUS APAPUN di minggu ini
        // (bukan hanya pending/approved) — mencegah UniqueConstraintViolation
        $assignedStudentIds = DutyClaim::whereIn('duty_slot_id', $weekSlots->pluck('id'))
            ->pluck('student_id')
            ->unique()
            ->toArray();

        // Cari siswa yang belum punya klaim piket sama sekali di minggu ini
        $unassignedStudents = StudentProfile::whereNotIn('id', $assignedStudentIds)->get();

        if ($unassignedStudents->isEmpty()) {
            $this->modalType = 'warning';
            $this->modalTitle = 'ℹ️ SEMUA SISWA TERJADWAL';
            $this->modalMessage = 'Seluruh siswa sudah memiliki jadwal piket di minggu ini (termasuk yang pending atau ditolak).';
            $this->showModal = true;
            return;
        }

        // Hitung kapasitas tiap slot (hitung SEMUA klaim bukan hanya aktif)
        $availableSlots = [];
        $totalRemainingCapacity = 0;
        foreach ($weekSlots as $slot) {
            $filledCount = $slot->claims->count(); // semua status
            $remaining = max(0, $slot->quota - $filledCount);
            $totalRemainingCapacity += $remaining;
            if ($remaining > 0) {
                $availableSlots[] = [
                    'model'     => $slot,
                    'remaining' => $remaining,
                ];
            }
        }

        if ($totalRemainingCapacity === 0) {
            $this->modalType = 'error';
            $this->modalTitle = ' KUOTA SLOT PENUH';
            $this->modalMessage = 'Semua slot piket di minggu ini sudah terisi penuh. Silakan naikkan kuota slot lalu coba lagi.';
            $this->showModal = true;
            return;
        }

        $assignedCount = 0;
        $studentIndex  = 0;
        $unassignedCount = $unassignedStudents->count();

        DB::transaction(function () use ($availableSlots, $unassignedStudents, &$assignedCount, &$studentIndex, $unassignedCount) {
            // FIX: Algoritma round-robin — putar ke tiap slot satu-satu
            // agar distribusi merata, tidak mengisi satu slot penuh dulu
            $slotCount = count($availableSlots);
            $slotIndex = 0;

            while ($studentIndex < $unassignedCount) {
                // Cari slot berikutnya yang masih ada kapasitas (round-robin)
                $found   = false;
                $checked = 0;
                while ($checked < $slotCount) {
                    $idx = $slotIndex % $slotCount;
                    if ($availableSlots[$idx]['remaining'] > 0) {
                        $found = true;
                        break;
                    }
                    $slotIndex++;
                    $checked++;
                }

                if (!$found) {
                    break; // Semua slot sudah penuh
                }

                $idx     = $slotIndex % $slotCount;
                $student = $unassignedStudents[$studentIndex];

                $availableSlots[$idx]['model']->claims()->create([
                    'student_id' => $student->id,
                    'claim_type' => ClaimType::REGULAR->value,
                    'status'     => ClaimStatus::Pending->value,
                ]);

                $availableSlots[$idx]['remaining']--;
                $assignedCount++;
                $studentIndex++;
                $slotIndex++; // pindah ke slot berikutnya (round-robin)
            }
        });

        unset($this->slots);

        if ($assignedCount < $unassignedCount) {
            $leftover = $unassignedCount - $assignedCount;
            $this->modalType  = 'warning';
            $this->modalTitle = ' PEMBAGIAN SEBAGIAN BERHASIL';
            $this->modalMessage = "Berhasil menjadwalkan {$assignedCount} siswa secara otomatis!\n\n Masih ada {$leftover} siswa yang belum mendapatkan jadwal karena total kuota slot tidak mencukupi.\n\nSilakan naikkan kuota slot piket (gunakan tombol ✏ di kartu hari) lalu klik Bagi Jadwal Otomatis kembali.";
        } else {
            $this->modalType  = 'success';
            $this->modalTitle = '🎉 PEMBAGIAN BERHASIL!';
            $this->modalMessage = "Berhasil membagikan tugas piket secara merata ke seluruh {$assignedCount} siswa yang belum terjadwal di minggu ini!";
        }
        $this->showAutoAssignConfirm = false;
        $this->showModal = true;

        $this->dispatch('notify', message: 'Jadwal piket otomatis berhasil dibagikan.', type: 'success');
    }

    public function runCheckMissed(): void
    {
        \Illuminate\Support\Facades\Artisan::call('piket:check-missed');
        $this->dispatch('notify', message: 'Pengecekan piket terlewat & sanksi otomatis berhasil dijalankan!', type: 'success');
    }

    public function render()
    {
        return view('livewire.admin.duty-schedule');
    }
}
