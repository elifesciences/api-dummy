eLife 2.0 Dummy API
===================

[![Build Status](http://ci--alfred.elifesciences.org/buildStatus/icon?job=test-api-dummy)](http://ci--alfred.elifesciences.org/job/test-api-dummy)

This contains a dummy implementation of the [eLife 2.0 API](https://github.com/elifesciences/api-raml).

## Import article

```$sh
docker compose run app ./bin/import 09560
```

The above command should result in a data fixture for article 09560 being created at `data/articles/09560.json`

## Run locally

```$sh
docker compose up
```

Then visit [http://localhost:8080/articles](http://localhost:8080/articles) in your browser.

## Update local vendor for development

```$sh
docker compose -f docker-compose.dev.yml run composer install
```

## Run tests

```$sh
docker compose run --rm app ./project_tests.sh
```
