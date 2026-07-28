<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$profiles = \App\Models\StudentProfile::where('lives', '<=', 0)
    ->where('status', \App\Enums\StudentStatus::CITIZEN)
    ->get();

$statusService = app(\App\Services\Contracts\StatusServiceInterface::class);

foreach ($profiles as $p) {
    $statusService->changeToConvict($p);
    echo "Updated {$p->user->name} to CONVICT.\n";
}

echo "Done.\n";
