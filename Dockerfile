FROM elifesciences/php_cli:d83fc4714914898b0842199578e1cc88d9feab2a

USER elife
ENV PROJECT_FOLDER=/srv/api-dummy
RUN mkdir ${PROJECT_FOLDER}
WORKDIR ${PROJECT_FOLDER}
COPY --chown=elife:elife composer.json composer.lock ${PROJECT_FOLDER}/
RUN composer-install
COPY --chown=elife:elife . ${PROJECT_FOLDER}
RUN composer-post

USER www-data
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "web/"]
