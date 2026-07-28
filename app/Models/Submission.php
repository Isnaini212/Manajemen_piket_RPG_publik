<?php

namespace App\Models;

use App\Enums\ReplacementStatus;
use App\Enums\VerifyStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Submission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'duty_claim_id',
        'replacement_id',
        'proof_url',
        'uploaded_at',
        'verify_status',
        'resubmit_count',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'verify_status' => VerifyStatus::class,
            'resubmit_count' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function dutyClaim(): BelongsTo
    {
        return $this->belongsTo(DutyClaim::class);
    }

    public function replacementDuty(): BelongsTo
    {
        return $this->belongsTo(ReplacementDuty::class, 'replacement_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(SubmissionHistory::class)->orderBy('created_at', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Approve the proof, mark any linked replacement duty as completed,
     * and clean up all historical rejected proof files.
     */
    public function approve(): void
    {
        $this->update(['verify_status' => VerifyStatus::Approved]);

        $this->replacementDuty?->update(['status' => ReplacementStatus::COMPLETED]);

        // Delete historical (rejected) proof files to free up storage.
        foreach ($this->histories as $history) {
            if ($history->proof_url) {
                Storage::disk('public')->delete($history->proof_url);
            }
            $history->delete();
        }
    }

    /**
     * Reject the proof. Archives the current proof_url + reason to submission_histories.
     * When $isFinal is false, the student may resubmit (increments resubmit_count).
     */
    public function reject(string $reason, bool $isFinal = false): void
    {
        // Archive current proof before overwriting.
        $this->histories()->create([
            'proof_url' => $this->proof_url,
            'reason'    => $reason,
        ]);

        $status = $isFinal ? VerifyStatus::RejectedFinal : VerifyStatus::Rejected;
        $this->update(['verify_status' => $status]);

        if (! $isFinal) {
            $this->increment('resubmit_count');
        }
    }
}

