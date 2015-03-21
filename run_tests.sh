#!/bin/bash

echo "--- PHP Syntax"
for PHP_FILE in $(find . -path ./vendor -prune -o -type f -iname "*.php" -print); do
  php -l ${PHP_FILE}
  if [ $? -ne 0 ]; then
    exit 1
  fi
done
