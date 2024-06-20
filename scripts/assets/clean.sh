#! /bin/sh

set -eu

cat <&0 | sed "s/^define[(] 'STANCER_ASSETS_VERSION', .*/define\( 'STANCER_ASSETS_VERSION', '\$[current-timestamp]' \);/"
