<?php

namespace App\Models;

use App\Enums\ReplacementStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReplacementDuty extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'original_claim_id',
        'deadline',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
            'status' => ReplacementStatus::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function originalClaim(): BelongsTo
    {
        return $this->belongsTo(DutyClaim::class, 'original_claim_id');
    }

    public function submission(): HasOne
    {
        return $this->hasOne(Submission::class, 'replacement_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Whether the deadline has passed without completion.
     */
    public function isExpired(): bool
    {
        if ($this->status === ReplacementStatus::COMPLETED) {
            return false;
        }

        return $this->deadline !== null && $this->deadline->isPast();
    }
}
