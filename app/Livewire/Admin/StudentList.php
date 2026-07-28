<?php

namespace App\Livewire\Admin;

use App\Enums\StudentStatus;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

#[Layout('layouts.admin')]
#[Title('Daftar Siswa')]
class StudentList extends Component
{
    use WithPagination, WithFileUploads;

    public string $search = '';

    public string $filterStatus = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public ?int $editingStudentId = null;
    public string $editName = '';
    public string $editUsername = '';
    public string $editStatus = '';
    public int $editLives = 0;
    public $editAvatar; // For file upload
    public ?string $currentAvatarUrl = null;
    public bool $removeAvatar = false;
    public bool $showEditModal = false;

    public function editStudent(int $id, string $name): void
    {
        $this->editingStudentId = $id;
        $this->editName = $name;
        $this->editAvatar = null;
        $this->removeAvatar = false;
        
        $profile = StudentProfile::with('user')->find($id);
        $this->editUsername = $profile?->user?->username ?? '';
        $this->currentAvatarUrl = $profile ? $profile->avatar_url : null;
        $this->editStatus = $profile?->status?->value ?? 'citizen';
        $this->editLives = (int) ($profile?->lives ?? 0);
        
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingStudentId = null;
        $this->editName = '';
        $this->editUsername = '';
        $this->editAvatar = null;
        $this->editStatus = '';
        $this->editLives = 0;
        $this->removeAvatar = false;
    }

    public function updatedEditLives($value): void
    {
        $lives = (int) $value;
        if ($lives > 0 && $this->editStatus === 'convict') {
            $this->editStatus = 'citizen';
        } elseif ($lives <= 0 && $this->editStatus === 'citizen') {
            $this->editStatus = 'convict';
        }
    }

    public function updatedEditStatus($value): void
    {
        if ($value === 'citizen' && $this->editLives <= 0) {
            $this->editLives = 1;
        } elseif ($value === 'convict' && $this->editLives > 0) {
            $this->editLives = 0;
        }
    }

    public function updateStudent(): void
    {
        // Otomatis lestarikan keselarasan antara status & nyawa sebelum validasi
        if ($this->editLives > 0 && $this->editStatus === 'convict') {
            $this->editStatus = 'citizen';
        } elseif ($this->editLives <= 0 && $this->editStatus === 'citizen') {
            $this->editStatus = 'convict';
        }

        $userId = StudentProfile::find($this->editingStudentId)?->user_id;

        $this->validate([
            'editName'     => ['required', 'string', "regex:/^[a-zA-Z\s\'.]+$/", 'max:100'],
            'editUsername' => ['required', 'string', 'alpha_dash', 'max:255', \Illuminate\Validation\Rule::unique('users', 'username')->ignore($userId)],
            'editStatus'   => ['required', 'in:citizen,convict'],
            'editLives'    => ['required', 'integer', 'min:0', 'max:10'],
            'editAvatar'   => ['nullable', 'image', 'max:2048'],
        ], [
            'editName.required'     => 'Nama harus diisi.',
            'editName.regex'        => 'Nama hanya boleh berisi huruf dan spasi (nama asli).',
            'editName.max'          => 'Nama maksimal 100 karakter.',
            'editUsername.required' => 'Username harus diisi.',
            'editUsername.alpha_dash' => 'Username hanya boleh berisi huruf, angka, garis bawah (_), atau tanda hubung (-).',
            'editUsername.unique'   => 'Username ini sudah digunakan.',
            'editStatus.required'   => 'Status harus dipilih.',
            'editStatus.in'         => 'Status tidak valid.',
            'editLives.required'    => 'Nyawa harus diisi.',
            'editLives.integer'     => 'Nyawa harus berupa angka.',
            'editLives.min'         => 'Nyawa minimal 0.',
            'editLives.max'         => 'Nyawa maksimal 10.',
            'editAvatar.image'      => 'File harus berupa gambar.',
            'editAvatar.max'        => 'Ukuran gambar maksimal 2MB.',
        ]);

        if ($this->editingStudentId) {
            $profile = StudentProfile::with('user')->find($this->editingStudentId);
            if ($profile) {
                // Update nama & username
                if ($profile->user) {
                    $profile->user->update([
                        'name' => $this->editName,
                        'username' => $this->editUsername,
                    ]);
                }

                // Update status (gunakan changeStatus() agar log tercatat jika berubah)
                $newStatus = StudentStatus::from($this->editStatus);
                if ($profile->status !== $newStatus) {
                    $profile->changeStatus($newStatus);
                }

                // Update nyawa langsung (catat ke life_logs)
                $currentLives = (int) $profile->fresh()->lives;
                if ($currentLives !== $this->editLives) {
                    $diff = $this->editLives - $currentLives;
                    $profile->fresh()->lifeLogs()->create([
                        'change' => $diff,
                        'reason' => 'admin_manual_edit',
                    ]);
                    $profile->update(['lives' => $this->editLives]);
                }

                // Hapus avatar jika dicentang atau diganti baru
                if ($this->removeAvatar || $this->editAvatar) {
                    if ($profile->profile_picture && \Illuminate\Support\Facades\Storage::disk('public')->exists($profile->profile_picture)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($profile->profile_picture);
                    }
                    $profile->update(['profile_picture' => null]);
                }

                // Simpan avatar baru
                if ($this->editAvatar) {
                    $path = $this->editAvatar->store('avatars', 'public');
                    $profile->update(['profile_picture' => $path]);
                }

                $this->dispatch('notify', message: 'Profil siswa berhasil diperbarui', type: 'success');
            }
        }

        $this->showEditModal = false;
        $this->editingStudentId = null;
        $this->editName = '';
        $this->editUsername = '';
        $this->editAvatar = null;
        $this->editStatus = '';
        $this->editLives = 0;
        $this->removeAvatar = false;
    }

    public function deleteStudent(int $id): void
    {
        $profile = StudentProfile::with(['user', 'dutyClaims.submission'])->find($id);
        
        if ($profile) {
            // Hapus avatar dari disk
            if ($profile->profile_picture && \Illuminate\Support\Facades\Storage::disk('public')->exists($profile->profile_picture)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($profile->profile_picture);
            }

            // Hapus semua file bukti submission
            foreach ($profile->dutyClaims as $claim) {
                if ($claim->submission && $claim->submission->proof_url) {
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($claim->submission->proof_url)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($claim->submission->proof_url);
                    }
                }
            }

            // Hapus user (akan men-cascade hapus profile, claim, dsb)
            if ($profile->user) {
                $profile->user->delete();
            }

            $this->dispatch('notify', message: 'Siswa beserta seluruh riwayatnya berhasil dihapus permanen', type: 'success');
        }
    }

    public function render()
    {
        $students = StudentProfile::with(['user', 'studentBadges'])
            ->when($this->search, function ($q) {
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$this->search}%")
                    ->orWhere('username', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%"));
            })
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->withExists(['dutyClaims as has_duty_this_week' => function ($q) {
                $q->whereHas('dutySlot', function ($qSlot) {
                    $qSlot->whereBetween('duty_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                })->where('status', \App\Enums\ClaimStatus::Approved->value);
            }])
            ->orderByDesc('xp')
            ->paginate(20);

        return view('livewire.admin.student-list', compact('students'));
    }
}
