#! /bin/sh

cat <&0 \
  | sed 's/^"Project-Id-Version.*"/"Project-Id-Version: $[last-commit-hash]\\n"/' \
  | sed 's/^"POT-Creation-Date.*"/"POT-Creation-Date: $[first-commit-date]\\n"/' \
  | sed 's/^"PO-Revision-Date.*"/"PO-Revision-Date: $[last-commit-date]\\n"/' \
  | sed 's/^"Last-Translator.*"/"Last-Translator: $[last-commit-author]\\n"/'
