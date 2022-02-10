FROM php:7.4-fpm

RUN apt-get update && \
apt-get install -y \
libpq-dev zlib1g-dev libxml2-dev librabbitmq-dev git openssh-client unzip vim libpng-dev curl libzip-dev

RUN docker-php-ext-install pdo pdo_pgsql pgsql sockets zip soap gd
RUN ln -s /usr/local/etc/php/php_no_errors.ini /usr/local/etc/php/php.ini

RUN pecl install apcu amqp mailparse xdebug

RUN docker-php-ext-enable apcu amqp mailparse xdebug
RUN docker-php-ext-configure intl \
    && docker-php-ext-install intl

RUN docker-php-ext-install opcache

RUN curl --location --output /usr/local/bin/composer https://getcomposer.org/composer.phar && chmod +x /usr/local/bin/composer
RUN curl -sS https://get.symfony.com/cli/installer | bash && mv /root/.symfony/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www

RUN PATH=$PATH:/var/www/vendor/bin:bin
