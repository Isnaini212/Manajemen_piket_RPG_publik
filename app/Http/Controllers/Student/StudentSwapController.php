<?php

namespace App\Http\Controllers\Student;

use App\Enums\SwapStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreSwapRequestRequest;
use App\Models\DutyClaim;
use App\Models\Notification;
use App\Models\SwapRequest;
use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StudentSwapController extends Controller
{
    /**
     * Show incoming swap requests and the student's own outgoing requests.
     */
    public function index()
    {
        $profile = auth()->user()->studentProfile;
        $profileId = $profile?->id;

        $incoming = SwapRequest::where('to_student_id', $profileId)
            ->where('status', SwapStatus::Pending)
            ->with(['fromClaim.dutySlot', 'fromClaim.student.user', 'toClaim.dutySlot'])
            ->latest()
            ->get();

        $mine = SwapRequest::whereHas('fromClaim', fn ($q) => $q->where('student_id', $profileId))
            ->with(['fromClaim.dutySlot', 'toStudent.user', 'toClaim.dutySlot'])
            ->latest()
            ->get();

        $swapUsed = $profileId ? SwapRequest::countThisMonth($profileId) : 0;
        $swapMax = (int) (SystemConfig::get('swap_limit_per_month') ?? 2);

        return view('student.swaps.index', compact('incoming', 'mine', 'swapUsed', 'swapMax', 'profile'));
    }

    /**
     * Open a new swap request against another student.
     */
    public function store(StoreSwapRequestRequest $request)
    {
        $profile = auth()->user()->studentProfile;

        $fromClaim = DutyClaim::with('dutySlot')->findOrFail($request->integer('from_claim_id'));

        $this->authorize('create', [SwapRequest::class, $fromClaim]);

        $swapMax = (int) (SystemConfig::get('swap_limit_per_month') ?? 2);

        if (SwapRequest::countThisMonth($profile->id) >= $swapMax) {
            return back()->withErrors(['error' => 'Limit tukar jadwal bulan ini sudah habis']);
        }

        // Resolve the target user to their student profile (to_student_id FK).
        $toUser = User::findOrFail($request->integer('to_student_id'));
        $toProfile = $toUser->studentProfile;

        if (! $toProfile) {
            return back()->withErrors(['to_student_id' => 'Siswa tujuan tidak memiliki profil.']);
        }

        $toClaimId = $request->integer('to_claim_id', null);
        $toClaim = null;

        if ($toClaimId) {
            $toClaim = DutyClaim::with('dutySlot')->findOrFail($toClaimId);

            if ($toClaim->student_id !== $toProfile->id) {
                return back()->withErrors(['to_claim_id' => 'Jadwal tujuan bukan milik siswa tersebut.']);
            }
        } else {
            // The target must have a claim in the same week as the source claim.
            $week = $this->weekOf($fromClaim->dutySlot?->duty_date);

            $targetHasSameWeek = $week && DutyClaim::where('student_id', $toProfile->id)
                ->whereHas('dutySlot', fn ($q) => $q
                    ->whereBetween('duty_date', [$week[0]->toDateString(), $week[1]->toDateString()]))
                ->exists();

            if (! $targetHasSameWeek) {
                return back()->withErrors(['to_student_id' => 'Siswa tujuan tidak punya jadwal di minggu yang sama']);
            }
        }

        $profile = auth()->user()->studentProfile;
        DB::transaction(function () use ($fromClaim, $toProfile, $toUser, $toClaimId, $profile): void {
            $data = [
                'from_student_id' => $profile->id,
                'from_claim_id' => $fromClaim->id,
                'to_student_id' => $toProfile->id,
                'status' => SwapStatus::Pending,
            ];

            if ($toClaimId) {
                $data['to_claim_id'] = $toClaimId;
            }

            SwapRequest::create($data);

            Notification::create([
                'user_id' => $toUser->id,
                'type' => 'swap_request',
                'message' => auth()->user()->name . ' mengajukan tukar jadwal piket dengan kamu.',
            ]);
        });

        return redirect()->back()->with('success', 'Request tukar berhasil dikirim');
    }

    /**
     * Accept or reject an incoming swap request.
     */
    public function respond(Request $request, SwapRequest $swapRequest)
    {
        $this->authorize('respond', $swapRequest);

        if ($swapRequest->status !== SwapStatus::Pending) {
            return back()->withErrors(['error' => 'Request ini sudah direspon.']);
        }

        $request->validate([
            'decision' => ['required', 'in:accepted,rejected'],
        ]);

        $swapRequest->load(['fromClaim.dutySlot', 'fromClaim.student.user', 'toClaim.dutySlot']);
        $fromClaim = $swapRequest->fromClaim;
        $toClaim = $swapRequest->toClaim;
        $fromUser = $fromClaim?->student?->user;
        $responderName = auth()->user()->name;

        if ($request->input('decision') === 'accepted') {
            DB::transaction(function () use ($swapRequest, $fromClaim, $toClaim, $fromUser, $responderName): void {
                if ($toClaim) {
                    // Tukar kedua claim
                    $tempStudentId = $fromClaim->student_id;
                    $fromClaim->update(['student_id' => $toClaim->student_id]);
                    $toClaim->update(['student_id' => $tempStudentId]);
                } else {
                    // Fallback: hanya pindahkan fromClaim ke responder
                    $fromClaim?->update(['student_id' => $swapRequest->to_student_id]);
                }

                $swapRequest->update([
                    'status' => SwapStatus::Accepted,
                    'responded_at' => now(),
                ]);

                $fromDate = optional($fromClaim?->dutySlot?->duty_date)->locale('id')->translatedFormat('d M Y');
                $toDate = optional($toClaim?->dutySlot?->duty_date)->locale('id')->translatedFormat('d M Y');
                $fromName = $fromUser?->name ?? 'Siswa';

                // Inform all admins.
                User::where('role', UserRole::Admin)->get()->each(function (User $admin) use ($fromName, $responderName, $fromDate, $toDate): void {
                    $message = $toDate
                        ? "{$fromName} dan {$responderName} menukar jadwal piket ({$fromDate} ↔ {$toDate})."
                        : "{$fromName} dan {$responderName} menukar jadwal piket {$fromDate}.";

                    Notification::create([
                        'user_id' => $admin->id,
                        'type' => 'swap_info',
                        'message' => $message,
                    ]);
                });

                // Inform the original requester.
                if ($fromUser) {
                    Notification::create([
                        'user_id' => $fromUser->id,
                        'type' => 'swap_accepted',
                        'message' => "{$responderName} menerima tukar jadwal kamu!",
                    ]);
                }
            });
        } else {
            DB::transaction(function () use ($swapRequest, $fromUser, $responderName): void {
                $swapRequest->update([
                    'status' => SwapStatus::Rejected,
                    'responded_at' => now(),
                ]);

                if ($fromUser) {
                    Notification::create([
                        'user_id' => $fromUser->id,
                        'type' => 'swap_rejected',
                        'message' => "{$responderName} menolak tukar jadwal kamu.",
                    ]);
                }
            });
        }

        return redirect()->back()->with('success', 'Berhasil');
    }

    /**
     * Monday–Sunday range containing the given date.
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    private function weekOf(mixed $date): ?array
    {
        if (! $date) {
            return null;
        }

        $carbon = Carbon::parse($date);

        return [
            $carbon->copy()->startOfWeek(Carbon::MONDAY),
            $carbon->copy()->endOfWeek(Carbon::SUNDAY),
        ];
    }
}
