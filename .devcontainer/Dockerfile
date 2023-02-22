# syntax=docker/dockerfile:1.3

ARG VARIANT="7.4"

FROM mcr.microsoft.com/devcontainers/php:0-${VARIANT}

RUN sed -i 's/9000/9003/' /usr/local/etc/php/conf.d/xdebug.ini \
 && sed -i 's/^xdebug\.start_with_request = yes/xdebug.start_with_request = trigger/' /usr/local/etc/php/conf.d/xdebug.ini

RUN --mount=type=cache,target=/var/lib/apt/lists/ curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash - \
 && apt-get update && apt-get install -y nodejs

RUN npm install --global pnpm
