REPO_PREFIX=scottaubrey/elifesciences-api-dummy
COMMIT=develop


build:
	docker build -f Dockerfile.build . -t $(REPO_PREFIX):$(COMMIT)

test:
	docker build -f Dockerfile.build --target tests . -t $(REPO_PREFIX):tests
	docker run --rm $(REPO_PREFIX):tests
	docker image rm $(REPO_PREFIX):tests

push: build
	docker push $(REPO_PREFIX):$(COMMIT)

buildx-and-push:
	docker buildx build --push --platform linux/amd64,linux/arm64  -f Dockerfile.build . -t $(REPO_PREFIX):$(COMMIT) -t $(REPO_PREFIX):latest


.PHONY: test build push
