#!/bin/sh
# Sorted start-up. Runs every time the app starts.
#   1. Make sure the database and app key exist in /data (kept across
#      restarts and updates, and included in HA backups), loading an
#      imported database first if one has been dropped in.
#   2. Write .env, run migrations, warm Laravel's caches.
#   3. Start the scheduler (cron), PHP-FPM, then nginx in the foreground.
#
# The defaults are for the Alpine image; the variables only exist so the
# same script can be tested elsewhere.
set -e

APP_DIR="${APP_DIR:-/app}"
DATA_DIR="${DATA_DIR:-/data}"
# The app's config folder (the "app_configs" Samba share in Home Assistant).
CONFIG_DIR="${CONFIG_DIR:-/config}"
WEB_USER="${WEB_USER:-nginx}"
PHP_BIN="${PHP_BIN:-php83}"
PHP_FPM_BIN="${PHP_FPM_BIN:-php-fpm83}"
NGINX_BIN="${NGINX_BIN:-nginx}"
START_SERVICES="${START_SERVICES:-yes}"

log() { echo "[sorted] $*"; }

cd "$APP_DIR"
mkdir -p "$DATA_DIR"
IMPORTED=no

# --- 1. Database and key -----------------------------------------------------
# A database dropped into the config folder as import.sqlite replaces the
# current one. The current one moves to the config folder as
# before-import.sqlite, and the import is renamed imported.sqlite so it only
# loads once. The image is public, so this is how real data gets in.
if [ -f "$CONFIG_DIR/import.sqlite" ]; then
    log "Importing import.sqlite from the config folder"
    for ext in "" -journal -wal -shm; do
        if [ -f "$DATA_DIR/database.sqlite$ext" ]; then
            mv "$DATA_DIR/database.sqlite$ext" "$CONFIG_DIR/before-import.sqlite$ext"
        fi
    done
    cp "$CONFIG_DIR/import.sqlite" "$DATA_DIR/database.sqlite"
    mv "$CONFIG_DIR/import.sqlite" "$CONFIG_DIR/imported.sqlite"
    IMPORTED=yes
fi

if [ ! -f "$DATA_DIR/database.sqlite" ]; then
    log "First start: creating an empty database"
    : > "$DATA_DIR/database.sqlite"
fi

if [ ! -s "$DATA_DIR/app_key" ]; then
    log "First start: generating the app key"
    echo "base64:$(head -c 32 /dev/urandom | base64)" > "$DATA_DIR/app_key"
    chmod 600 "$DATA_DIR/app_key"
fi

# --- 2. Laravel ---------------------------------------------------------------
# .env.base holds the fixed settings (made by build.sh, including APP_URL).
{
    cat "$APP_DIR/.env.base"
    echo "APP_KEY=$(cat "$DATA_DIR/app_key")"
    echo "DB_DATABASE=$DATA_DIR/database.sqlite"
} > "$APP_DIR/.env"

for d in storage/app/public storage/framework/cache/data \
         storage/framework/views storage/logs bootstrap/cache; do
    mkdir -p "$APP_DIR/$d"
done

# Sessions live in /data so a restart doesn't invalidate the tablet's open
# page (otherwise its first tap afterwards fails with "419 Page Expired").
mkdir -p "$DATA_DIR/sessions"
if [ ! -L "$APP_DIR/storage/framework/sessions" ]; then
    rm -rf "$APP_DIR/storage/framework/sessions"
    ln -s "$DATA_DIR/sessions" "$APP_DIR/storage/framework/sessions"
fi

# Open pages poll public/sync.txt for changes (PHP rewrites it, nginx serves it).
touch "$APP_DIR/public/sync.txt"

# The web server's user must own everything it writes to: the database file,
# SQLite's journal next to it, sessions, compiled views, the sync stamp.
chown -R "$WEB_USER:$WEB_USER" "$DATA_DIR" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" \
    "$APP_DIR/public/sync.txt"

run_artisan() {
    if [ "$(id -u)" = "0" ] && [ "$WEB_USER" != "root" ]; then
        su -s /bin/sh "$WEB_USER" -c "cd '$APP_DIR' && $PHP_BIN artisan $*"
    else
        $PHP_BIN artisan "$@"
    fi
}

log "Running database migrations"
run_artisan migrate --force --no-interaction

# An imported database's open chores can be months old. Sorted dates each
# chore's next occurrence from the previous one's date, so old chores would
# come back one day at a time. On import only, move them to today.
if [ "$IMPORTED" = "yes" ]; then
    $PHP_BIN -r "
        \$db = new PDO('sqlite:' . \$argv[1]);
        try {
            \$n = \$db->exec(\"UPDATE tasks SET date = date('now') || ' 00:00:00'
                             WHERE status = 'todo' AND date IS NOT NULL AND date < date('now')\");
            echo '[sorted] Moved ' . \$n . ' overdue open chores from the imported database to today' . PHP_EOL;
        } catch (Throwable \$e) {
            echo '[sorted] Could not re-date imported chores: ' . \$e->getMessage() . PHP_EOL;
        }
    " "$DATA_DIR/database.sqlite"
fi

log "Caching config, routes and views"
run_artisan optimize:clear --quiet
run_artisan optimize --quiet

# Make sure every template has its next task (also runs nightly via cron).
run_artisan tasks:generate --quiet || log "tasks:generate failed (see above)"

[ "$START_SERVICES" = "yes" ] || { log "Setup finished (services not started)"; exit 0; }

# --- 3. Services ----------------------------------------------------------------
log "Starting scheduler"
crond -b -l 8

log "Starting PHP-FPM"
$PHP_FPM_BIN -F &

log "Starting nginx on port 8080"
exec $NGINX_BIN -g 'daemon off;'
