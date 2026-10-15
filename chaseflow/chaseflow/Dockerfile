FROM richarvey/nginx-php-fpm:3.1.0

LABEL maintainer="chaseflow"
WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && chmod +x /var/www/html/scripts/00-laravel-deploy.sh

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    WEBROOT=/var/www/html/public \
    RUN_SCRIPTS=1 \
    REAL_IP_HEADER=1 \
    COMPOSER_ALLOW_SUPERUSER=1

EXPOSE 80

CMD ["/start.sh"]
