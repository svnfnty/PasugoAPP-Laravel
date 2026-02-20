#!/bin/bash

# Ensure we are in the app directory
cd /app

# Run migrations (ensure DB is connected)
echo "Running migrations..."
php artisan migrate --force

# Start Laravel Reverb in the background 
echo "Starting Laravel Reverb..."
php artisan reverb:start --host=0.0.0.0 --port=8081 &

# Start the web server
echo "Starting web server on port $PORT..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
