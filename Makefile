REPO_PREFIX=scottaubrey/elifesciences-api-dummy
COMMIT=develop


build:
	docker build -f Dockerfile . -t $(REPO_PREFIX):$(COMMIT)

test:
	docker build -f Dockerfile --target tests . -t $(REPO_PREFIX):tests
	docker run --rm $(REPO_PREFIX):tests
	docker image rm $(REPO_PREFIX):tests
	docker-compose -f docker-compose.yml up -d
	docker-compose -f docker-compose.yml exec -T cli ./smoke_tests.sh || exit_code=$$?; docker-compose -f docker-compose.yml down; exit $$exit_code

push: build
	docker push $(REPO_PREFIX):$(COMMIT)

buildx-and-push:
	docker buildx build --push --platform linux/amd64,linux/arm64  -f Dockerfile . -t $(REPO_PREFIX):$(COMMIT) -t $(REPO_PREFIX):latest


.PHONY: test build push
