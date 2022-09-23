#!/bin/bash
aws ssm get-parameters-by-path --region eu-west-1 --path /whatshafiz/ --recursive --with-decryption |jq -r '.[] | .[] | [.Name, .Value]| join("=")'|sed  "s/\/whatshafiz\///g" > /tmp/.env
sudo docker cp /tmp/.env `sudo docker ps |grep php-fpm|awk '{print $1}'`:/var/www/.env

