FROM keboola/base-php56
MAINTAINER Miro Cillik <miro@keboola.com>

ADD . /code
WORKDIR /code

RUN composer selfupdate
RUN composer install --no-interaction

CMD php ./src/run.php --data=/data
