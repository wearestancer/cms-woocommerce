#! /bin/bash

set -eu

current_timestamp=`date -u +%s`
current_version=`grep '"version":' package.json | awk -F\" '{ print $4 }'`

requires_php_version=`grep '"php":' package.json | awk -F'>=' '{ print $2 }' | awk -F'"' '{ print $1 }'`
requires_wp_version=`grep '"wordpress":' package.json | awk -F'>=' '{ print $2 }' | awk -F'"' '{ print $1 }'`

# output
cat <&0 | sed "s/\$\[current-timestamp\]/${current_timestamp}/g" \
        | sed "s/\$\[current-version\]/${current_version}/g" \
        | sed "s/\$\[requires-php-version\]/${requires_php_version}/g" \
        | sed "s/\$\[requires-wp-version\]/${requires_wp_version}/g"
