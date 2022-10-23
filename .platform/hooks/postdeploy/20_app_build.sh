#!/bin/bash

sudo docker exec `sudo docker ps |grep php-fpm|awk '{print $1}'` composer install
sudo docker exec `sudo docker ps |grep php-fpm|awk '{print $1}'` composer dump-autoload -o
sudo docker exec `sudo docker ps |grep php-fpm|awk '{print $1}'` bash -c 'chmod -R 777 /var/www/storage/'
sudo docker exec `sudo docker ps |grep php-fpm|awk '{print $1}'` bash -c 'cp -R /var/www/public /var/www/public_html'
sudo docker exec `sudo docker ps |grep php-fpm|awk '{print $1}'` php artisan clear-compiled
sudo docker exec `sudo docker ps |grep php-fpm|awk '{print $1}'` php artisan storage:link
sudo docker exec `sudo docker ps |grep php-fpm|awk '{print $1}'` php artisan config:cache
sudo docker exec `sudo docker ps |grep php-fpm|awk '{print $1}'` php artisan route:cache
sudo docker exec `sudo docker ps |grep php-fpm|awk '{print $1}'` php artisan event:cache
sudo docker exec `sudo docker ps |grep php-fpm|awk '{print $1}'` php artisan migrate --force
sudo docker exec `sudo docker ps |grep php-fpm|awk '{print $1}'` supervisorctl restart all
