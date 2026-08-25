FROM php:8.4-apache

ARG NODE_MAJOR=24
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        curl \
        chromium \
        libicu-dev \
        libonig-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        pdo_mysql \
        zip \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && a2enmod rewrite \
    && curl -fsSL https://deb.nodesource.com/setup_${NODE_MAJOR}.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && npm install --global npm@latest \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm composer-setup.php \
    && composer global require laravel/installer \
    && ln -s /root/.composer/vendor/bin/laravel /usr/local/bin/laravel \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php-apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php-apache/99-xdebug.ini /usr/local/etc/php/conf.d/99-xdebug.ini
COPY docker/php-apache/install-laravel.sh /usr/local/bin/install-laravel.sh
RUN chmod +x /usr/local/bin/install-laravel.sh

WORKDIR /workspaces/LaravelResumeGenerationSystem/src
