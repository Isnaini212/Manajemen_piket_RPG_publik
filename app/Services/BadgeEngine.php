<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Enums\ClaimType;
use App\Enums\LogicOperator;
use App\Enums\StudentStatus;
use App\Enums\SwapStatus;
use App\Models\Badge;
use App\Models\BadgeRuleCondition;
use App\Models\BadgeRuleGroup;
use App\Models\DutyClaim;
use App\Models\Notification;
use App\Models\Semester;
use App\Models\StatusChangeLog;
use App\Models\StudentBadge;
use App\Models\StudentProfile;
use App\Models\Submission;
use App\Models\SwapRequest;
use App\Services\Contracts\BadgeEngineInterface;
use Illuminate\Support\Facades\Log;

class BadgeEngine implements BadgeEngineInterface
{
    /**
     * Per-request cache of computed student data, keyed by profile id.
     *
     * @var array<int, array<string, mixed>>
     */
    private static array $cache = [];

    /**
     * Evaluate every badge the student has not earned yet and award the ones
     * whose rules are now satisfied.
     */
    public function checkAll(int $studentProfileId, string $event): void
    {
        $profile = StudentProfile::with('user')->find($studentProfileId);

        if (! $profile) {
            Log::warning('BadgeEngine: profile tidak ditemukan', ['id' => $studentProfileId]);

            return;
        }

        $earnedBadgeIds = StudentBadge::where('student_profile_id', $studentProfileId)->pluck('badge_id');

        $badges = Badge::whereNotIn('id', $earnedBadgeIds)->get();

        foreach ($badges as $badge) {
            $this->evaluateBadge($badge, $studentProfileId);
        }
    }

    /**
     * Evaluate a specific badge against all students who do not have it yet.
     */
    public function evaluateBadgeForAllStudents(int $badgeId): void
    {
        $badge = Badge::find($badgeId);
        if (! $badge) {
            return;
        }

        // Ambil semua student profile id yang belum memiliki badge ini.
        $studentIdsWithBadge = StudentBadge::where('badge_id', $badgeId)->pluck('student_profile_id');
        $studentProfilesWithoutBadge = StudentProfile::whereNotIn('id', $studentIdsWithBadge)->pluck('id');

        foreach ($studentProfilesWithoutBadge as $studentProfileId) {
            $this->evaluateBadge($badge, $studentProfileId);
        }
    }

    /**
     * A badge is awarded only when ALL of its rule groups pass (AND between
     * groups). A badge with no rule groups is never granted automatically.
     */
    private function evaluateBadge(Badge $badge, int $studentProfileId): void
    {
        $groups = $badge->ruleGroups()->with('conditions')->get();

        if ($groups->isEmpty()) {
            return;
        }

        foreach ($groups as $group) {
            if (! $this->evaluateGroup($group, $studentProfileId)) {
                return;
            }
        }

        $this->grantBadge($badge->id, $studentProfileId);
    }

    /**
     * Evaluate a rule group: AND requires all conditions true, OR requires at
     * least one. An empty group evaluates to false.
     */
    private function evaluateGroup(BadgeRuleGroup $group, int $studentProfileId): bool
    {
        $conditions = $group->conditions;

        if ($conditions->isEmpty()) {
            return false;
        }

        $data = $this->getStudentData($studentProfileId);

        if ($group->logic_operator === LogicOperator::Or) {
            return $conditions->contains(fn (BadgeRuleCondition $c) => $this->evaluateCondition($c, $data));
        }

        // Default: AND.
        return $conditions->every(fn (BadgeRuleCondition $c) => $this->evaluateCondition($c, $data));
    }

    /**
     * Evaluate a single condition against the pre-computed student data.
     */
    private function evaluateCondition(BadgeRuleCondition $condition, array $studentData): bool
    {
        $fieldValue = $studentData[$condition->field] ?? 0;
        $expected = $condition->value;

        return match ($condition->operator) {
            '>=' => (float) $fieldValue >= (float) $expected,
            '<=' => (float) $fieldValue <= (float) $expected,
            '>' => (float) $fieldValue > (float) $expected,
            '<' => (float) $fieldValue < (float) $expected,
            '=' => $fieldValue == $expected,
            '!=' => $fieldValue != $expected,
            default => false,
        };
    }

    /**
     * Compute every supported field for a student in one pass (avoids N+1),
     * memoised per request in a static cache.
     *
     * @return array<string, mixed>
     */
    private function getStudentData(int $studentProfileId): array
    {
        if (isset(self::$cache[$studentProfileId])) {
            return self::$cache[$studentProfileId];
        }

        $profile = StudentProfile::with('user')->findOrFail($studentProfileId);

        $activeSemester = Semester::where('is_active', true)->first();

        $data = [
            'total_xp' => (int) $profile->xp,

            'total_approved_missions' => DutyClaim::where('student_id', $studentProfileId)
                ->where('status', ClaimStatus::Approved->value)
                ->count(),

            'consecutive_approved_missions' => $this->consecutiveApprovedMissions($studentProfileId),

            'has_been_convict' => StatusChangeLog::where('student_profile_id', $studentProfileId)
                ->where('to_status', StudentStatus::CONVICT->value)
                ->exists() ? 1 : 0,

            'current_status' => $profile->status->value,

            'total_swap_used' => SwapRequest::whereHas(
                'fromClaim',
                fn ($q) => $q->where('student_id', $studentProfileId),
            )->where('status', SwapStatus::Accepted->value)->count(),

            'early_submission_streak' => $this->earlySubmissionStreak($studentProfileId),

            'semester_without_convict' => $this->semesterWithoutConvict($studentProfileId, $activeSemester?->id),
        ];

        self::$cache[$studentProfileId] = $data;

        return $data;
    }

    /**
     * Streak of approved duties, newest-first by duty date, until the first
     * non-approved claim breaks the run.
     */
    private function consecutiveApprovedMissions(int $studentProfileId): int
    {
        $statuses = DutyClaim::query()
            ->join('duty_slots', 'duty_claims.duty_slot_id', '=', 'duty_slots.id')
            ->where('duty_claims.student_id', $studentProfileId)
            ->orderByDesc('duty_slots.duty_date')
            ->pluck('duty_claims.status');

        $streak = 0;

        foreach ($statuses as $status) {
            // Eloquent may return a cast ClaimStatus enum here, not a raw string.
            $value = $status instanceof ClaimStatus ? $status->value : $status;

            if ($value === ClaimStatus::Approved->value) {
                $streak++;

                continue;
            }

            break;
        }

        return $streak;
    }

    /**
     * Streak of on-time submissions, newest-first by duty date, until the
     * first late submission (uploaded after the duty date) breaks the run.
     */
    private function earlySubmissionStreak(int $studentProfileId): int
    {
        $rows = Submission::query()
            ->join('duty_claims', 'submissions.duty_claim_id', '=', 'duty_claims.id')
            ->join('duty_slots', 'duty_claims.duty_slot_id', '=', 'duty_slots.id')
            ->where('duty_claims.student_id', $studentProfileId)
            ->orderByDesc('duty_slots.duty_date')
            ->get(['submissions.uploaded_at', 'duty_slots.duty_date']);

        $streak = 0;

        foreach ($rows as $row) {
            $uploadedDate = \Illuminate\Support\Carbon::parse($row->uploaded_at)->toDateString();
            $dutyDate = \Illuminate\Support\Carbon::parse($row->duty_date)->toDateString();

            // On time = uploaded on or before the duty date.
            if ($uploadedDate <= $dutyDate) {
                $streak++;

                continue;
            }

            break;
        }

        return $streak;
    }

    /**
     * 1 when the student has no status change logged in the active semester,
     * 0 otherwise (or when there is no active semester).
     */
    private function semesterWithoutConvict(int $studentProfileId, ?int $activeSemesterId): int
    {
        if ($activeSemesterId === null) {
            return 0;
        }

        $hasLog = StatusChangeLog::where('student_profile_id', $studentProfileId)
            ->where('semester_id', $activeSemesterId)
            ->exists();

        return $hasLog ? 0 : 1;
    }

    /**
     * Award a badge once (guarded against races) and notify the student.
     */
    public function grantBadge(int $badgeId, int $studentProfileId): void
    {
        if (StudentBadge::where('student_profile_id', $studentProfileId)->where('badge_id', $badgeId)->exists()) {
            return;
        }

        $badge = Badge::find($badgeId);

        if (! $badge) {
            return;
        }

        $studentBadge = StudentBadge::firstOrCreate(
            [
                'student_profile_id' => $studentProfileId,
                'badge_id' => $badgeId,
            ],
            [
                'earned_at' => now(),
            ],
        );

        // Only notify if this call actually created the row.
        if (! $studentBadge->wasRecentlyCreated) {
            return;
        }

        $userId = StudentProfile::where('id', $studentProfileId)->value('user_id');

        if ($userId !== null) {
            Notification::create([
                'user_id' => $userId,
                'type' => 'badge_earned',
                'message' => '🏆 Kamu mendapat badge baru: ' . $badge->name . '!',
            ]);
        }

        Log::info('Badge diberikan', [
            'student_profile_id' => $studentProfileId,
            'badge_id' => $badgeId,
            'badge' => $badge->name,
        ]);
    }
}
