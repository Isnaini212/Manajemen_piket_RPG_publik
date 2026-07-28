<?php

namespace App\Models;

use App\Enums\LogicOperator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BadgeRuleGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'badge_id',
        'logic_operator',
    ];

    protected function casts(): array
    {
        return [
            'logic_operator' => LogicOperator::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(BadgeRuleCondition::class, 'rule_group_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Evaluate the group's conditions combined with its logic operator.
     * AND requires all conditions to pass; OR requires at least one.
     * An empty group evaluates to false.
     */
    public function evaluate(int $studentId): bool
    {
        $conditions = $this->conditions;

        if ($conditions->isEmpty()) {
            return false;
        }

        if ($this->logic_operator === LogicOperator::Or) {
            return $conditions->contains(fn (BadgeRuleCondition $c) => $c->evaluate($studentId));
        }

        return $conditions->every(fn (BadgeRuleCondition $c) => $c->evaluate($studentId));
    }
}
