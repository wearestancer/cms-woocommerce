#! /bin/bash

set -eu

current_timestamp=`date -u +%s`
current_version=`grep '"version":' package.json | awk -F\" '{ print $4 }'`

# output
cat <&0 | sed "s/\$\[current-timestamp\]/${current_timestamp}/g" \
        | sed "s/\$\[current-version\]/${current_version}/g"
