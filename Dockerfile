FROM elifesciences/php_cli:20180129144044

USER elife
RUN mkdir /srv/api-dummy
WORKDIR /srv/api-dummy
COPY --chown=elife:elife composer.json composer.lock /srv/api-dummy/
RUN composer install --classmap-authoritative --no-dev
COPY --chown=elife:elife . /srv/api-dummy

USER www-data
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "web/"]
