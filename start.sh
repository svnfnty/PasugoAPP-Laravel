#!/bin/bash

# Start Reverb on a different internal port (e.g. 8081)
# Note: This is only accessible internally within the container 
# unless you use a proxy, but it prevents the crash.
echo "Starting Laravel Reverb on port 8081..."
php artisan reverb:start --host=0.0.0.0 --port=8081 &

sleep 2

echo "Running migrations..."
php artisan migrate --force

echo "Starting web server on port $PORT..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}

