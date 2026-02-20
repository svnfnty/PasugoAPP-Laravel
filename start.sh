#!/bin/bash

# Start Reverb WebSocket server in the background
echo "Starting Laravel Reverb on port 8080..."
php artisan reverb:start --host=0.0.0.0 --port=8080 &

# Give Reverb a moment to start
sleep 2

# Run migrations automatically
echo "Running migrations..."
php artisan migrate --force

# Start the main web server on Railway's assigned PORT
echo "Starting web server on port $PORT..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
