FROM alpine:3.15

#### ARG & ENV

# AMQP Extension Support (overriden by .env file)
ARG AMQP_ENABLED=0
# Node.js default Package Manager (overriden by .env file)
ARG NODEJS_PACKAGE_MANAGER=yarn
# Docker Git Identity (needed for composer)
ARG DOCKER_GIT_EMAIL=docker@git.com
ARG DOCKER_GIT_NAME=docker_git

# [ini] Opcache Default Config (overriden by .env file)
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0

##############

RUN apk add bash

RUN apk --update add openssh-client curl git \ 
    # core php modules
    php-apache2 php-cli php-phar php-openssl \
    # performance php modules
    php-opcache \
    # extra php modules
    php-json php-mbstring \
    # symfony core modules
    php-ctype php-iconv php-session php-tokenizer \
    php-simplexml php-xml php-dom php-intl \
    # symfony optional/extra
    php-posix php-xmlwriter \
    # db modules
    php-pdo php-pdo_pgsql php-pgsql && \
    # other magic
    rm -f /var/cache/apk/* && \
    # composer install
    curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer && \
    # www dir setup
    mkdir -p /var/www/html/ && chown -R apache:apache /var/www/html

# install NodeJS with Yarn or NPM
RUN set -eux & apk add --no-cache nodejs ${NODEJS_PACKAGE_MANAGER}

# install AMQP
RUN if [ "${AMQP_ENABLED}" = "1" ] ; then apk add php7-pecl-amqp; fi

# symfony cli install
RUN curl -sS https://get.symfony.com/cli/installer | bash && mv /root/.symfony/bin/symfony /usr/local/bin/symfony

# setup git
RUN git config --global user.name ${DOCKER_GIT_EMAIL}
RUN git config --global user.email ${DOCKER_GIT_NAME}

# copy config files
COPY etc/php/conf.d/opcache.ini /etc/php7/conf.d/opcache.ini
COPY etc/apache2/httpd.conf /etc/apache2/httpd.conf
COPY etc/apache2/sites/ /etc/apache2/sites/
COPY etc/php/php.ini /etc/php7/php.ini
COPY scripts/entrypoint.sh /opt/entrypoint.sh

EXPOSE 80

WORKDIR /var/www/html/

CMD ["/opt/entrypoint.sh"]