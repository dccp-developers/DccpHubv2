#!/bin/bash

# Change to the project directory
cd /home/dccp/projects/DccpHubv2
export APP_ENV=production   
export APP_DEBUG=false

# Install/update dependencies if needed
composer install --no-dev --optimize-autoloader
npm ci --production

# Clear and cache Laravel configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icon:cache

# Start the FrankenPHP server
php artisan octane:start --server=frankenphp --host=127.0.0.1 --port=28562 --admin-port=28563 &
php artisan queue:work redis --queue=assessments,pdf-generation --sleep=3 --tries=3 &
php artisan nightwatch:agent &
php artisan horizon &
wait

# php artisan serve --host=127.0.0.1 --port=28561
