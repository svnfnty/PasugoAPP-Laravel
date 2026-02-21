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
// We use a simpler direct redirection to /dev/stderr to ensure logs appear in Railway
exec("php artisan reverb:start --host=0.0.0.0 --port=8081 --debug > /proc/1/fd/2 2>&1 &");

// 3. Briefly wait and check if Reverb is alive
usleep(500000);

echo "Reverb process spawned. Logging to Container Stderr.\n";

// 4. Start FrankenPHP Gateway (This must be last as it blocks)
echo "Starting FrankenPHP Gateway...\n";
passthru("frankenphp run --config Caddyfile");
?>
