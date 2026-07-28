<?php

namespace App\Providers;

use App\Services\BadgeEngine;
use App\Services\Contracts\BadgeEngineInterface;
use App\Services\Contracts\PenaltyServiceInterface;
use App\Services\Contracts\RedemptionServiceInterface;
use App\Services\Contracts\RewardServiceInterface;
use App\Services\Contracts\SemesterServiceInterface;
use App\Services\Contracts\StatusServiceInterface;
use App\Services\SemesterService;
use App\Models\DutyClaim;
use App\Models\DutySlot;
use App\Models\Submission;
use App\Models\SwapRequest;
use App\Policies\DutyClaimPolicy;
use App\Policies\DutySlotPolicy;
use App\Policies\SubmissionPolicy;
use App\Policies\SwapRequestPolicy;
use App\Services\PenaltyService;
use App\Services\RedemptionService;
use App\Services\RewardService;
use App\Services\StatusService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RewardServiceInterface::class, RewardService::class);
        $this->app->bind(PenaltyServiceInterface::class, PenaltyService::class);
        $this->app->bind(StatusServiceInterface::class, StatusService::class);
        $this->app->bind(RedemptionServiceInterface::class, RedemptionService::class);
        $this->app->bind(BadgeEngineInterface::class, BadgeEngine::class);
        $this->app->bind(SemesterServiceInterface::class, SemesterService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(DutySlot::class, DutySlotPolicy::class);
        Gate::policy(Submission::class, SubmissionPolicy::class);
        Gate::policy(DutyClaim::class, DutyClaimPolicy::class);
        Gate::policy(SwapRequest::class, SwapRequestPolicy::class);

        // Pastikan folder temporer upload Livewire selalu ada (mengatasi error di hosting seperti InfinityFree)
        $tmpDir = storage_path('app/livewire-tmp');
        if (! is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }
    }
}
