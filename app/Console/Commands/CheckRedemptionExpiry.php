<?php

namespace App\Console\Commands;

use App\Enums\StudentStatus;
use App\Models\StatusChangeLog;
use App\Services\Contracts\RedemptionServiceInterface;
use Illuminate\Console\Command;

class CheckRedemptionExpiry extends Command
{
    protected $signature = 'piket:check-redemption-expiry';

    protected $description = 'Tandai penebusan Convict yang lewat deadline sebagai gagal.';

    public function handle(RedemptionServiceInterface $redemption): int
    {
        $logs = StatusChangeLog::where('to_status', StudentStatus::CONVICT->value)
            ->whereNull('resolved_at')
            ->where('redemption_deadline', '<', now())
            ->with('studentProfile')
            ->get();

        foreach ($logs as $log) {
            $profile = $log->studentProfile;

            if (! $profile) {
                continue;
            }

            $redemption->markRedemptionFailed($profile);
            $this->info("Redemption failed untuk profile ID: {$log->student_profile_id}");
        }

        $this->info("Selesai. Total diproses: {$logs->count()}");

        return self::SUCCESS;
    }
}
