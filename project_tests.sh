#!/usr/bin/env bash
set -e

rm -f build/*.xml

make lint
php -d memory_limit=128M vendor/bin/phpunit --log-junit build/phpunit.xml
