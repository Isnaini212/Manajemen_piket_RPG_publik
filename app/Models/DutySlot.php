<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use App\Enums\DutySlotStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DutySlot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'semester_id',
        'duty_date',
        'quota',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'duty_date' => 'date',
            'quota' => 'integer',
            'status' => DutySlotStatus::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(DutyClaim::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Whether the slot still has room, counting only non-rejected claims.
     * A slot is considered available while it is Open (or legacy Aktif).
     */
    public function isQuotaAvailable(): bool
    {
        if (! in_array($this->status, [DutySlotStatus::Open, DutySlotStatus::Aktif], true)) {
            return false;
        }

        $taken = $this->claims()
            ->whereNotIn('status', [ClaimStatus::Failed->value])
            ->count();

        return $taken < $this->quota;
    }
}
