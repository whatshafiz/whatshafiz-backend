#!/bin/bash

docker volume create whatshafiz-mariadb-data

docker-compose -f docker-compose-dev.yml up -d

cp ./src/.env.example ./src/.env

docker exec -i whatshafiz_php-fpm composer install
docker exec -i whatshafiz_php-fpm php artisan key:generate
docker exec -i whatshafiz_php-fpm php artisan migrate:fresh --seed
