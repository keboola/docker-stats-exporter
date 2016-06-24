FROM php:7.0
MAINTAINER Vladimír Kriška <vlado@keboola.com>
ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update -q \
  && apt-get install unzip git -y

RUN apt-get install python-setuptools -y \
  && easy_install supervisor

WORKDIR /root

RUN curl -sS https://getcomposer.org/installer | php \
  && mv composer.phar /usr/local/bin/composer

COPY . /var/www/html

WORKDIR /var/www/html

RUN composer install --prefer-dist --no-interaction
