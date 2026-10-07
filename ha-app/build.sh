#!/usr/bin/env bash
# Build the Sorted Home Assistant app into ha-app/dist/sorted.
#
# Run it on the Mac from anywhere:   ~/sorted/ha-app/build.sh
# Needs: php (8.2+), composer, npm, rsync. Your project files aren't changed:
# everything happens in a copy under ha-app/.build/.
#
# The front end has the app's address baked in (Ziggy), so build for the
# address the tablet will use. Default is the Pi; override with:
#   APP_URL=http://192.168.0.80:8080 ~/sorted/ha-app/build.sh
set -euo pipefail

HERE="$(cd "$(dirname "$0")" && pwd)"
PROJECT="$(cd "$HERE/.." && pwd)"
APP_URL="${APP_URL:-http://192.168.0.79:8080}"
WORK="$HERE/.build/app"
DIST="$HERE/dist/sorted"

need() { command -v "$1" >/dev/null 2>&1 || { echo "Missing: $1" >&2; exit 1; }; }
need php; need composer; need npm; need rsync

echo "==> Copying the project into $WORK"
mkdir -p "$WORK"
rsync -a --delete \
    --exclude '/node_modules' --exclude '/vendor' --exclude '/.env' \
    --exclude '/.idea' --exclude '/.claude' --exclude '/.git' --exclude '/ha-app' \
    --exclude '.DS_Store' --exclude '/public/build' --exclude '/public/hot' --exclude '/public/sync.txt' \
    --exclude '/storage/logs/*' --exclude '/storage/framework/sessions/*' \
    --exclude '/storage/framework/views/*' --exclude '/storage/framework/cache/data/*' \
    --exclude '/bootstrap/cache/*.php' --exclude '/database/database.sqlite' \
    "$PROJECT/" "$WORK/"

cd "$WORK"

# Only used during the build (Ziggy reads APP_URL when it writes ziggy.js).
cat > .env <<EOF
APP_NAME=Sorted
APP_ENV=production
APP_DEBUG=false
APP_URL=$APP_URL
DB_CONNECTION=sqlite
EOF

echo "==> PHP libraries (production only)"
composer install --no-dev --optimize-autoloader --no-interaction --no-progress
# If composer ever falls back to git clones, drop their history.
find vendor -name .git -type d -prune -exec rm -rf {} +

echo "==> Front end (APP_URL=$APP_URL)"
npm ci --no-audit --no-fund
npm run build

# Settings the app runs with. run.sh adds APP_KEY and DB_DATABASE at start-up.
cat > .env.base <<EOF
APP_NAME=Sorted
APP_ENV=production
APP_DEBUG=false
APP_URL=$APP_URL
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
LOG_CHANNEL=stderr
LOG_LEVEL=warning
DB_CONNECTION=sqlite
SESSION_DRIVER=file
SESSION_LIFETIME=525600
CACHE_STORE=file
QUEUE_CONNECTION=sync
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
MAIL_MAILER=log
EOF
rm -f .env

echo "==> Assembling $DIST"
rm -rf "$DIST"
mkdir -p "$DIST/seed"
cp -R "$HERE/package/." "$DIST/"
if [ -f "$PROJECT/database/database.sqlite" ]; then
    cp "$PROJECT/database/database.sqlite" "$DIST/seed/database.sqlite"
else
    : > "$DIST/seed/database.sqlite"
fi
rsync -a --exclude '/node_modules' "$WORK/" "$DIST/app/"
chmod -R u+rwX,go+rX "$DIST"
chmod 755 "$DIST/rootfs/run.sh"

VERSION="$(sed -n 's/^version: *"\{0,1\}\([^"]*\)"\{0,1\} *$/\1/p' "$DIST/config.yaml")"
SIZE="$(du -sh "$DIST" | cut -f1)"
echo ""
echo "Done: Sorted $VERSION, $SIZE, in $DIST"
echo "Copy that 'sorted' folder into the 'local_apps' share on Home Assistant (Samba share app)."
