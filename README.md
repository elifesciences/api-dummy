eLife 2.0 Labs Experiments experiment
=====================================

This contains a simplified implementation of the eLife Labs Experiments API (based on https://github.com/elifesciences/elife-2.0-swagger) to test versioning/listing strategies.

It contains a single experiment, but the schema has two major version available:

* 1.0 didn't contain a `foo` property.
* 1.1 added an optional `foo` property that contains a string.
* 2.0 changed the `foo` property from a string to an array.

1. Execute `vagrant up`.
2. Execute `curl --include http://localhost:8080/labs-experiments/1` to find the latest version (ie 2) of experiment 1.
3. Execute `curl --include http://localhost:8080/labs-experiments/1 --header "Accept: application/vnd.elife.labs-experiment+json; version=1"` to get version 1 of experiment 1.
4. Execute `curl --include http://localhost:8080/labs-experiments` to see a listing.
5. Execute `curl --include http://localhost:8080/labs-experiments?foo=bar` to see a listing using a deprecated query string parameter (that doesn't actually do anythign).

Note unlike the Swagger docs, versioning doesn't include the minor version as it adheres to semver (eg a 1.0 client can understand the 1.1 response, it just ignores the `foo` property). It could be changed so that requesting 1.0 actually returns a 1.0 response (ie no `foo` property), but this seems like unnecessary complexity.

Blocks
------

Blocks are being versioned the experiment response: `paragraph` blocks are at version 1, while the `image` block is at version 2. There's no content negotiation here, so if a client only understands `image` version 1... what happens?
