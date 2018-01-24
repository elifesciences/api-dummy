FROM elifesciences/php_cli

USER root
RUN mkdir /srv/api-dummy && chown elife:elife /srv/api-dummy

USER elife
WORKDIR /srv/api-dummy
COPY composer.json composer.lock /srv/api-dummy/
RUN composer install --classmap-authoritative --no-dev
COPY . /srv/api-dummy

USER www-data
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "web/"]
