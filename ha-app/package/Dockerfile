# Sorted: Laravel + PHP 8.3 (FPM) + nginx on Alpine.
# The app arrives ready-built (vendor/ and public/build/ made on the Mac by
# build.sh), so this image only installs PHP and nginx and copies files in.
#
# The base image is fixed on purpose (no ARG BUILD_FROM): Home Assistant's
# own base images use s6 as their entrypoint, which this app doesn't need.
FROM alpine:3.22

RUN apk add --no-cache \
        nginx \
        php83 \
        php83-fpm \
        php83-ctype \
        php83-curl \
        php83-dom \
        php83-fileinfo \
        php83-iconv \
        php83-mbstring \
        php83-opcache \
        php83-openssl \
        php83-pcntl \
        php83-pdo \
        php83-pdo_sqlite \
        php83-posix \
        php83-session \
        php83-simplexml \
        php83-sqlite3 \
        php83-tokenizer \
        php83-xml \
        php83-xmlwriter \
    && ln -sf /usr/bin/php83 /usr/bin/php

# nginx, PHP-FPM, PHP settings, the scheduler's crontab and the start script.
COPY rootfs/ /
# First-run database (only used when /data has no database yet).
COPY seed/database.sqlite /opt/sorted/seed.sqlite
# The built Laravel app.
COPY app/ /app/

# Files copied from a Mac or a network share can arrive readable only by their
# owner; nginx and PHP run as the "nginx" user, so open them up first.
RUN chmod -R a+rX /app /etc/nginx /etc/php83 \
    && chmod 755 /run.sh \
    && chmod 600 /etc/crontabs/nginx \
    && chown -R nginx:nginx /app/storage /app/bootstrap/cache

WORKDIR /app
CMD ["/run.sh"]
