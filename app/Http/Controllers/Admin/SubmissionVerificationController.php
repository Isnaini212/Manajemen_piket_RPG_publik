<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClaimStatus;
use App\Enums\VerifyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifySubmissionRequest;
use App\Models\Notification;
use App\Models\Submission;
use App\Services\Contracts\PenaltyServiceInterface;
use App\Services\Contracts\RewardServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SubmissionVerificationController extends Controller
{
    public function __construct(
        private readonly RewardServiceInterface $rewardService,
        private readonly PenaltyServiceInterface $penaltyService,
    ) {}

    /**
     * List submissions filtered by verify status (default: pending).
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Submission::class);

        $filter = $request->input('filter', 'pending');

        $submissions = Submission::query()
            ->when($filter !== 'all', fn ($q) => $q->where('verify_status', $filter))
            ->with(['dutyClaim.student.user', 'dutyClaim.dutySlot'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.submissions.index', compact('submissions', 'filter'));
    }

    /**
     * Show a single submission for review.
     */
    public function show(Submission $submission)
    {
        $this->authorize('viewAny', Submission::class);

        $submission->load(['dutyClaim.student.user', 'dutyClaim.dutySlot']);

        return view('admin.submissions.show', compact('submission'));
    }

    /**
     * Apply an admin verification decision to a submission.
     */
    public function verify(VerifySubmissionRequest $request, Submission $submission)
    {
        $this->authorize('verify', Submission::class);

        $submission->load(['dutyClaim.student.user', 'dutyClaim.dutySlot']);
        $claim = $submission->dutyClaim;

        // Guard: a submission with no linked claim cannot be processed.
        if (! $claim) {
            return back()->withErrors(['error' => 'Submission tidak terhubung ke klaim piket.']);
        }

        // 'rejected' needs an early, non-transactional guard on the resubmit
        // window so we can return an error before mutating anything.
        if ($request->input('decision') === 'rejected') {
            $dutyDate = $claim->dutySlot?->duty_date;
            $window = $dutyDate ? Carbon::parse($dutyDate)->addDays(2)->endOfDay() : null;

            if ($window && now()->greaterThan($window)) {
                return back()->withErrors([
                    'decision' => 'Waktu resubmit sudah habis, gunakan Tolak Final',
                ]);
            }
        }

        try {
            DB::transaction(function () use ($request, $submission, $claim): void {
                switch ($request->input('decision')) {
                    case 'approved':
                        $submission->update(['verify_status' => VerifyStatus::Approved]);
                        $claim->update(['status' => ClaimStatus::Approved]);
                        $this->rewardService->grantReward($claim);
                        break;

                    case 'rejected':
                        $submission->update(['verify_status' => VerifyStatus::Rejected]);
                        $submission->increment('resubmit_count');

                        Notification::create([
                            'user_id' => $claim->student?->user_id,
                            'type' => 'resubmit_required',
                            'message' => 'Bukti piket kamu ditolak: '
                                . $request->input('rejection_reason')
                                . '. Silakan upload ulang.',
                        ]);
                        break;

                    case 'rejected_final':
                        $submission->update(['verify_status' => VerifyStatus::RejectedFinal]);
                        $claim->update(['status' => ClaimStatus::Failed]);
                        $this->penaltyService->triggerFailureFlow($claim);
                        break;
                }
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal memverifikasi submission', [
                'submission_id' => $submission->id,
                'decision' => $request->input('decision'),
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Terjadi kesalahan saat memproses verifikasi.']);
        }

        return redirect()->back()->with('success', 'Verifikasi berhasil');
    }
}
