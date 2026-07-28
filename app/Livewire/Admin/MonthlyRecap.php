<?php

namespace App\Livewire\Admin;

use App\Models\StudentProfile;
use App\Models\DutyClaim;
use App\Enums\ClaimStatus;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Rekap Bulanan')]
class MonthlyRecap extends Component
{
    public $month;
    public $year;

    public function mount()
    {
        $this->month = Carbon::now()->month;
        $this->year = Carbon::now()->year;
    }

    public function getYearsProperty()
    {
        $startYear = 2026;
        $currentYear = Carbon::now()->year;
        return range($startYear, max($currentYear, $startYear));
    }

    public function getMonthsProperty()
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
    }

    public function render()
    {
        $students = StudentProfile::with(['user'])->get()->map(function ($student) {
            $claims = DutyClaim::where('student_id', $student->id)
                ->whereHas('dutySlot', function ($query) {
                    $query->whereMonth('duty_date', $this->month)
                          ->whereYear('duty_date', $this->year);
                })->get();

            $total = $claims->count();
            $approved = $claims->where('status', ClaimStatus::Approved)->count();
            $failed = $claims->where('status', ClaimStatus::Failed)->count();
            $others = $total - ($approved + $failed);

            return [
                'name' => $student->user->name ?? '-',
                'email' => $student->user->email ?? '-',
                'total' => $total,
                'approved' => $approved,
                'failed' => $failed,
                'missed' => 0, // Hardcode to 0 for missed since it's removed
                'others' => $others
            ];
        })->sortByDesc('approved')->values();

        return view('livewire.admin.monthly-recap', [
            'students' => $students
        ]);
    }

    public function exportDomPDF()
    {
        $students = StudentProfile::with(['user'])->get()->map(function ($student) {
            $claims = DutyClaim::where('student_id', $student->id)
                ->whereHas('dutySlot', function ($query) {
                    $query->whereMonth('duty_date', $this->month)
                          ->whereYear('duty_date', $this->year);
                })->get();

            $total = $claims->count();
            $approved = $claims->where('status', ClaimStatus::Approved)->count();
            $failed = $claims->where('status', ClaimStatus::Failed)->count();
            $others = $total - ($approved + $failed);

            return [
                'name' => $student->user->name ?? '-',
                'email' => $student->user->email ?? '-',
                'total' => $total,
                'approved' => $approved,
                'failed' => $failed,
                'others' => $others
            ];
        })->sortByDesc('approved')->values();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.monthly-recap-dompdf', [
            'students' => $students,
            'monthName' => $this->months[$this->month],
            'year' => $this->year
        ])->setPaper('a4', 'portrait');

        $fileName = 'Rekap_Piket_' . $this->months[$this->month] . '_' . $this->year . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }
}
