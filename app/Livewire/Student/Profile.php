<?php

namespace App\Livewire\Student;

use App\Enums\ClaimStatus;
use App\Models\Badge;
use App\Models\DutyClaim;
use App\Models\StudentBadge;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Profil')]
class Profile extends Component
{
    use WithFileUploads;

    public bool $isEditing = false;

    public string $name = '';

    public string $username = '';

    #[Validate('nullable|image|mimes:jpg,jpeg,png,gif,webp|max:4096')]
    public $newAvatar = null;

    public function mount(): void
    {
        $profile = $this->profile;
        $this->name = $profile?->user?->name ?? '';
        $this->username = $profile?->user?->username ?? '';
    }

    #[Computed]
    public function profile(): ?StudentProfile
    {
        return StudentProfile::with('user')->where('user_id', auth()->id())->first();
    }

    public function saveProfile(): void
    {
        $profile = $this->profile;

        if (! $profile) {
            $this->dispatch('notify', message: 'Profil tidak ditemukan.', type: 'error');

            return;
        }

        $user = $profile->user;
        $userId = $user?->id;

        $this->validate([
            'name' => ['required', 'string', "regex:/^[a-zA-Z\s\'.]+$/", 'max:100'],
            'username' => ['required', 'string', 'alpha_dash', 'max:255', \Illuminate\Validation\Rule::unique('users', 'username')->ignore($userId)],
            'newAvatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:4096'],
        ], [
            'name.required' => 'Nama harus diisi.',
            'name.regex' => 'Nama lengkap hanya boleh berisi huruf dan spasi (nama asli).',
            'name.max' => 'Nama maksimal 100 karakter.',
            'username.required' => 'Username harus diisi.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, garis bawah (_), atau tanda hubung (-).',
            'username.unique' => 'Username ini sudah digunakan.',
        ]);

        $user?->update([
            'name' => $this->name,
            'username' => $this->username,
        ]);

        if ($this->newAvatar) {
            // Delete old avatar file if exists
            if ($profile->profile_picture) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($profile->profile_picture);
            }

            $path = $this->newAvatar->store('avatars', 'public');
            $profile->update(['profile_picture' => $path]);
        }

        $this->newAvatar = null;
        $this->isEditing = false;
        unset($this->profile);

        $this->dispatch('notify', message: 'Profil berhasil diperbarui!', type: 'success');
    }

    public function removeAvatar(): void
    {
        $profile = $this->profile;

        if (! $profile) {
            return;
        }

        if ($profile->profile_picture) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($profile->profile_picture);
            $profile->update(['profile_picture' => null]);
            unset($this->profile);
            $this->dispatch('notify', message: 'Foto profil dihapus.', type: 'success');
        }
    }

    #[Computed]
    public function xpLifeBuyCost(): int
    {
        return (int) (\App\Models\SystemConfig::get('xp_per_life_buy') ?? 150);
    }

    #[Computed]
    public function livesMax(): int
    {
        return (int) (\App\Models\SystemConfig::get('lives_max') ?? 3);
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

        $cost = $this->xpLifeBuyCost;
        $maxLives = $this->livesMax;

        if ($profile->lives >= $maxLives) {
            $this->dispatch('notify', message: 'Nyawa kamu sudah maksimal!', type: 'error');

            return;
        }

        if ($profile->xp < $cost) {
            $this->dispatch('notify', message: "XP kamu tidak cukup. Butuh {$cost} XP untuk memulihkan 1 nyawa.", type: 'error');

            return;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($profile, $cost): void {
            // Deduct XP
            $profile->decrement('xp', $cost);
            $profile->xpLogs()->create([
                'amount' => -$cost,
                'reason' => 'tukar_xp_nyawa',
            ]);

            // Restore +1 Life
            $profile->increaseLivesPartially(1, 'tukar_xp_nyawa');
        });

        unset($this->profile);
        $this->dispatch('notify', message: "Berhasil! Nyawa bertambah +1. (-{$cost} XP)", type: 'success');
    }

    /**
     * @return array{total_piket: int, total_xp: int, streak: int, badge_count: int}
     */
    #[Computed]
    public function stats(): array
    {
        $profile = $this->profile;

        if (! $profile) {
            return ['total_piket' => 0, 'total_xp' => 0, 'streak' => 0, 'badge_count' => 0];
        }

        return [
            'total_piket' => DutyClaim::where('student_id', $profile->id)
                ->where('status', ClaimStatus::Approved->value)->count(),
            'total_xp' => (int) $profile->xp,
            'streak' => $this->currentStreak($profile->id),
            'badge_count' => StudentBadge::where('student_profile_id', $profile->id)->count(),
        ];
    }

    /**
     * All system badges flagged with whether the student owns them.
     */
    #[Computed]
    public function badges(): Collection
    {
        $profile = $this->profile;

        $ownedIds = $profile
            ? StudentBadge::where('student_profile_id', $profile->id)->pluck('badge_id')->all()
            : [];

        return Badge::orderBy('name')->get()->map(function (Badge $badge) use ($ownedIds) {
            $badge->owned = in_array($badge->id, $ownedIds, true);

            return $badge;
        });
    }

    #[Computed]
    public function recentClaims(): Collection
    {
        $profile = $this->profile;

        if (! $profile) {
            return collect();
        }

        return DutyClaim::where('student_id', $profile->id)
            ->with('dutySlot')
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * Approved-claim streak, newest-first by duty date.
     */
    private function currentStreak(int $profileId): int
    {
        $statuses = DutyClaim::query()
            ->join('duty_slots', 'duty_claims.duty_slot_id', '=', 'duty_slots.id')
            ->where('duty_claims.student_id', $profileId)
            ->orderByDesc('duty_slots.duty_date')
            ->pluck('duty_claims.status');

        $streak = 0;

        foreach ($statuses as $status) {
            $value = $status instanceof ClaimStatus ? $status->value : $status;

            if ($value === ClaimStatus::Approved->value) {
                $streak++;

                continue;
            }

            break;
        }

        return $streak;
    }

    public function render()
    {
        return view('livewire.student.profile');
    }
}
