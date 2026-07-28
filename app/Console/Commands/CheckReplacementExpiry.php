<?php

namespace App\Console\Commands;

use App\Enums\ReplacementStatus;
use App\Models\ReplacementDuty;
use App\Services\Contracts\PenaltyServiceInterface;
use Illuminate\Console\Command;

class CheckReplacementExpiry extends Command
{
    protected $signature = 'piket:check-replacement-expiry';

    protected $description = 'Tandai piket pengganti yang lewat deadline sebagai expired dan terapkan penalti.';

    public function handle(PenaltyServiceInterface $penalty): int
    {
        $replacements = ReplacementDuty::where('status', ReplacementStatus::OFFERED->value)
            ->where('deadline', '<', now())
            ->whereDoesntHave('submission', function ($query) {
                $query->whereIn('verify_status', [
                    \App\Enums\VerifyStatus::Pending->value,
                    \App\Enums\VerifyStatus::Approved->value
                ]);
            })
            ->get();

        foreach ($replacements as $replacement) {
            $penalty->handleReplacementExpired($replacement);
            $this->info("Replacement ID {$replacement->id} expired, penalti diterapkan");
        }

        $this->info("Selesai. Total diproses: {$replacements->count()}");

        return self::SUCCESS;
    }
}
