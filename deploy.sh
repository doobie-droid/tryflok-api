#!/bin/sh

cd /var/www

php artisan migrate
php artisan cache:clear
php artisan route:cache
php artisan config:cache

/usr/bin/supervisord -c /etc/supervisord.conf