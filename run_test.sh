#!/bin/bash

echo "--- PHP Syntax"
for PHP_FILE in $(find . -type f -iname "*.php"); do
  php -l ${PHP_FILE}
  if [ $? -ne 0 ]; then
    exit 1
  fi
done
