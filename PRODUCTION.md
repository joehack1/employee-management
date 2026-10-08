# LEAVE_FLOW PRODUCTION RUNBOOK

Everything below assumes a Ubuntu/Debian-style VPS or shared host with native
PHP ≥ 8.2, Composer 2, Node 20+, and MySQL/MariaDB. Do **not** use Docker or
`php artisan serve` in production.

---

## 1. Server environment

```bash
php -v    # needs pdo_mysql
composer -V
node -v && npm -v
```

Create a dedicated DB user instead of `root`:

```sql
CREATE USER 'leave_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON leave_db.* TO 'leave_user'@'localhost';
FLUSH PRIVILEGES;
```

> The app is MySQL/MariaDB only — no SQLite anywhere (tests use `leave_db_test`).

## 2. Ship the code (git-based)

```bash
git push origin main          # from your workstation
# on the server:
git clone <repo> /var/www/leaveflow && cd /var/www/leaveflow
```

A template env (gitignored) is provided: `cp .env.production .env` — but if
`.env.production` is not on the server, create `.env` from `.env.example` and
apply the production values from the table below.

Set values **at minimum**:

| Key | Value | Why |
|---|---|---|
| `APP_ENV` | `production` | production error handling |
| `APP_DEBUG` | `false` | never expose stack traces |
| `APP_URL` | `https://your-domain` | asset/route generation |
| `APP_KEY` | fresh (`php artisan key:generate`) | encryption (sessions, passwords) |
| `SESSION_SECURE_COOKIE` | `true` | only send session cookie over HTTPS |
| `DB_*` | dedicated user, not root | least privilege |
| `MAIL_*` | your working SMTP credentials (`mail.indepthresearch.co.ke:465`) | password reset links |

## 3. Build + optimize

```bash
chmod +x deploy.sh
./deploy.sh --migrate
```

This runs: `composer install --no-dev --optimize-autoloader` → `npm ci` +
`npm run build` → `storage:link` → `php artisan optimize` (+`route:cache`) →
`migrate --force` → removes dev artifacts (`public/hot`,
`public/fonts-manifest.dev.json`).

Equivalent manual steps:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci && npm run build
php artisan storage:link
php artisan optimize
php artisan migrate --force
rm -f public/hot public/fonts-manifest.dev.json
```

## 4. Web server

Point the document root at **`public/`** (the front controller). Example nginx:

```nginx
server {
    listen 443 ssl http2;
    server_name your-domain;
    root /var/www/leaveflow/public;
    index index.php;
    client_max_body_size 10M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ /\.(?!well-known).* { deny all; }
    location ~ ^/storage/ { try_files $uri =404; }
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options SAMEORIGIN;
}
```

After any change to `.env`:

```bash
php artisan config:cache
php artisan route:cache
```

## 5. Scheduler — REQUIRED

The leave-accrual job runs monthly; without the scheduler, accruals stop.

Cron (runs the Laravel scheduler every minute):

```
* * * * * php /var/www/leaveflow/artisan schedule:run >> /dev/null 2>&1
```

Verify: `php artisan schedule:list`

## 6. Permissions

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

`public/storage` must be a symlink to `storage/app/public` (`storage:link` does this).

## 7. What must NEVER be uploaded/served

- `.env` (secrets)
- `public/hot`, `public/fonts-manifest.dev.json` (Vite dev markers)
- `storage/logs/*`, `storage/framework/sessions/*`, `storage/framework/cache/*`
- `.git/`, `node_modules/`
- `bootstrap/cache/*.php` are ephemeral caches — regenerate on the server

## 8. Deployment checklist

- [ ] `./deploy.sh --migrate` finished without errors
- [ ] `php artisan about` shows `Environment: production` and caches on
- [ ] `curl -I https://your-domain/login` → 200/302, Content-Type text/html
- [ ] Compiled CSS/JS load: `/build/assets/app-*.css` → 200 (not 404)
- [ ] `php artisan schedule:list` lists `leave:accrue-monthly`
- [ ] Login → dashboard works; password reset link lands in the inbox
- [ ] Confirm no `cdn.tailwindcss.com` / `jsdelivr` in page source (should already be gone)

## 9. Troubleshooting

| Symptom | Cause / fix |
|---|---|
| `/build/assets/*` returns 404 | `public/build` missing → run `npm run build` (deploy.sh step 2) |
| "Vite manifest not found" | build output not deployed → rerun `./deploy.sh` |
| Page references `:5173` or localhost URLs | `public/hot` exists → `rm -f public/hot; php artisan optimize` |
| Blank/500 after env change | `php artisan config:clear` then re-`config:cache` |
| Blade errors after deploy | `php artisan view:cache` (deploy.sh does it via `optimize`) |
| Route 404s | route cache is stale → `php artisan route:cache` |

## 10. Staging the same way

For a test deploy, use the same steps with `APP_ENV=staging`,
`APP_DEBUG=true`, and `DB_DATABASE=leave_db_test` — the test suite already
runs against MySQL (`leave_db_test`) and passes.