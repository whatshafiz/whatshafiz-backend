FROM nozkok/php8-laravel8:8.1.3

COPY ./docker/php/conf.d/ /usr/local/etc/php/conf.d/
COPY ./docker/supervisor/laravel-worker.conf /etc/supervisor/conf.d/
COPY ./ /var/www

# RUN pecl install xdebug
# RUN docker-php-ext-enable xdebug

RUN chown -R root /var/www

EXPOSE 9000

CMD ["sh", "-c", "/usr/bin/supervisord -c /etc/supervisor/supervisord.conf && php-fpm"]
