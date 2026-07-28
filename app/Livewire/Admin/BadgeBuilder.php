<?php

namespace App\Livewire\Admin;

use App\Models\Badge;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\Contracts\BadgeEngineInterface;
use App\Models\StudentProfile;

#[Layout('layouts.admin')]
#[Title('Badge Builder')]
class BadgeBuilder extends Component
{
    use WithFileUploads;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $formName = '';

    public string $formDescription = '';

    public $formIcon = null;

    public ?int $manualStudentId = null;
    public ?int $manualBadgeId = null;



    #[Computed]
    public function badges(): Collection
    {
        return Badge::with(['studentBadges.studentProfile.user'])->withCount('studentBadges')->orderBy('name')->get();
    }

    #[Computed]
    public function allStudents(): Collection
    {
        return StudentProfile::with('user')->get()->sortBy(fn($sp) => $sp->user->name);
    }

    public function newBadge(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->dispatch('open-modal', 'badge-modal');
    }

    public function saveBadge(): void
    {
        $this->validate([
            'formName' => ['required', 'string', 'max:100'],
            'formDescription' => ['required', 'string', 'max:500'],
            'formIcon' => ['nullable', 'image', 'max:2048'],
        ]);

        DB::transaction(function (): void {
            $data = [
                'name' => $this->formName,
                'description' => $this->formDescription,
            ];

            if ($this->formIcon) {
                $data['icon_url'] = $this->formIcon->store('badges', 'public');
            }

            if ($this->editingId) {
                $badge = Badge::findOrFail($this->editingId);

                if ($this->formIcon && $badge->icon_url) {
                    Storage::disk('public')->delete($badge->icon_url);
                }

                $badge->update($data);

                // Rebuild rules from scratch.
                foreach ($badge->ruleGroups()->with('conditions')->get() as $group) {
                    $group->conditions()->forceDelete();
                    $group->forceDelete();
                }
            } else {
                $badge = Badge::create($data);
            }
        });

        // Trigger evaluasi retroaktif agar siswa yang sudah memenuhi kriteria langsung dapat
        $badgeId = $this->editingId ?? Badge::latest('id')->value('id');
        if ($badgeId) {
            app(BadgeEngineInterface::class)->evaluateBadgeForAllStudents($badgeId);
        }

        $this->resetForm();
        unset($this->badges);
        $this->dispatch('close-modal', 'badge-modal');
        $this->dispatch('notify', message: 'Badge berhasil disimpan.', type: 'success');
    }

    public function editBadge(int $id): void
    {
        $badge = Badge::findOrFail($id);

        $this->editingId = $badge->id;
        $this->formName = $badge->name;
        $this->formDescription = (string) $badge->description;
        $this->formIcon = null;

        $this->showForm = true;
        $this->dispatch('open-modal', 'badge-modal');
    }

    public function deleteBadge(int $id): void
    {
        $badge = Badge::find($id);

        if ($badge) {
            if ($badge->icon_url) {
                Storage::disk('public')->delete($badge->icon_url);
            }

            $badge->delete();
            unset($this->badges);
            $this->dispatch('notify', message: 'Badge dihapus.', type: 'success');
        }
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->dispatch('close-modal', 'badge-modal');
    }

    private function resetForm(): void
    {
        $this->reset('showForm', 'editingId', 'formName', 'formDescription', 'formIcon');
    }

    public function openManualModal(): void
    {
        $this->reset('manualStudentId', 'manualBadgeId');
        $this->dispatch('open-modal', 'manual-badge-modal');
    }

    public function closeManualModal(): void
    {
        $this->reset('manualStudentId', 'manualBadgeId');
        $this->dispatch('close-modal', 'manual-badge-modal');
    }

    public function assignManualBadge(): void
    {
        $this->validate([
            'manualStudentId' => ['required', 'exists:student_profiles,id'],
            'manualBadgeId' => ['required', 'exists:badges,id'],
        ], [
            'manualStudentId.required' => 'Pilih siswa terlebih dahulu.',
            'manualBadgeId.required' => 'Pilih badge terlebih dahulu.',
        ]);

        app(BadgeEngineInterface::class)->grantBadge($this->manualBadgeId, $this->manualStudentId);

        $this->closeManualModal();
        unset($this->badges); // refresh counts
        $this->dispatch('notify', message: 'Badge berhasil diberikan secara manual.', type: 'success');
    }

    public function render()
    {
        return view('livewire.admin.badge-builder');
    }
}
