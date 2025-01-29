.PHONY: dev stop test import-article import-reviewed-preprint

dev:
	docker compose up

stop:
	docker compose down

test:
	docker compose run --rm app ./project_tests.sh

import-article:
	chmod a+w data/articles/
	docker compose run app ./bin/import $(ARTICLE_ID)

import-reviewed-preprint:
	@if [ "${REVIEWED_PREPRINT_ID}" = "" ]; then echo "Expected REVIEWED_PREPRINT_ID"; exit 1; fi
	curl https://api.elifesciences.org/reviewed-preprints/$(REVIEWED_PREPRINT_ID) | jq . > data/reviewed-preprints/$(REVIEWED_PREPRINT_ID).json

vendor:
	composer install
