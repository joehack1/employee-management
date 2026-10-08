#!/usr/bin/env bash
set -euo pipefail

# ---------------------------------------------------------------------------
# LeaveFlow — production build & optimize script
#
#   ./deploy.sh              build assets + cache config/routes/views
#   ./deploy.sh --migrate    same, and also run database migrations
#
# Run from the app directory ON THE PRODUCTION SERVER after pulling the repo.
# Prereqs on the server: native PHP (8.2+), Composer 2, Node 20+ / npm 10+,
# and MySQL/MariaDB reachable per .env.
# ---------------------------------------------------------------------------

APP_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$APP_DIR"

step() { printf '\n\033[1;36m==> %s\033[0m\n' "$*"; }

command -v php >/dev/null 2>&1     || { echo "✗ php not found on PATH"; exit 1; }
command -v composer >/dev/null 2>&1 || { echo "✗ composer not found on PATH"; exit 1; }
command -v npm >/dev/null 2>&1      || { echo "✗ npm not found on PATH"; exit 1; }

MIGRATE=0
[ "${1:-}" = "--migrate" ] && MIGRATE=1

# 1) Composer: production deps only, optimized classmap ---------------
step "Composer (--no-dev, optimized autoloader)"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# 2) Frontend ----------------------------------------------------------
step "Frontend build (npm)"
if [ -f package-lock.json ]; then
    npm ci --no-audit --no-fund || npm install --no-audit --no-fund
else
    npm install --no-audit --no-fund
fi
npm run build

# 3) Storage link (public/user uploads) -------------------------------
step "Storage symlink"
php artisan storage:link 2>/dev/null || true

# 4) Artisan caches (config + routes + views + events) ----------------
step "Caching config, routes, views, events (php artisan optimize)"
php artisan optimize

# Actually rebuild the route cache to catch any deferred sources
php artisan route:cache

# 5) Database migrations -----------------------------------------------
step "Migrations"
if [ "$MIGRATE" = "1" ]; then
    php artisan migrate --force
else
    echo "Skipped (pass --migrate to run migrations)."
fi

# 6) Strip dev-only artifacts that must never be served ----------------
step "Removing dev artifacts"
rm -f public/hot public/fonts-manifest.dev.json
echo "Removed: public/hot, public/fonts-manifest.dev.json (if present)."

# 7) Verify --------------------------------------------------------------
step "Verification"
php artisan about --only=environment,optimization 2>/dev/null || php artisan about
echo
echo "Build verified: public/build/assets/"
ls -1 public/build/assets/

cat <<'EOF'

--------------------------------------------------------------------------
Next steps on the production server:
  1. Make sure your real .env is in place (see .env.production template):
       APP_ENV=production  APP_DEBUG=false  APP_URL=https://your-domain
     then:  php artisan key:generate   (if APP_KEY is empty)
     and:   php artisan config:cache   (after ANY .env change)
  2. Web server: point docroot at  public/  (nginx/apache/Caddy/Forge/Herd).
     Do NOT run `php artisan serve` in production.
  3. Scheduler (required for leave accrual) — add this cron line:
       * * * * * php <APP_DIR>/artisan schedule:run >> /dev/null 2>&1
  4. File permissions (owned by the web user, e.g. www-data):
       chown -R <user>:<group> storage bootstrap/cache
       chmod -R 775 storage bootstrap/cache
  5. Never upload:  .env, public/hot, public/fonts-manifest.dev.json,
     storage/logs/*, .git, node_modules, bootstrap/cache/*.php (regenerated).
EOF