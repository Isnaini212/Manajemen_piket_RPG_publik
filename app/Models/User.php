<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * The student profile that belongs to the user.
     */
    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    /**
     * Swap requests received by this user (as the targeted student).
     *
     * Note: the swap domain is keyed on `student_profiles`, so "received"
     * swaps resolve cleanly through the profile via `to_student_id`.
     */
    public function swapRequestsReceived(): HasManyThrough
    {
        return $this->hasManyThrough(
            SwapRequest::class,
            StudentProfile::class,
            'user_id',        // FK on student_profiles -> users.id
            'to_student_id',  // FK on swap_requests -> student_profiles.id
            'id',             // local key on users
            'id',             // local key on student_profiles
        );
    }

    /**
     * Swap requests sent by this user.
     *
     * Sent swaps chain User -> StudentProfile -> DutyClaim -> SwapRequest
     * (two intermediate tables), which `hasManyThrough` cannot express, so
     * this delegates to the profile-level relationship. Returns null only
     * when the user has no student profile (e.g. an admin).
     */
    public function swapRequestsSent(): ?HasManyThrough
    {
        return $this->studentProfile?->swapRequestsSent();
    }

    /**
     * Notifications addressed to this user.
     *
     * Overrides the Notifiable trait's relation because this project uses a
     * custom `notifications` table (type / message / is_read) rather than
     * Laravel's default database-notifications schema.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Determine whether the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Determine whether the user is a student.
     */
    public function isSiswa(): bool
    {
        return $this->role === UserRole::Siswa;
    }

    /**
     * Get the URL for the user's avatar.
     * Prioritizes custom uploaded picture for students, falls back to default UI Avatars.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->studentProfile?->profile_picture) {
            return \Illuminate\Support\Facades\Storage::url($this->studentProfile->profile_picture);
        }

        $color = $this->isAdmin() ? '7c3aed' : 'f5c518';
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name ?? 'U') . '&background=2a2a4a&color=' . $color . '&size=128&bold=true';
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $required = filter_var(\App\Models\SystemConfig::get('require_email_verification', 'false'), FILTER_VALIDATE_BOOLEAN);

        if ($required) {
            $this->notify(new \App\Notifications\CustomVerifyEmail);
        }
    }
}
