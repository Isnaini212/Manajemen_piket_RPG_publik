<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LifeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_profile_id',
        'change',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'change' => 'integer',
        ];
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }
}
