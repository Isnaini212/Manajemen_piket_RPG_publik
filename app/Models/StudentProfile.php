<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saved(function (StudentProfile $profile) {
            // Automatically switch to convict if lives reach 0 (e.g. from manual database edit or penalty)
            if ($profile->wasChanged('lives') && $profile->lives <= 0 && ! $profile->isConvict()) {
                app(\App\Services\Contracts\StatusServiceInterface::class)->changeToConvict($profile);
            }

            // Automatically switch back to citizen if lives increase > 0 while convict
            if ($profile->wasChanged('lives') && $profile->lives > 0 && $profile->isConvict()) {
                $profile->updateQuietly([
                    'status' => \App\Enums\StudentStatus::CITIZEN,
                    'status_since' => null,
                ]);

                $profile->statusChangeLogs()
                    ->whereNull('resolved_at')
                    ->update(['resolved_at' => now()]);

                \App\Models\Notification::create([
                    'user_id' => $profile->user_id,
                    'type' => 'status_recovered',
                    'message' => 'Status kamu dipulihkan menjadi CITIZEN karena nyawa telah bertambah.',
                ]);
            }
        });
    }

    protected $fillable = [
        'user_id',
        'xp',
        'lives',
        'status',
        'status_since',
        'profile_picture',
    ];

    protected function casts(): array
    {
        return [
            'xp' => 'integer',
            'lives' => 'integer',
            'status' => StudentStatus::class,
            'status_since' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Avatar Helper
    |--------------------------------------------------------------------------
    */

    /**
     * Get the URL for this student's avatar (uploaded image or a default placeholder).
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->profile_picture) {
            return \Illuminate\Support\Facades\Storage::url($this->profile_picture);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->user?->name ?? 'S') . '&background=2a2a4a&color=f5c518&size=128&bold=true';
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dutyClaims(): HasMany
    {
        return $this->hasMany(DutyClaim::class, 'student_id');
    }

    public function xpLogs(): HasMany
    {
        return $this->hasMany(XpLog::class);
    }

    public function lifeLogs(): HasMany
    {
        return $this->hasMany(LifeLog::class);
    }

    public function statusChangeLogs(): HasMany
    {
        return $this->hasMany(StatusChangeLog::class);
    }

    public function studentBadges(): HasMany
    {
        return $this->hasMany(StudentBadge::class);
    }

    /**
     * Swap requests targeting this student.
     */
    public function swapRequestsReceived(): HasMany
    {
        return $this->hasMany(SwapRequest::class, 'to_student_id');
    }

    /**
     * Swap requests initiated from this student's duty claims.
     */
    public function swapRequestsSent(): HasManyThrough
    {
        return $this->hasManyThrough(
            SwapRequest::class,
            DutyClaim::class,
            'student_id',    // FK on duty_claims -> student_profiles.id
            'from_claim_id', // FK on swap_requests -> duty_claims.id
            'id',            // local key on student_profiles
            'id',            // local key on duty_claims
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Whether the student is currently a "convict".
     */
    public function isConvict(): bool
    {
        return $this->status === StudentStatus::CONVICT;
    }

    /**
     * Add XP and record it in the XP log.
     */
    public function addXp(int $amount, string $reason): void
    {
        $this->increment('xp', $amount);

        $this->xpLogs()->create([
            'amount' => $amount,
            'reason' => $reason,
        ]);
    }

    /**
     * Reduce lives (clamped at 0) and record it in the life log.
     */
    public function reduceLives(int $amount, string $reason): void
    {
        $amount = abs($amount);
        $newLives = max(0, $this->lives - $amount);
        $applied = $this->lives - $newLives;

        $this->update(['lives' => $newLives]);

        $this->lifeLogs()->create([
            'change' => -$applied,
            'reason' => $reason,
        ]);
    }

    /**
     * Partially restore lives without exceeding the configured maximum.
     */
    public function increaseLivesPartially(int $amount, string $reason = 'Pemulihan nyawa'): void
    {
        $max = (int) SystemConfig::get('lives_max', 3);
        $newLives = min($max, $this->lives + abs($amount));
        $applied = $newLives - $this->lives;

        if ($applied <= 0) {
            return;
        }

        $this->update(['lives' => $newLives]);

        $this->lifeLogs()->create([
            'change' => $applied,
            'reason' => $reason,
        ]);
    }

    /**
     * Change the student's status, stamping the time and logging the change
     * against the currently active semester.
     */
    public function changeStatus(StudentStatus $newStatus): void
    {
        $from = $this->status;

        if ($from === $newStatus) {
            return;
        }

        $this->update([
            'status' => $newStatus,
            'status_since' => now(),
        ]);

        $semester = Semester::where('is_active', true)->first();

        if (! $semester) {
            return;
        }

        $deadline = null;
        if ($newStatus === StudentStatus::CONVICT) {
            $weeks = (int) (SystemConfig::get('redemption_period_weeks') ?? 1);
            $deadline = now()->addWeeks($weeks);
        }

        $this->statusChangeLogs()->create([
            'semester_id' => $semester->id,
            'from_status' => $from,
            'to_status' => $newStatus,
            'redemption_deadline' => $deadline,
        ]);
    }

    /**
     * Scope to order students for the leaderboard:
     * Citizens first, then Convicts, then order by XP descending.
     */
    public function scopeOrderByLeaderboard($query)
    {
        return $query->orderByRaw("CASE WHEN status = 'citizen' THEN 1 ELSE 2 END ASC")
                     ->orderByDesc('xp')
                     ->orderBy('id', 'asc');
    }
}
