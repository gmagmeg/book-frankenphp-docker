FROM dunglas/frankenphp:1-php8.4-bookworm

RUN install-php-extensions \
    pcntl \
    pdo_pgsql \
    intl \
    zip \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . /app

RUN composer install --no-interaction --prefer-dist \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache

COPY docker/start-container.sh /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container

EXPOSE 8000

CMD ["/usr/local/bin/start-container"]
