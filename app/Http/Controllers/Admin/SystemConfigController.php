<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use Illuminate\Http\Request;

class SystemConfigController extends Controller
{
    /**
     * Show all configuration entries.
     */
    public function index()
    {
        $configs = SystemConfig::orderBy('key')->get();

        return view('admin.system-configs.index', compact('configs'));
    }

    /**
     * Persist the edited configuration values.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'configs.citizen_weekly_missions' => ['required', 'integer', 'min:1', 'max:7'],
            'configs.convict_weekly_missions' => ['required', 'integer', 'min:1', 'max:14'],
            'configs.swap_limit_per_month' => ['required', 'integer', 'min:0', 'max:10'],
            'configs.replacement_duty_enabled' => ['required', 'boolean'],
            'configs.replacement_duty_days' => ['required', 'integer', 'min:1', 'max:7'],
            'configs.convict_status_visible' => ['required', 'boolean'],
            'configs.redemption_period_days' => ['required', 'integer', 'min:7', 'max:60'],
            'configs.lives_max' => ['required', 'integer', 'min:1', 'max:10'],
            'configs.lives_on_recovery' => ['required', 'integer', 'min:1'],
            'configs.xp_per_mission' => ['required', 'integer', 'min:1'],
        ]);

        $booleanKeys = ['replacement_duty_enabled', 'convict_status_visible'];

        foreach ($validated['configs'] as $key => $value) {
            // Store booleans as 'true'/'false' strings so they round-trip
            // consistently with the rest of the config values.
            if (in_array($key, $booleanKeys, true)) {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
            }

            SystemConfig::set($key, $value);
        }

        return redirect()->back()->with('success', 'Konfigurasi berhasil disimpan');
    }
}
