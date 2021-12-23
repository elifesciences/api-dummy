
# COMPOSER generate autoloader and vendor files
FROM composer:2.2.1@sha256:3aab311114daad07d7a1c8331cc3a21265496066320fc7cd22203434f636e6bb as build
COPY composer.json \
    composer.lock \
    ./
RUN composer --no-interaction install ${composer_dev_arg} --ignore-platform-reqs --no-autoloader --no-suggest --prefer-dist
COPY test/ test/
RUN composer --no-interaction dump-autoload ${composer_dev_arg} --classmap-authoritative


# build the main image here
FROM scottaubrey/elifesciences-php:7.4-cli@sha256:df34b2b14b20a9e8a4017594e9ede8074d52a8db4f6fc2e553f29f4a0dea5cf4 as image

USER root
ENV PROJECT_FOLDER=/srv/api-dummy
RUN mkdir ${PROJECT_FOLDER}
RUN chown elife:elife ${PROJECT_FOLDER}
WORKDIR ${PROJECT_FOLDER}

COPY --chown=elife:elife smoke_tests.sh ./
COPY --chown=elife:elife web/ web/
COPY --from=build --chown=elife:elife /app/vendor/ vendor/
COPY --chown=elife:elife data/ data/
COPY --chown=elife:elife src/ src/


# this is an image output designed for tests
FROM image as tests

USER root
RUN mkdir -p build && \
    chown --recursive elife:elife .
COPY --chown=elife:elife test/ test/
COPY --chown=elife:elife phpcs.xml.dist phpunit.xml.dist project_tests.sh ./

USER elife
CMD ["./project_tests.sh"]


# this is the final image output for the build
FROM image as output
USER www-data
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "web/"]
