<?php

namespace App\Http\Controllers\Student;

use App\Enums\ClaimStatus;
use App\Enums\ClaimType;
use App\Http\Controllers\Controller;
use App\Models\DutyClaim;
use App\Models\DutySlot;
use App\Models\Semester;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StudentDutyController extends Controller
{
    /**
     * Show this week's available duty slots and the student's weekly progress.
     */
    public function index()
    {
        $semester = Semester::where('is_active', true)->first();
        $profile = auth()->user()->studentProfile;

        $weeklyQuota = $profile && $profile->isConvict()
            ? (int) (SystemConfig::get('convict_weekly_missions') ?? 3)
            : (int) (SystemConfig::get('citizen_weekly_missions') ?? 1);

        [$weekStart, $weekEnd] = $this->currentWeekRange();

        $slots = collect();
        $claimedCount = 0;

        if ($semester && $profile) {
            $slots = DutySlot::where('semester_id', $semester->id)
                ->whereBetween('duty_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->with(['claims.student.user']) // claims used for quota display/counting

                ->orderBy('duty_date')
                ->get();

            $claimedCount = DutyClaim::where('student_id', $profile->id)
                ->whereHas('dutySlot', fn ($q) => $q
                    ->where('semester_id', $semester->id)
                    ->whereBetween('duty_date', [$weekStart->toDateString(), $weekEnd->toDateString()]))
                ->count();
        }

        return view('student.missions.index', compact('slots', 'weeklyQuota', 'claimedCount', 'profile'));
    }

    /**
     * Claim a duty slot for the current week.
     */
    public function store(Request $request)
    {
        $this->authorize('create', DutyClaim::class);

        $request->validate([
            'duty_slot_id' => ['required', 'exists:duty_slots,id'],
        ]);

        $profile = auth()->user()->studentProfile;

        if (! $profile) {
            return back()->withErrors(['error' => 'Profil siswa tidak ditemukan.']);
        }

        $semester = Semester::where('is_active', true)->first();

        if (! $semester) {
            return back()->withErrors(['error' => 'Tidak ada semester aktif.']);
        }

        $slot = DutySlot::with('claims')->findOrFail($request->integer('duty_slot_id'));

        // Only the active semester's slots are claimable.
        if ($slot->semester_id !== $semester->id) {
            return back()->withErrors(['error' => 'Slot tidak berada di semester aktif']);
        }

        if (! $slot->isQuotaAvailable()) {
            return back()->withErrors(['error' => 'Kuota slot sudah penuh']);
        }

        // A student cannot claim the same slot twice.
        $alreadyClaimed = DutyClaim::where('duty_slot_id', $slot->id)
            ->where('student_id', $profile->id)
            ->exists();

        if ($alreadyClaimed) {
            return back()->withErrors(['error' => 'Kamu sudah mengklaim slot ini']);
        }

        // Enforce the weekly mission quota (citizen vs convict).
        [$weekStart, $weekEnd] = $this->currentWeekRange();

        $weeklyQuota = $profile->isConvict()
            ? (int) (SystemConfig::get('convict_weekly_missions') ?? 3)
            : (int) (SystemConfig::get('citizen_weekly_missions') ?? 1);

        $claimedThisWeek = DutyClaim::where('student_id', $profile->id)
            ->whereHas('dutySlot', fn ($q) => $q
                ->where('semester_id', $semester->id)
                ->whereBetween('duty_date', [$weekStart->toDateString(), $weekEnd->toDateString()]))
            ->count();

        if ($claimedThisWeek >= $weeklyQuota) {
            return back()->withErrors(['error' => 'Kuota misi wajib minggu ini sudah tercapai']);
        }

        $claimType = $profile->isConvict() ? ClaimType::PUNISHMENT : ClaimType::REGULAR;

        DB::transaction(function () use ($slot, $profile, $claimType): void {
            DutyClaim::create([
                'duty_slot_id' => $slot->id,
                'student_id' => $profile->id,
                'claim_type' => $claimType,
                // A fresh claim awaits verification -> Pending (spec's 'claimed').
                'status' => ClaimStatus::Pending,
            ]);
        });

        return redirect()->back()->with('success', 'Misi berhasil diklaim!');
    }

    /**
     * Monday–Sunday range for the current week.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function currentWeekRange(): array
    {
        return [
            now()->startOfWeek(Carbon::MONDAY),
            now()->endOfWeek(Carbon::SUNDAY),
        ];
    }
}
