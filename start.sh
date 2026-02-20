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

# Start Laravel Reverb in the background on an internal port
# We use 8081 so it doesn't conflict with the web server
echo "Starting Laravel Reverb on port 8081..."
php artisan reverb:start --host=0.0.0.0 --port=8081 &

# Start the actual Laravel Web Server on the Railway $PORT
echo "Starting web server on port $PORT..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}

