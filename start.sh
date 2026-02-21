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


# Start all services (Laravel, Reverb, Proxy) via npm to ensure correct PATH
echo "Starting application services..."
npm start

