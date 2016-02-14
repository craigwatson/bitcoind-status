#!/bin/bash

composer install

PHP_FILES=$(find . -path ./vendor -prune -o -type f -iname "*.php" -print)

echo "--- PHP Syntax"
for PHP_FILE in ${PHP_FILES}; do
  php -l ${PHP_FILE}
  if [ $? -ne 0 ]; then
    exit 1
  fi
done

echo "--- PHP Standards"
for PHP_FILE in ${PHP_FILES}; do
  BASENAME=$(basename ${PHP_FILE})
  if [ "${BASENAME}" != "easybitcoin.php" ]; then
    ./vendor/bin/phpcs --colors -n ${PHP_FILE}
    if [ $? -ne 0 ]; then
      exit 1
    else
      echo "No PEAR standards failures in ${PHP_FILE}"
    fi
  fi
done
