<?php

namespace App\Services\Contracts;

interface BadgeEngineInterface
{
    /**
     * Evaluate every not-yet-earned badge for a student and award the ones
     * whose rules are now satisfied.
     */
    public function checkAll(int $studentProfileId, string $event): void;

    /**
     * Evaluate a specific badge against all students who do not have it yet.
     */
    public function evaluateBadgeForAllStudents(int $badgeId): void;

    /**
     * Award a badge once (guarded against races) and notify the student.
     */
    public function grantBadge(int $badgeId, int $studentProfileId): void;
}
