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
	curl https://api.elifesciences.org/reviewed-preprints/$(REVIEWED_PREPRINT_ID) | jq . > data/reviewed-preprints/$(REVIEWED_PREPRINT_ID).json
