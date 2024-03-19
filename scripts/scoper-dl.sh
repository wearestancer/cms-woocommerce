#! /usr/bin/bash

set -eu

scoper_version="0.18.7" # Last version supporting PHP 8.1 with release artifacts

# Do adjust the URL based on the latest release
curl -Lso scoper.phar "https://github.com/humbug/php-scoper/releases/download/${scoper_version}/php-scoper.phar"
curl -Lso scoper.phar.asc "https://github.com/humbug/php-scoper/releases/download/${scoper_version}/php-scoper.phar.asc"

# Add the issuer
gpg --quiet --keyserver hkps://keys.openpgp.org --recv-keys 74A754C9778AA03AA451D1C1A000F927D67184EE

# Check that the signature matches
gpg --quiet --verify scoper.phar.asc scoper.phar 2>/dev/null
result=$?

# Cleaning
rm scoper.phar.asc

if [ "$result" = 0 ]; then
  mv scoper.phar /usr/bin/scoper
  chmod +x /usr/bin/scoper
else
  rm scoper.phar
fi

exit "$result"
