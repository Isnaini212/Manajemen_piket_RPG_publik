<?php

namespace App\Services;

use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\SystemConfig;
use App\Models\User;
use App\Services\Contracts\StatusServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatusService implements StatusServiceInterface
{
    /**
     * Transition a student from Citizen to Convict: flag the profile, open a
     * redemption window, log the change, and notify the student + all admins.
     */
    public function changeToConvict(StudentProfile $profile): void
    {
        try {
            DB::transaction(function () use ($profile): void {
                $profile->update([
                    'status' => StudentStatus::CONVICT,
                    'status_since' => now(),
                ]);

                $weeks = (int) (SystemConfig::get('redemption_period_weeks') ?? 1);
                $deadline = now()->addWeeks($weeks);

                $profile->statusChangeLogs()->create([
                    'semester_id' => Semester::where('is_active', true)->first()?->id,
                    'from_status' => StudentStatus::CITIZEN,
                    'to_status' => StudentStatus::CONVICT,
                    'redemption_deadline' => $deadline,
                ]);

                // Notify the student.
                Notification::create([
                    'user_id' => $profile->user_id,
                    'type' => 'status_changed',
                    'message' => 'Status kamu berubah ke CONVICT. Selesaikan misi hukuman untuk kembali ke Citizen.',
                ]);

                // Notify every admin.
                $studentName = $profile->user?->name ?? 'Siswa';

                User::where('role', UserRole::Admin)->get()->each(function (User $admin) use ($studentName): void {
                    Notification::create([
                        'user_id' => $admin->id,
                        'type' => 'status_changed',
                        'message' => "Siswa {$studentName} berubah status ke CONVICT.",
                    ]);
                });
            });

            Log::warning('Student changed to CONVICT', [
                'student_profile_id' => $profile->id,
                'user_id' => $profile->user_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal mengubah status ke CONVICT', [
                'student_profile_id' => $profile->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Transition a student from Convict back to Citizen: restore some lives
     * (capped at the maximum), resolve any open redemption logs, and notify.
     */
    public function changeBackToCitizen(StudentProfile $profile): void
    {
        try {
            DB::transaction(function () use ($profile): void {
                $livesMax = (int) (SystemConfig::get('lives_max') ?? 3);
                $recovery = (int) (SystemConfig::get('lives_on_recovery') ?? 1);
                $newLives = min($livesMax, $profile->lives + $recovery);

                $profile->update([
                    'status' => StudentStatus::CITIZEN,
                    'lives' => $newLives,
                    'status_since' => null,
                ]);

                // Close any still-open status change log for this student.
                $profile->statusChangeLogs()
                    ->whereNull('resolved_at')
                    ->update(['resolved_at' => now()]);

                Notification::create([
                    'user_id' => $profile->user_id,
                    'type' => 'status_recovered',
                    'message' => 'Selamat! Status kamu kembali ke CITIZEN. Nyawa bertambah +' . $recovery,
                ]);
            });

            Log::info('Student recovered to CITIZEN', [
                'student_profile_id' => $profile->id,
                'user_id' => $profile->user_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal mengembalikan status ke CITIZEN', [
                'student_profile_id' => $profile->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
