FROM php:7.4-cli

ENV COMPOSER_ALLOW_SUPERUSER=1
ARG COMPOSER_FLAGS="--prefer-dist --no-interaction"

WORKDIR /code

RUN apt-get update && apt-get install -y \
        git \
        unzip \
   --no-install-recommends && rm -r /var/lib/apt/lists/*

COPY ./docker/php/php.ini /usr/local/etc/php/php.ini

ADD https://getcomposer.org/installer composer-setup.php
RUN php composer-setup.php --1 \
  && mv composer.phar /usr/local/bin/composer

COPY composer.* ./
RUN composer install $COMPOSER_FLAGS --no-scripts --no-autoloader
COPY . .
RUN composer install $COMPOSER_FLAGS

CMD php /code/src/run.php --data=/data
