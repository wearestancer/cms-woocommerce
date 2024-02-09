#! /bin/bash

file="$(git rev-parse --show-toplevel)/$1"

current_version=`grep '"version":' package.json | awk -F\" '{ print $4 }'`

first_date=`git log --pretty=format:'%ad' --date=format:'%Y-%m-%d %H:%M%z' -- "$file" | tail -n1`
hash=`git log --pretty=format:'%H' --date=format:'%Y-%m-%d %H:%M%z' -1 -- "$file"`
last_date=`git log --pretty=format:'%ad' --date=format:'%Y-%m-%d %H:%M%z' -1 -- "$file"`
last_author=`git log --pretty=format:'%aN <%aE>' --date=format:'%Y-%m-%d %H:%M%z' -1 -- "$file"`

# output
cat <&0 \
  | sed "s/\$\[last-commit-author\]/${last_author}/g" \
  | sed "s/\$\[last-commit-hash\]/${current_version} - ${hash}/g" \
  | sed "s/\$\[first-commit-date\]/${first_date}/g" \
  | sed "s/\$\[last-commit-date\]/${last_date}/g"
