FROM php:8.3-cli

RUN docker-php-ext-install mysqli pdo pdo_mysql

WORKDIR /app

COPY . /app

RUN cp config.example.php config.php \
    && mkdir -p storage uploads uploads/licenses uploads/profiles \
    && chmod -R 777 storage uploads

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /app"]
