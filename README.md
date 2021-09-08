eLife 2.0 Dummy API
===================

[![Build Status](http://ci--alfred.elifesciences.org/buildStatus/icon?job=test-api-dummy)](http://ci--alfred.elifesciences.org/job/test-api-dummy)

This contains a dummy implementation of the [eLife 2.0 API](https://github.com/elifesciences/api-raml).

##Import article

```$sh
cd /srv/api-dummy
./bin/import 09560
```

The above command should result in a data fixture for article 09560 being created at `/srv/api-dummy/data/articles/09560.json`
