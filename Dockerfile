ARG image_tag=latest
FROM elifesciences/api-dummy_composer:${image_tag} AS build
FROM ghcr.io/elifesciences/php:8.0-cli@sha256:f681973227dbab578218a98f252dfd36fd42016b74be6d0044221a7d8cdcc4e6

USER elife
ENV PROJECT_FOLDER=/srv/api-dummy
RUN mkdir ${PROJECT_FOLDER}
WORKDIR ${PROJECT_FOLDER}

COPY --chown=elife:elife smoke_tests.sh ./
COPY --chown=elife:elife web/ web/
COPY --from=build --chown=elife:elife /app/vendor/ vendor/
COPY --chown=elife:elife data/ data/
COPY --chown=elife:elife src/ src/

USER www-data
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "web/"]
