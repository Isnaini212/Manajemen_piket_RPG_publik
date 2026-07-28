<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Badge extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'icon_url',
        'description',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function ruleGroups(): HasMany
    {
        return $this->hasMany(BadgeRuleGroup::class);
    }

    public function studentBadges(): HasMany
    {
        return $this->hasMany(StudentBadge::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Determine whether a student qualifies for this badge.
     *
     * Rule groups are OR-combined: the badge is earned when at least one
     * group is satisfied (each group internally applies its own AND/OR).
     * A badge with no rule groups can never be auto-earned.
     */
    public function evaluateFor(int $studentId): bool
    {
        $groups = $this->ruleGroups;

        if ($groups->isEmpty()) {
            return false;
        }

        return $groups->contains(fn (BadgeRuleGroup $group) => $group->evaluate($studentId));
    }
}
