<?php
echo "--- Pasugo Bootloader ---\n";

// 1. Run Migrations
// 1. Clear Caches and Run Migrations
echo "Clearing caches...\n";
putenv('LOG_CHANNEL=stderr');
passthru("php artisan config:clear");
passthru("php artisan cache:clear");
passthru("php artisan route:clear");

echo "Running migrations...\n";
passthru("php artisan migrate --force");

echo "Checking environment for Reverb...\n";
$reverbKey = getenv('REVERB_APP_KEY') ?: 'NOT SET';
echo "REVERB_APP_KEY: " . substr($reverbKey, 0, 4) . "...\n";

// 2. Start Reverb in the background correctly
echo "Starting Reverb on 0.0.0.0:8081...\n";
// Pass LOG_CHANNEL=stderr to the sub-process
$cmd = "export LOG_CHANNEL=stderr && php artisan reverb:start --host=0.0.0.0 --port=8081 --debug";
exec("($cmd >> reverb.log 2>&1) & echo $!", $output);
$pid = $output[0] ?? 'unknown';
echo "Reverb started with PID: $pid (Logs in reverb.log)\n";

// 3. Start Reverb Monitor in the background
echo "Starting Log Monitor...\n";
exec("php start_reverb_monitor.php > /dev/null 2>&1 &");

// 4. Start FrankenPHP Gateway (This must be last as it blocks)
echo "Starting FrankenPHP Gateway...\n";
passthru("frankenphp run --config Caddyfile");
?>
