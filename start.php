<?php
echo "--- Pasugo Bootloader ---\n";

// 1. Run Migrations
echo "Running migrations...\n";
passthru("php artisan migrate --force");

echo "Checking environment for Reverb...\n";
$reverbKey = getenv('REVERB_APP_KEY') ?: 'NOT SET';
$reverbPort = getenv('REVERB_SERVER_PORT') ?: '8081';
echo "REVERB_APP_KEY: " . substr($reverbKey, 0, 4) . "...\n";
echo "REVERB_SERVER_PORT: " . $reverbPort . "\n";

// 2. Start Reverb in the background on port 8081
echo "Starting Reverb on 0.0.0.0:8081...\n";
exec("php artisan reverb:start --host=0.0.0.0 --port=8081 --debug > reverb.log 2>&1 &");

// 3. Start Reverb Monitor
exec("php start_reverb_monitor.php &");

// 4. Start FrankenPHP Gateway
echo "Starting FrankenPHP Gateway...\n";
passthru("frankenphp run --config Caddyfile");
?>
