.PHONY: dev test import-article

dev:
	docker compose up

test:
	docker compose run --rm app ./project_tests.sh

import-article:
	docker compose run app ./bin/import $(ARTICLE_ID)
