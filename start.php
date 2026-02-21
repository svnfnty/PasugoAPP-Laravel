<?php
echo "--- Pasugo Bootloader ---\n";

// 1. Run Migrations
// 1. Clear Caches and Run Migrations
echo "Clearing caches...\n";
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
pclose(popen("php artisan reverb:start --host=0.0.0.0 --port=8081 --debug > reverb.log 2>&1 &", "r"));

// 3. Start Reverb Monitor in the background correctly
echo "Starting Log Monitor...\n";
pclose(popen("php start_reverb_monitor.php &", "r"));

// 4. Start FrankenPHP Gateway (This must be last as it blocks)
echo "Starting FrankenPHP Gateway...\n";
passthru("frankenphp run --config Caddyfile");
?>
