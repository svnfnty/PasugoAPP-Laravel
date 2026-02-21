<?php
echo "--- Pasugo Bootloader ---\n";

// 1. Run Migrations
echo "Running migrations...\n";
passthru("php artisan migrate --force");

// 2. Start Reverb in the background on port 8081
echo "Starting Reverb on 8081...\n";
exec("php artisan reverb:start --host=0.0.0.0 --port=8081 > reverb.log 2>&1 &");

// 3. Start FrankenPHP with our Caddyfile
// We try to find the frankenphp binary or use the default
echo "Starting FrankenPHP Gateway...\n";
passthru("frankenphp run --config Caddyfile");
?>
