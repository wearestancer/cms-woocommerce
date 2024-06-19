#! /bin/bash

set -eu

current_timestamp=`date -u +%s`

# output
cat <&0 | sed "s/\$\[current-timestamp\]/${current_timestamp}/g"
