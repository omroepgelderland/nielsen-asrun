#!/bin/bash

set -euo pipefail

export COMPOSER_NO_DEV=0
composer8.1 install
composer8.1 check-platform-reqs
php8.1 vendor/bin/parallel-lint \
    --exclude vendor/ \
    ./
vendor/bin/phpstan analyse
# php8.1 vendor/bin/phpcs --standard=ruleset.xml -n
php8.1 vendor/bin/phpunit tests
php8.2 vendor/bin/phpunit tests
php8.3 vendor/bin/phpunit tests
php8.4 vendor/bin/phpunit tests
