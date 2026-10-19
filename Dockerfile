
FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    librdkafka-dev \
    libsqlite3-dev \
    git \
    unzip \
    && pecl install rdkafka \
    && docker-php-ext-enable rdkafka \
    && docker-php-ext-install pdo_sqlite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

CMD ["php", "-v"]
