#!/usr/bin/env bash
set -ex

[ $(curl --write-out %{http_code} --silent --output /dev/null http://localhost:8080/labs-posts) == 200 ]
