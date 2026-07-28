<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$replacements = App\Models\ReplacementDuty::where('status', 'offered')->get();
$count = count($replacements);
foreach($replacements as $r) { 
    $r->delete(); 
}
echo 'Deleted ' . $count . ' replacement duties. Dashboard will regenerate them correctly on next load.';
