<?php

namespace App\Models;

use App\Enums\SwapStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class SwapRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'from_student_id',
        'from_claim_id',
        'to_claim_id',
        'to_student_id',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SwapStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function fromClaim(): BelongsTo
    {
        return $this->belongsTo(DutyClaim::class, 'from_claim_id');
    }

    public function toClaim(): BelongsTo
    {
        return $this->belongsTo(DutyClaim::class, 'to_claim_id');
    }

    public function fromStudent(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'from_student_id');
    }

    /**
     * The student (profile) the swap is offered to.
     */
    public function toStudent(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'to_student_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Count swaps a student initiated in the current calendar month, used to
     * enforce the `swap_limit_per_month` config. Counts only ACCEPTED swaps.
     */
    public static function countThisMonth(int $studentId): int
    {
        // Hitung hanya swap yang DIJALANKAN (from_student_id) dan di-accept
        return static::where('from_student_id', $studentId)
            ->where('status', SwapStatus::Accepted)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
    }

    /**
     * Accept the swap: reassign the originating claim to the target student.
     */
    public function accept(): void
    {
        // Keep for backwards compatibility, but the main logic is in Livewire component
        DB::transaction(function () {
            $this->update([
                'status' => SwapStatus::Accepted,
                'responded_at' => now(),
            ]);

            if ($this->toClaim) {
                // Tukar kedua claim jika ada toClaim
                $tempStudentId = $this->fromClaim->student_id;
                $this->fromClaim->update(['student_id' => $this->toClaim->student_id]);
                $this->toClaim->update(['student_id' => $tempStudentId]);
            } else {
                // Fallback untuk request lama tanpa toClaim
                $this->fromClaim?->update(['student_id' => $this->to_student_id]);
            }
        });
    }

    /**
     * Reject the swap.
     */
    public function reject(): void
    {
        $this->update([
            'status' => SwapStatus::Rejected,
            'responded_at' => now(),
        ]);
    }
}
