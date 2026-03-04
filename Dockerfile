FROM composer:2.9 AS build

COPY composer.json composer.lock ./
RUN composer --no-interaction install --no-suggest --prefer-dist

FROM ghcr.io/elifesciences/php:8.0-cli@sha256:7822d0bab29b09f5ad6c4f338c8fabdd277c1ac3fa00f3b16b598ed4d62739d0 AS base

USER elife

ENV PROJECT_FOLDER=/srv/api-dummy
WORKDIR ${PROJECT_FOLDER}

COPY --chown=elife:elife smoke_tests.sh ./
COPY --chown=elife:elife web/ web/
COPY --from=build --chown=elife:elife /app/vendor/ vendor/
COPY --chown=elife:elife data/ data/
COPY --chown=elife:elife src/ src/

USER www-data
EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "web/"]

FROM base AS prod

FROM base AS test

USER root
RUN mkdir -p build && chown www-data:www-data build

USER www-data
COPY --chown=elife:elife phpcs.xml.dist phpunit.xml.dist project_tests.sh ./
COPY --chown=elife:elife test/ test/

CMD ["./project_tests.sh"]

FROM test AS dev

COPY --from=build /usr/bin/composer /usr/bin/composer

CMD ["php", "-S", "0.0.0.0:8080", "-t", "web/"]
