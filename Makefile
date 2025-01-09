.PHONY: dev test import-article

dev:
	docker compose up

test:
	docker compose run --rm app ./project_tests.sh

import-article:
	chmod a+w data/articles/
	docker compose run app ./bin/import $(ARTICLE_ID)
