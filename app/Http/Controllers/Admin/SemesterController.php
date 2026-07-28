<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSemesterRequest;
use App\Models\Semester;
use App\Services\Contracts\SemesterServiceInterface;
use Illuminate\Http\Request;

class SemesterController extends Controller
{
    public function __construct(
        private readonly SemesterServiceInterface $semesterService,
    ) {}

    /**
     * List all semesters, newest first.
     */
    public function index()
    {
        $semesters = Semester::orderByDesc('created_at')->get();

        return view('admin.semesters.index', compact('semesters'));
    }

    /**
     * Create a new semester and make it the active one.
     */
    public function store(StoreSemesterRequest $request)
    {
        Semester::where('is_active', true)->update(['is_active' => false]);

        Semester::create([
            'name' => $request->string('name'),
            'start_date' => $request->date('start_date'),
            'end_date' => $request->date('end_date'),
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Semester baru berhasil dibuat');
    }

    /**
     * Trigger a full semester reset (requires an explicit typed confirmation).
     */
    public function triggerReset(Request $request, Semester $semester)
    {
        if (! $semester->is_active) {
            return back()->withErrors(['error' => 'Reset hanya bisa dilakukan pada semester aktif.']);
        }

        $request->validate([
            'confirmation' => ['required', 'in:RESET'],
        ], [
            'confirmation.in' => 'Ketik RESET untuk konfirmasi.',
        ]);

        $this->semesterService->resetAll();

        return redirect()->route('admin.semesters.index')->with('success', 'Semester berhasil direset');
    }

    /**
     * Delete a semester.
     */
    public function destroy(Semester $semester)
    {
        $isActive = $semester->is_active;
        $semester->delete(); // Automatically soft deletes if soft deletes are used, or hard deletes. Duty slots should cascade or be handled.

        // If the deleted semester was active, make the latest available semester active.
        if ($isActive) {
            $latest = Semester::orderByDesc('created_at')->first();
            if ($latest) {
                $latest->update(['is_active' => true]);
            }
        }

        return redirect()->route('admin.semesters.index')->with('success', 'Semester berhasil dihapus');
    }
}
