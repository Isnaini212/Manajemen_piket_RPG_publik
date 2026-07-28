<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DutySlotStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDutySlotRequest;
use App\Models\DutySlot;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DutySlotController extends Controller
{
    /**
     * List duty slots for the active semester, grouped by week of year.
     */
    public function index()
    {
        $this->authorize('viewAny', DutySlot::class);

        $semester = Semester::where('is_active', true)->first();

        $slots = $semester
            ? DutySlot::where('semester_id', $semester->id)
                ->with('claims.student.user')
                ->orderBy('duty_date')
                ->get()
            : collect();

        $slotsByWeek = $slots->groupBy(
            fn (DutySlot $slot) => Carbon::parse($slot->duty_date)->weekOfYear,
        );

        return view('admin.duty-slots.index', compact('slotsByWeek', 'semester'));
    }

    /**
     * Create a new duty slot in the active semester.
     */
    public function store(StoreDutySlotRequest $request)
    {
        $semester = Semester::where('is_active', true)->first();

        if (! $semester) {
            return back()->withErrors(['duty_date' => 'Tidak ada semester aktif.']);
        }

        $exists = DutySlot::where('semester_id', $semester->id)
            ->whereDate('duty_date', $request->date('duty_date'))
            ->exists();

        if ($exists) {
            return back()->withErrors(['duty_date' => 'Slot untuk tanggal ini sudah ada']);
        }

        DutySlot::create([
            'semester_id' => $semester->id,
            'duty_date' => $request->date('duty_date'),
            'quota' => $request->integer('quota'),
            'status' => DutySlotStatus::Open,
        ]);

        return redirect()->back()->with('success', 'Slot piket berhasil ditambahkan');
    }

    /**
     * Update a slot's quota (cannot drop below the number of existing claims).
     */
    public function update(Request $request, DutySlot $dutySlot)
    {
        $this->authorize('update', $dutySlot);

        $minQuota = $dutySlot->claims()->count();

        $request->validate([
            'quota' => ['required', 'integer', 'min:1'],
        ]);

        if ($request->integer('quota') < $minQuota) {
            return back()->withErrors([
                'quota' => "Kuota tidak boleh kurang dari jumlah klaim yang sudah ada ({$minQuota})",
            ]);
        }

        $dutySlot->update(['quota' => $request->integer('quota')]);

        return redirect()->back()->with('success', 'Kuota berhasil diupdate');
    }

    /**
     * Delete a slot that has no claims yet.
     */
    public function destroy(DutySlot $dutySlot)
    {
        $this->authorize('delete', $dutySlot);

        if ($dutySlot->claims()->exists()) {
            return back()->withErrors(['error' => 'Slot tidak bisa dihapus karena sudah ada klaim']);
        }

        $dutySlot->delete();

        return redirect()->back()->with('success', 'Slot berhasil dihapus');
    }
}
