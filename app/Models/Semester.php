<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Semester extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function dutySlots(): HasMany
    {
        return $this->hasMany(DutySlot::class);
    }

    public function statusChangeLogs(): HasMany
    {
        return $this->hasMany(StatusChangeLog::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Make this the single active semester.
     */
    public function activate(): void
    {
        DB::transaction(function () {
            static::where('id', '!=', $this->id)->update(['is_active' => false]);
            $this->update(['is_active' => true]);
        });
    }

    /**
     * Reset every student's RPG stats to their starting values for a new
     * semester (full lives, zero XP, back to "citizen").
     */
    public function resetAll(): void
    {
        $max = (int) SystemConfig::get('lives_max', 3);

        StudentProfile::query()->update([
            'xp' => 0,
            'lives' => $max,
            'status' => StudentStatus::CITIZEN->value,
            'status_since' => now(),
        ]);
    }
}
