<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use App\Enums\ClaimType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class DutyClaim extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'duty_slot_id',
        'student_id',
        'claim_type',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'claim_type' => ClaimType::class,
            'status' => ClaimStatus::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function dutySlot(): BelongsTo
    {
        return $this->belongsTo(DutySlot::class);
    }

    /**
     * The student (profile) that owns this claim.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_id');
    }

    public function submission(): HasOne
    {
        return $this->hasOne(Submission::class);
    }

    public function swapRequests(): HasMany
    {
        return $this->hasMany(SwapRequest::class, 'from_claim_id');
    }

    public function replacementDuty(): HasOne
    {
        return $this->hasOne(ReplacementDuty::class, 'original_claim_id');
    }
}
