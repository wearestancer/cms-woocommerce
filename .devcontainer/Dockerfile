# syntax=docker/dockerfile:1.3

ARG DOCKER_PROXY=docker.io/wearestancer
ARG PHP_VERSION=8.1

FROM ${DOCKER_PROXY}/wordpress:php${PHP_VERSION}

ARG TARGET_USER=vscode

# Install sudo
RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked set -eu \
      && apt update \
      && apt upgrade -y \
      && apt install -y --no-install-recommends sudo vim \
      && sed -i 's/%sudo.*/%sudo   ALL=(ALL:ALL) NOPASSWD: ALL/' /etc/sudoers

# Create user
RUN useradd --create-home --shell /bin/bash --uid 1000 --groups sudo,www-data $TARGET_USER

# Ensure target folder is available
RUN mkdir -p /var/www/html/wp-content/plugins/stancer

COPY ./fs/post-attach /post-attach
RUN chmod +x /post-attach && chown ${TARGET_USER}: /var/www/html/wp-content/plugins/stancer

USER $TARGET_USER
