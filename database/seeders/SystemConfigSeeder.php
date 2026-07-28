<?php

namespace Database\Seeders;

use App\Models\SystemConfig;
use Illuminate\Database\Seeder;

class SystemConfigSeeder extends Seeder
{
    /**
     * Seed all configuration keys with their default values.
     *
     * Boolean values are stored as string flags ('true' / 'false') so they
     * round-trip cleanly through the text `value` column.
     */
    public function run(): void
    {
        $defaults = [
            'citizen_weekly_missions' => '1',
            'convict_weekly_missions' => '3',
            'swap_limit_per_month' => '2',
            'replacement_duty_enabled' => 'true',
            'replacement_duty_days' => '3',
            'convict_status_visible' => 'false',
            'redemption_period_weeks' => '1',
            'lives_max' => '3',
            'lives_on_recovery' => '1',
        ];

        foreach ($defaults as $key => $value) {
            SystemConfig::updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }
}
