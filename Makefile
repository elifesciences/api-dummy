.PHONY: dev test

dev:
	docker compose up

test:
	docker compose run --rm app ./project_tests.sh
