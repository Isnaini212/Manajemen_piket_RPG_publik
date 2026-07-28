<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusChangeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_profile_id',
        'semester_id',
        'from_status',
        'to_status',
        'redemption_deadline',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => StudentStatus::class,
            'to_status' => StudentStatus::class,
            'redemption_deadline' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Whether the redemption window has lapsed without being resolved.
     */
    public function isRedemptionExpired(): bool
    {
        if ($this->resolved_at !== null) {
            return false;
        }

        return $this->redemption_deadline !== null && $this->redemption_deadline->isPast();
    }
}
