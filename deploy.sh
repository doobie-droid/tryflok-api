#!/bin/sh

cd /var/www

composer install --optimize-autoloader --no-dev --ignore-platform-reqs
yes | php artisan migrate
php artisan cache:clear
php artisan route:cache
php artisan config:cache

/usr/bin/supervisord -c /etc/supervisor/supervisord.conf