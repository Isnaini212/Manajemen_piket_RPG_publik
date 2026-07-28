<?php

namespace App\Livewire\Student;

use App\Enums\UserRole;
use App\Enums\VerifyStatus;
use App\Models\DutyClaim;
use App\Models\Notification;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class UploadProof extends Component
{
    use WithFileUploads;

    public int $claimId;

    #[Validate('required|image|max:5120|mimes:jpg,jpeg,png,webp')]
    public $photo;

    public ?DutyClaim $claim = null;

    public ?Submission $existingSubmission = null;

    public function mount(int $claimId): void
    {
        $this->claimId = $claimId;

        $this->claim = DutyClaim::with(['dutySlot', 'student'])->findOrFail($claimId);

        // Ownership guard: the claim must belong to the current student.
        abort_unless($this->claim->student?->user_id === auth()->id(), 403);

        $this->loadSubmission();
    }

    private function loadSubmission(): void
    {
        $this->existingSubmission = Submission::with('histories')
            ->where('duty_claim_id', $this->claimId)
            ->latest('id')
            ->first();
    }

    public function updatedPhoto(): void
    {
        try {
            $this->validateOnly('photo', [
                'photo' => 'required|image|max:5120|mimes:jpg,jpeg,png,webp',
            ], [
                'photo.mimes' => 'Format file tidak didukung! Bukti piket hanya boleh berformat JPG, JPEG, PNG, atau WEBP (GIF/Animasi tidak diperbolehkan).',
                'photo.image' => 'File yang dipilih bukan berupa gambar yang valid.',
                'photo.max' => 'Ukuran foto terlalu besar! Maksimal ukuran foto adalah 5MB.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $message = $e->validator->errors()->first('photo');
            $this->reset('photo');
            $this->dispatch('notify', message: $message, type: 'error');
        }
    }

    public function submit(): void
    {
        try {
            $this->validate([
                'photo' => 'required|image|max:5120|mimes:jpg,jpeg,png,webp',
            ], [
                'photo.mimes'    => 'Format file tidak didukung! Bukti piket hanya boleh berformat JPG, JPEG, PNG, atau WEBP (GIF/Animasi tidak diperbolehkan).',
                'photo.image'    => 'File yang dipilih bukan berupa gambar yang valid.',
                'photo.max'      => 'Ukuran foto terlalu besar! Maksimal ukuran foto adalah 5MB.',
                'photo.required' => 'Silakan pilih foto bukti terlebih dahulu.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $message = $e->validator->errors()->first('photo');
            $this->dispatch('notify', message: $message, type: 'error');
            throw $e;
        }

        $sub = $this->existingSubmission;

        // Block permanently-failed proofs.
        if ($sub && $sub->verify_status === VerifyStatus::RejectedFinal) {
            $this->dispatch('notify', message: 'Bukti sudah ditolak final.', type: 'error');

            return;
        }

        // Block approved proofs.
        if ($sub && $sub->verify_status === VerifyStatus::Approved) {
            $this->dispatch('notify', message: 'Bukti sudah disetujui oleh admin, tidak bisa diubah.', type: 'error');

            return;
        }

        // Tentukan jenis operasi.
        $isPendingEdit = $sub && $sub->verify_status === VerifyStatus::Pending;
        $isResubmit    = $sub && $sub->verify_status === VerifyStatus::Rejected;

        $activeReplacement = null;

        // Pengecekan window upload DILEWATI untuk pending edit:
        // bukti sudah pernah dikirim, siswa hanya memperbaiki foto sebelum admin verifikasi.
        if (! $isPendingEdit) {
            $activeReplacement = \App\Models\ReplacementDuty::where('original_claim_id', $this->claimId)
                ->where('status', \App\Enums\ReplacementStatus::OFFERED)
                ->first();

            if ($activeReplacement) {
                if ($activeReplacement->isExpired() || now()->gt($activeReplacement->deadline)) {
                    $this->dispatch('notify', message: 'Batas waktu piket pengganti sudah habis.', type: 'error');

                    return;
                }
            } else {
                $dutyDate = $this->claim?->dutySlot?->duty_date;

                if (! $dutyDate) {
                    $this->dispatch('notify', message: 'Jadwal tidak valid.', type: 'error');

                    return;
                }

                if (! Carbon::parse($dutyDate)->isToday()) {
                    $message = Carbon::parse($dutyDate)->isFuture()
                        ? 'Belum saatnya mengunggah bukti piket untuk jadwal ini.'
                        : 'Batas waktu mengunggah bukti piket sudah lewat. Silakan cek menu piket pengganti.';
                    $this->dispatch('notify', message: $message, type: 'error');

                    return;
                }
            }
        }

        // Kompresi & konversi foto ke WebP menggunakan Intervention Image.
        // - orient():    memperbaiki rotasi EXIF dari kamera HP agar tidak terbalik.
        // - scaleDown(): memperkecil resolusi proporsional ke maks lebar 1200px.
        // - encode():    mengompresi dengan kualitas 80% ke format WebP.
        $manager = new ImageManager(new Driver());
        $image   = $manager->decode($this->photo->getRealPath())
                            ->orient()
                            ->scaleDown(1200);

        $dir         = 'submissions/' . auth()->id();
        $storagePath = \Illuminate\Support\Facades\Storage::disk('public')->path($dir);
        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $filename = uniqid('proof_', true) . '.webp';
        $image->encode(new \Intervention\Image\Encoders\WebpEncoder(80))
              ->save($storagePath . '/' . $filename);
        $path = $dir . '/' . $filename;

        if ($isPendingEdit) {
            // Edit foto sebelum diverifikasi admin — hanya update URL foto & waktu upload.
            // resubmit_count tidak naik, karena ini bukan reject dari admin.
            // NOTE: File lama tidak dihapus agar histori tetap ada.
            $sub->update([
                'proof_url'   => $path,
                'uploaded_at' => now(),
            ]);
        } elseif ($isResubmit) {
            // NOTE: File lama TIDAK dihapus agar histori penolakan
            // di panel Admin tetap bisa dibuka (mencegah error 403).
            $sub->update([
                'proof_url'      => $path,
                'verify_status'  => VerifyStatus::Pending,
                'uploaded_at'    => now(),
                'resubmit_count' => $sub->resubmit_count + 1,
                'replacement_id' => $activeReplacement?->id,
            ]);
        } else {
            Submission::create([
                'duty_claim_id'  => $this->claimId,
                'replacement_id' => $activeReplacement?->id,
                'proof_url'      => $path,
                'verify_status'  => VerifyStatus::Pending,
                'uploaded_at'    => now(),
                'resubmit_count' => 0,
            ]);
        }

        $this->reset('photo');
        $this->loadSubmission();

        // Kirim notifikasi ke admin hanya untuk upload baru dan resubmit (bukan pending edit).
        if (! $isPendingEdit) {
            $admins           = User::where('role', UserRole::Admin)->get();
            $studentName      = auth()->user()->name;
            $dutyDate         = $this->claim?->dutySlot?->duty_date;
            $dutyDateFormatted = $dutyDate
                ? Carbon::parse($dutyDate)->locale('id')->isoFormat('dddd, DD MMMM YYYY')
                : '';
            $notifMessage = $isResubmit
                ? "{$studentName} mengunggah ulang bukti piket untuk tanggal {$dutyDateFormatted}."
                : "{$studentName} mengunggah bukti piket untuk tanggal {$dutyDateFormatted}.";

            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type'    => 'piket_submission',
                    'message' => $notifMessage,
                ]);
            }
        }

        $this->dispatch('proof-uploaded');
        $successMsg = $isPendingEdit ? 'Foto bukti berhasil diperbarui!' : 'Bukti berhasil diupload!';
        $this->dispatch('notify', message: $successMsg, type: 'success');
    }

    public function render()
    {
        return view('livewire.student.upload-proof');
    }
}
