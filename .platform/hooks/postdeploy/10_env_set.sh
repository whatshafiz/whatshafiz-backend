#!/bin/bash
ENVIRONMENT=$(/opt/elasticbeanstalk/bin/get-config environment -k ENVIRONMENT)
aws ssm get-parameters-by-path --region eu-central-1 --path /whatshafiz-backend/$ENVIRONMENT/ --recursive --with-decryption |jq -r '.[] | .[] | [.Name, .Value]| join("=")'|sed  "s/\/whatshafiz-backend\/${ENVIRONMENT}\///g" > /tmp/.env
docker cp /tmp/.env `sudo docker ps |grep php-fpm|awk '{print $1}'`:/var/www/.env
