<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBadgeRequest;
use App\Models\Badge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BadgeController extends Controller
{
    /**
     * Fields supported by the badge rule engine (see BadgeEngine).
     *
     * @var array<int, string>
     */
    private const AVAILABLE_FIELDS = [
        'total_xp',
        'total_approved_missions',
        'consecutive_approved_missions',
        'has_been_convict',
        'current_status',
        'total_swap_used',
        'early_submission_streak',
        'semester_without_convict',
    ];

    /**
     * List badges with how many students earned each.
     */
    public function index()
    {
        $badges = Badge::withCount('studentBadges')->get();

        return view('admin.badges.index', compact('badges'));
    }

    /**
     * Show the create form with the list of rule fields available.
     */
    public function create()
    {
        $fields = self::AVAILABLE_FIELDS;

        return view('admin.badges.create', compact('fields'));
    }

    /**
     * Persist a new badge together with its rule groups and conditions.
     */
    public function store(StoreBadgeRequest $request)
    {
        DB::transaction(function () use ($request): void {
            $iconPath = $request->hasFile('icon')
                ? Storage::disk('public')->put('badges', $request->file('icon'))
                : null;

            $badge = Badge::create([
                'name' => $request->string('name'),
                'description' => $request->string('description'),
                'icon_url' => $iconPath,
            ]);

            $this->syncRuleGroups($badge, $request->input('rule_groups', []));
        });

        return redirect()->route('admin.badges.index')->with('success', 'Badge berhasil dibuat');
    }

    /**
     * Show the edit form with existing rule groups/conditions loaded.
     */
    public function edit(Badge $badge)
    {
        $badge->load('ruleGroups.conditions');
        $fields = self::AVAILABLE_FIELDS;

        return view('admin.badges.edit', compact('badge', 'fields'));
    }

    /**
     * Update badge fields and rebuild its rule groups from scratch.
     */
    public function update(StoreBadgeRequest $request, Badge $badge)
    {
        DB::transaction(function () use ($request, $badge): void {
            $data = [
                'name' => $request->string('name'),
                'description' => $request->string('description'),
            ];

            if ($request->hasFile('icon')) {
                if ($badge->icon_url) {
                    Storage::disk('public')->delete($badge->icon_url);
                }

                $data['icon_url'] = Storage::disk('public')->put('badges', $request->file('icon'));
            }

            $badge->update($data);

            // Remove the old rule definitions, then recreate them.
            foreach ($badge->ruleGroups()->with('conditions')->get() as $group) {
                $group->conditions()->forceDelete();
                $group->forceDelete();
            }

            $this->syncRuleGroups($badge, $request->input('rule_groups', []));
        });

        return redirect()->route('admin.badges.index')->with('success', 'Badge berhasil diperbarui');
    }

    /**
     * Delete a badge and remove its stored icon.
     */
    public function destroy(Badge $badge)
    {
        if ($badge->icon_url) {
            Storage::disk('public')->delete($badge->icon_url);
        }

        $badge->delete();

        return redirect()->back()->with('success', 'Badge dihapus');
    }

    /**
     * Create rule groups (and their conditions) for a badge from request data.
     *
     * @param  array<int, array<string, mixed>>  $ruleGroups
     */
    private function syncRuleGroups(Badge $badge, array $ruleGroups): void
    {
        foreach ($ruleGroups as $groupData) {
            $group = $badge->ruleGroups()->create([
                // DB stores the operator uppercase (AND/OR); the form sends and/or.
                'logic_operator' => strtoupper((string) ($groupData['logic_operator'] ?? 'and')),
            ]);

            foreach ($groupData['conditions'] ?? [] as $condition) {
                $group->conditions()->create([
                    'field' => $condition['field'],
                    'operator' => $condition['operator'],
                    'value' => $condition['value'],
                ]);
            }
        }
    }
}
