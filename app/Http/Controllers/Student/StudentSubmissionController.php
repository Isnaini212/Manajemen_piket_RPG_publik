<?php

namespace App\Http\Controllers\Student;

use App\Enums\ClaimStatus;
use App\Enums\UserRole;
use App\Enums\VerifyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreSubmissionRequest;
use App\Models\DutyClaim;
use App\Models\Notification;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentSubmissionController extends Controller
{
    /**
     * Upload the proof for a claimed duty.
     */
    public function store(StoreSubmissionRequest $request)
    {
        $profile = auth()->user()->studentProfile;

        $claim = DutyClaim::with(['dutySlot', 'student'])->findOrFail($request->integer('duty_claim_id'));

        // Must belong to the current student.
        if (! $profile || $claim->student_id !== $profile->id) {
            abort(403);
        }

        // The claim must still be awaiting a submission (spec's 'claimed').
        if ($claim->status !== ClaimStatus::Pending) {
            return back()->withErrors(['error' => 'Klaim ini tidak bisa disubmit.']);
        }

        $activeReplacement = \App\Models\ReplacementDuty::where('original_claim_id', $claim->id)
            ->where('status', \App\Enums\ReplacementStatus::OFFERED)
            ->first();

        if ($activeReplacement) {
            if ($activeReplacement->isExpired() || now()->gt($activeReplacement->deadline)) {
                return back()->withErrors(['error' => 'Batas waktu piket pengganti sudah habis.']);
            }
        } else {
            $dutyDate = $claim->dutySlot?->duty_date;
            if (! $dutyDate) {
                return back()->withErrors(['error' => 'Jadwal tidak valid.']);
            }

            if (! Carbon::parse($dutyDate)->isToday()) {
                $message = Carbon::parse($dutyDate)->isFuture()
                    ? 'Belum saatnya mengunggah bukti piket untuk jadwal ini.'
                    : 'Batas waktu mengunggah bukti piket sudah lewat. Silakan cek menu piket pengganti.';
                return back()->withErrors(['error' => $message]);
            }
        }

        $path = $request->file('proof_file')->store('submissions/' . auth()->id(), 'public');

        DB::transaction(function () use ($claim, $path, $activeReplacement): void {
            Submission::create([
                'duty_claim_id' => $claim->id,
                'replacement_id' => $activeReplacement ? $activeReplacement->id : null,
                'proof_url' => $path,
                'verify_status' => VerifyStatus::Pending,
                'uploaded_at' => now(),
                'resubmit_count' => 0,
            ]);

            // The claim stays Pending: proof uploaded, awaiting admin verify
            // (spec's 'pending_submission' maps to ClaimStatus::Pending).
            $claim->update(['status' => ClaimStatus::Pending]);
        });

        // Create notifications for all admins
        $admins = User::where('role', UserRole::Admin)->get();
        $studentName = auth()->user()->name;
        $dutyDate = $claim->dutySlot?->duty_date;
        $dutyDateFormatted = $dutyDate ? Carbon::parse($dutyDate)->locale('id')->isoFormat('dddd, DD MMMM YYYY') : '';
        $message = "{$studentName} mengunggah bukti piket untuk tanggal {$dutyDateFormatted}.";

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'piket_submission',
                'message' => $message,
            ]);
        }

        return redirect()->back()->with('success', 'Bukti berhasil diupload!');
    }

    /**
     * Re-upload proof after a (non-final) rejection, within the resubmit window.
     */
    public function resubmit(Request $request, Submission $submission)
    {
        $submission->load(['dutyClaim.student.user', 'dutyClaim.dutySlot']);

        // Ownership check via the claim's student.
        if ($submission->dutyClaim?->student?->user_id !== auth()->id()) {
            abort(403);
        }

        // Only a plain rejection can be resubmitted (not a final rejection).
        if ($submission->verify_status !== VerifyStatus::Rejected) {
            return back()->withErrors(['error' => 'Bukti ini tidak bisa diupload ulang.']);
        }

        $claim = $submission->dutyClaim;
        $activeReplacement = \App\Models\ReplacementDuty::where('original_claim_id', $claim->id)
            ->where('status', \App\Enums\ReplacementStatus::OFFERED)
            ->first();

        if ($activeReplacement) {
            if ($activeReplacement->isExpired() || now()->gt($activeReplacement->deadline)) {
                return back()->withErrors(['error' => 'Batas waktu piket pengganti sudah habis.']);
            }
        } else {
            $dutyDate = $claim->dutySlot?->duty_date;
            if (! $dutyDate) {
                return back()->withErrors(['error' => 'Jadwal tidak valid.']);
            }

            if (! Carbon::parse($dutyDate)->isToday()) {
                $message = Carbon::parse($dutyDate)->isFuture()
                    ? 'Belum saatnya mengunggah bukti piket untuk jadwal ini.'
                    : 'Batas waktu mengunggah bukti piket sudah lewat. Silakan cek menu piket pengganti.';
                return back()->withErrors(['error' => $message]);
            }
        }

        $request->validate([
            'proof_file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        // Note: we do NOT delete the old file here.
        // The old proof_url is archived in submission_histories by the reject() model method,
        // and the file will be cleaned up automatically when the submission is finally approved.

        $path = $request->file('proof_file')->store('submissions/' . auth()->id(), 'public');

        $submission->update([
            'proof_url' => $path,
            'verify_status' => VerifyStatus::Pending,
            'uploaded_at' => now(),
            'resubmit_count' => $submission->resubmit_count + 1,
            'replacement_id' => $activeReplacement ? $activeReplacement->id : null,
        ]);

        // Create notifications for all admins
        $admins = User::where('role', UserRole::Admin)->get();
        $studentName = auth()->user()->name;
        $dutyDate = $submission->dutyClaim?->dutySlot?->duty_date;
        $dutyDateFormatted = $dutyDate ? Carbon::parse($dutyDate)->locale('id')->isoFormat('dddd, DD MMMM YYYY') : '';
        $message = "{$studentName} mengunggah ulang bukti piket untuk tanggal {$dutyDateFormatted}.";

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'piket_submission',
                'message' => $message,
            ]);
        }

        return redirect()->back()->with('success', 'Bukti berhasil diupload ulang!');
    }
}
