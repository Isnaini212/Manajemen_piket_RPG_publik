<?php

use App\Enums\UserRole;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Student;
use App\Livewire;

// Landing page publik (atau redirect ke dashboard bila sudah login)
Route::get('/', function () {
    $leaderboard = \App\Models\StudentProfile::query()
        ->with('user')
        ->withCount(['dutyClaims as piket_count' => fn ($q) => $q->where('status', \App\Enums\ClaimStatus::Approved->value)])
        ->orderByLeaderboard()
        ->limit(10)
        ->get();

    $stats = [
        'petualang' => \App\Models\User::where('role', UserRole::Siswa)->count(),
        'xp' => (int) \App\Models\StudentProfile::sum('xp'),
        'piket' => \App\Models\DutyClaim::where('status', \App\Enums\ClaimStatus::Approved->value)->count(),
        'badge' => \App\Models\Badge::count(),
    ];

    $convictVisible = filter_var(\App\Models\SystemConfig::get('convict_status_visible'), FILTER_VALIDATE_BOOLEAN);

    return view('landing', compact('leaderboard', 'stats', 'convictVisible'));
})->name('home');

// Admin routes
Route::prefix('admin')->middleware(['auth', 'role:admin', 'check.email.verified'])->name('admin.')->group(function () {
    Route::get('/dashboard', Livewire\Admin\AdminDashboard::class)->name('dashboard');

    // Jadwal piket & verifikasi kini ditangani penuh oleh komponen Livewire.
    Route::get('duty-slots', Livewire\Admin\DutySchedule::class)->name('duty-slots.index');
    Route::get('submissions', Livewire\Admin\SubmissionVerification::class)->name('submissions.index');

    Route::get('swap-logs', [Admin\AdminSwapLogController::class, 'index'])->name('swap-logs.index');

    // Konfigurasi & badge builder via Livewire.
    Route::get('config', Livewire\Admin\ConfigPanel::class)->name('config.index');
    Route::get('badges', Livewire\Admin\BadgeBuilder::class)->name('badges.index');

    Route::get('semesters', [Admin\SemesterController::class, 'index'])->name('semesters.index');
    Route::post('semesters', [Admin\SemesterController::class, 'store'])->name('semesters.store');
    Route::post('semesters/{semester}/reset', [Admin\SemesterController::class, 'triggerReset'])->name('semesters.reset');
    Route::delete('semesters/{semester}', [Admin\SemesterController::class, 'destroy'])->name('semesters.destroy');
    Route::post('run-command/{command}', function (string $command) {
        $allowed = [
            'check-missed' => 'piket:check-missed',
            'check-replacement' => 'piket:check-replacement-expiry',
            'check-redemption' => 'piket:check-redemption-expiry',
        ];
        if (isset($allowed[$command])) {
            \Illuminate\Support\Facades\Artisan::call($allowed[$command]);

            return back()->with('success', 'Command berhasil dijalankan');
        }
        abort(403);
    })->name('run-command');

    Route::get('students', Livewire\Admin\StudentList::class)->name('students.index');
    Route::get('recap', Livewire\Admin\MonthlyRecap::class)->name('recap.index');
});

// Student routes
Route::prefix('student')->middleware(['auth', 'role:siswa', 'ensure.profile', 'check.email.verified'])->name('student.')->group(function () {
    Route::get('/dashboard', Livewire\Student\Dashboard::class)->name('dashboard');
    Route::get('missions', Livewire\Student\MissionList::class)->name('missions');
    Route::post('missions/claim', [Student\StudentDutyController::class, 'store'])->name('missions.claim');
    Route::get('swap', Livewire\Student\SwapRequest::class)->name('swap');
    Route::post('swap', [Student\StudentSwapController::class, 'store'])->name('swap.store');
    Route::patch('swap/{swapRequest}/respond', [Student\StudentSwapController::class, 'respond'])->name('swap.respond');
    Route::post('submissions', [Student\StudentSubmissionController::class, 'store'])->name('submissions.store');
    Route::post('submissions/{submission}/resubmit', [Student\StudentSubmissionController::class, 'resubmit'])->name('submissions.resubmit');
    Route::get('badges', Livewire\Student\Badges::class)->name('badges');
    Route::get('profile', Livewire\Student\Profile::class)->name('profile');
});

// Shared routes (semua user terautentikasi)
Route::middleware(['auth'])->group(function () {
    Route::get('/leaderboard', Livewire\Leaderboard::class)->name('leaderboard');

    // Halaman profil bawaan Breeze (ubah nama, password, dll).
    Route::view('/profile', 'profile')->name('profile');

    // Logout (stack Breeze+Livewire tidak menyediakan route ini secara default).
    Route::post('/logout', function (\Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
});

// Profil publik dihapus karena sekarang menggunakan pop-up (modal)

// Breeze authentication routes (login, register, logout, password, verification).
require __DIR__.'/auth.php';
