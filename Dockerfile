ARG image_tag=latest
FROM elifesciences/api-dummy_composer:${image_tag} AS build
FROM elifesciences/php_7.0_cli:656bb4bdf1e49a5e80337e2a7c4f44f10c3f52b0

ENV PROJECT_FOLDER=/srv/api-dummy

WORKDIR ${PROJECT_FOLDER}

USER root
RUN chown elife:elife .

COPY --chown=elife:elife smoke_tests.sh ./
COPY --chown=elife:elife web/ web/
COPY --from=build --chown=elife:elife /app/vendor/ vendor/
COPY --chown=elife:elife data/ data/
COPY --chown=elife:elife src/ src/

USER www-data
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "web/"]
