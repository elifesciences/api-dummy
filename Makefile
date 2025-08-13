.PHONY: dev
dev:
	docker compose up

.PHONY: stop
stop:
	docker compose down

.PHONY: lint
lint:
	vendor/bin/phpcs --standard=phpcs.xml.dist --warning-severity=0 -p src/ web/ test/

.PHONY: lint-fix
lint-fix:
	vendor/bin/phpcbf --standard=phpcs.xml.dist --warning-severity=0 -p src/ web/ test/

.PHONY: test
test:
	docker compose run --rm app ./project_tests.sh

.PHONY: import-article 
import-article:
	chmod a+w data/articles/
	docker compose run app ./bin/import $(ARTICLE_ID)

.PHONY: import-reviewed-preprint
import-reviewed-preprint:
	$(if $(REVIEWED_PREPRINT_ID),,$(error REVIEWED_PREPRINT_ID make variable needs to be set))
	curl https://api.elifesciences.org/reviewed-preprints/$(REVIEWED_PREPRINT_ID) | jq . > data/reviewed-preprints/$(REVIEWED_PREPRINT_ID).json

vendor:
	composer install
