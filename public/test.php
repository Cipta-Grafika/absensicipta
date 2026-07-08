<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$r = \App\Models\ReplacementHour::first();
echo "Start: " . $r->start_hour . "\n";
echo "End: " . $r->end_hour . "\n";
echo "Diff: " . \Carbon\Carbon::parse($r->end_hour)->diffInMinutes(\Carbon\Carbon::parse($r->start_hour)) . "\n";
echo "Accessor1: " . $r->duration_minutes . "\n";
echo "Accessor2: " . $r->formatted_duration . "\n";
