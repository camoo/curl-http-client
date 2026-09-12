FROM php:8.5-cli-alpine

RUN apk add --no-cache \
    curl-dev \
    libxml2-dev \
    oniguruma-dev \
    git \
    unzip \
    && docker-php-ext-install curl dom mbstring

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
