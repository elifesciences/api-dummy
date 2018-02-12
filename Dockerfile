FROM elifesciences/php_cli:8e6cd52684b79b923fe87f254ea0b832a085568c

USER elife
RUN mkdir /srv/api-dummy
WORKDIR /srv/api-dummy
COPY --chown=elife:elife composer.json composer.lock /srv/api-dummy/
RUN composer-install
COPY --chown=elife:elife . /srv/api-dummy
RUN composer-post

USER www-data
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "web/"]
