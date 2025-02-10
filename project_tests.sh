#!/usr/bin/env bash
set -e

rm -f build/*.xml

vendor/bin/phpcs --standard=phpcs.xml.dist --warning-severity=0 -p src/ web/
vendor/bin/phpcs --standard=phpcs.xml.dist --warning-severity=0 -p test/
php -d memory_limit=128M vendor/bin/phpunit --log-junit build/phpunit.xml
