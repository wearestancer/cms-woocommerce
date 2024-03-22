#! /usr/bin/bash

set -eu

scoper_version="0.17.5" # Last version supporting PHP 7.4 with release artifacts

# Exit now if the command exists
exists=$(which scoper &>/dev/null; echo $?)

if [ "$exists" = 0 ]; then
  exit 0;
fi

# Do adjust the URL based on the latest release
curl -Lso scoper.phar "https://github.com/humbug/php-scoper/releases/download/${scoper_version}/php-scoper.phar"
curl -Lso scoper.phar.asc "https://github.com/humbug/php-scoper/releases/download/${scoper_version}/php-scoper.phar.asc"

mv scoper.phar /usr/bin/scoper
chmod +x /usr/bin/scoper
