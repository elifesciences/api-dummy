#!/usr/bin/env bash
set -ex

[ $(curl --write-out %{http_code} --silent --output /dev/null $(hostname)/labs-experiments) == 200 ]
