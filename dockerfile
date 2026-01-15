FROM php:8.2-fpm

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    git unzip curl libicu-dev libonig-dev libzip-dev libxslt-dev \
    && docker-php-ext-install intl pdo pdo_mysql zip opcache xsl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@11.7.0 \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/nutrition

COPY . .

EXPOSE 8000

CMD ["php", "bin/console", "server:run", "0.0.0.0:8000"]
