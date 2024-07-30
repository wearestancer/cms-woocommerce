#! /bin/sh

set -eu

cat <&0 \
  | sed "s/^define[(] 'STANCER_ASSETS_VERSION', .*/define\( 'STANCER_ASSETS_VERSION', '\$[current-timestamp]' \);/g" \
  | sed "s/^define[(] 'STANCER_WC_VERSION', .*/define\( 'STANCER_WC_VERSION', '\$[current-version]' \);/g" \
  | sed "s/\* Version:.*/* Version:     \$[current-version]/g" \
  | sed "s/^Stable tag:.*/Stable tag: \$[current-version]/g"
