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


# Start the actual Laravel Web Server on the Railway $PORT
echo "Starting web server on port $PORT..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8080}  &

# Start Laravel Reverb in the background on port 8081
echo "Starting Laravel Reverb on port 8080..."
php artisan reverb:start --host=0.0.0.0 --port=8080
