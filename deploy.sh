#!/bin/sh

cd /var/www

composer install --optimize-autoloader --no-dev --ignore-platform-reqs
php artisan migrate --no-interaction
php artisan cache:clear
php artisan route:cache
php artisan config:cache

/usr/bin/supervisord -c /etc/supervisord/supervisord.conf