#! /bin/sh

stored_version=$(grep '^Tested up to:' README.txt | cut -d: -f2 | xargs)
version=$(printf "${stored_version}\n${WORDPRESS_VERSION}\n" | sort -Vr | head -n1)

sed -i "s/^Tested up to:.*/Tested up to: ${version}/" README.txt
