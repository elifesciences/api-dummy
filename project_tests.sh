#!/usr/bin/env bash
set -e

proofreader src/ test/ web/
vendor/bin/phpunit --log-junit build/phpunit.xml

