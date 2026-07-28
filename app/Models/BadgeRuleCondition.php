<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BadgeRuleCondition extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'rule_group_id',
        'field',
        'operator',
        'value',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function ruleGroup(): BelongsTo
    {
        return $this->belongsTo(BadgeRuleGroup::class, 'rule_group_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Evaluate this single condition against a student's stats.
     *
     * Supported fields are resolved from the student profile and its
     * aggregates; supported operators: =, !=, >, >=, <, <=.
     */
    public function evaluate(int $studentId): bool
    {
        $profile = StudentProfile::find($studentId);

        if (! $profile) {
            return false;
        }

        $actual = $this->resolveField($profile);

        if ($actual === null) {
            return false;
        }

        return $this->compare($actual, $this->value);
    }

    /**
     * Resolve the condition's field name to a concrete value for the student.
     */
    protected function resolveField(StudentProfile $profile): mixed
    {
        return match ($this->field) {
            'xp' => $profile->xp,
            'lives' => $profile->lives,
            'status' => $profile->status?->value,
            'badge_count' => $profile->studentBadges()->count(),
            'approved_duties' => $profile->dutyClaims()
                ->where('status', \App\Enums\ClaimStatus::Approved->value)
                ->count(),
            default => null,
        };
    }

    /**
     * Compare an actual value against the expected value using this
     * condition's operator. Numeric comparisons are used when both sides
     * are numeric, otherwise loose string equality for =/!=.
     */
    protected function compare(mixed $actual, mixed $expected): bool
    {
        $numeric = is_numeric($actual) && is_numeric($expected);

        return match ($this->operator) {
            '=', '==' => $numeric ? ((float) $actual === (float) $expected) : ((string) $actual === (string) $expected),
            '!=', '<>' => $numeric ? ((float) $actual !== (float) $expected) : ((string) $actual !== (string) $expected),
            '>' => $numeric && (float) $actual > (float) $expected,
            '>=' => $numeric && (float) $actual >= (float) $expected,
            '<' => $numeric && (float) $actual < (float) $expected,
            '<=' => $numeric && (float) $actual <= (float) $expected,
            default => false,
        };
    }
}
