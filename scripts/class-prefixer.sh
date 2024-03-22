#! /usr/bin/bash

set -eu

current_dir=$(dirname $(realpath $0))
search_dir=$(dirname "$current_dir")
# We find all the files with a dependancy to Stancer.
files=$(grep -REl '^use Stancer;$' --exclude-dir=node_modules/ --exclude-dir=vendor/ "$search_dir")

#we create a new file for our archive with the dependency scoped
for file in $files; do
  sed -i'.old' -e 's/use Stancer;/use Stancer\\Scoped\\Isolated\\Stancer;/g' "$file"
done

rm -rf vendor-prefixer
mv build vendor-prefixer
