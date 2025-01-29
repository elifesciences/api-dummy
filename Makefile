.PHONY: dev
dev:
	docker compose up

.PHONY: stop
stop:
	docker compose down

.PHONY: test
test:
	docker compose run --rm app ./project_tests.sh

.PHONY: import-article 
import-article:
	chmod a+w data/articles/
	docker compose run app ./bin/import $(ARTICLE_ID)

.PHONY: import-reviewed-preprint
import-reviewed-preprint:
	@if [ "${REVIEWED_PREPRINT_ID}" = "" ]; then echo "Expected REVIEWED_PREPRINT_ID"; exit 1; fi
	curl https://api.elifesciences.org/reviewed-preprints/$(REVIEWED_PREPRINT_ID) | jq . > data/reviewed-preprints/$(REVIEWED_PREPRINT_ID).json

vendor:
	composer install
