#!/bin/bash

# Ensure we are in the app directory
cd /app

# Clear all cached files that might have local Windows paths
echo "Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
rm -f bootstrap/cache/*.php

# Run migrations (ensure DB is connected)
echo "Running migrations..."
php artisan migrate --force


# Start the actual Laravel Web Server on internal port 8000
echo "Starting Laravel web server on port 8000..."
export PHP_CLI_SERVER_WORKERS=5
php artisan serve --host=127.0.0.1 --port=8000 &

# Start Laravel Reverb in the background on port 8081
echo "Starting Laravel Reverb on port 8081..."
php artisan reverb:start --host=127.0.0.1 --port=8081 &

# Configure and Start Nginx to bridge the public $PORT to our internal services
echo "Starting Nginx Proxy on port $PORT..."
sed -i "s/\${PORT}/$PORT/g" nginx.conf
nginx -c /app/nginx.conf -g "daemon off;"

