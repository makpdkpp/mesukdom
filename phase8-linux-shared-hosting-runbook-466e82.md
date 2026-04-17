# Phase 8 — Linux Shared Hosting (Plesk) Runbook Plan
This plan lists what must be configured/changed to run the app reliably on a Linux shared hosting environment with Plesk and no interactive shell access.

## 1) Deployment constraints & approach
- **Vendor dependencies**
  - You can use **Plesk Git deployment with Composer enabled** (preferred) to install `vendor/` on the server.
  - Alternative fallback: deploy with `vendor/` included (build on your machine/CI then upload).
  - Ensure `public/` is the web root and `.env` is **not** publicly accessible.
- **Migrations**
  - Must be run at least once after deploy.
  - Preferred: Plesk “Scheduled Task” to run `php artisan migrate --force` (one-time per deploy).
  - If you truly cannot run `artisan` commands on server, the fallback is: temporarily enable a **protected** admin-only endpoint to run migrations (requires code change; do only if needed).

## 2) Environment (.env) checklist (production)
- **Core**
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_URL=https://<your-domain>`
  - Set correct `DB_*` for MySQL/Postgres used by hosting.
- **Sessions/cache**
  - On shared hosting, prefer DB-backed:
    - `SESSION_DRIVER=database`
    - `CACHE_STORE=database` (or `file` if DB cache not desired)
- **Queues (critical)**
  - If you can run a long-lived worker via Supervisor: use Redis/DB normally.
  - On shared hosting (no daemons), use **DB queue + cron-driven worker**:
    - `QUEUE_CONNECTION=database`
    - Ensure tables exist: `jobs`, `failed_jobs`, `job_batches`.
- **Mail**
  - In production do not leave `MAIL_MAILER=log`.
  - Configure SMTP credentials in Plesk and set `MAIL_*` accordingly.
- **Stripe**
  - Configure in platform admin UI:
    - `stripe_enabled=true`
    - `stripe_mode=test|live`
    - `stripe_publishable_key`, `stripe_secret_key`, `stripe_webhook_secret` (whsec_...)

## 3) Plesk Cron Jobs (no supervisor) — required setup
You need 2 scheduled tasks.

### 3.1 Laravel Scheduler (runs every minute)
- **Command** (example):
  - `<php_path> /var/www/vhosts/<domain>/httpdocs/artisan schedule:run >> /dev/null 2>&1`
- **Frequency**: `* * * * *`
- **Why**: runs `routes/console.php` schedules (trial expiry reminders, invoice reminders, etc.).

### 3.2 Queue worker (runs every minute, single-shot)
- **Command** (example):
  - `<php_path> /var/www/vhosts/<domain>/httpdocs/artisan queue:work --stop-when-empty --sleep=0 --tries=3 >> /dev/null 2>&1`
- **Frequency**: `* * * * *`
- **Why**: processes webhook jobs (`ProcessStripeWebhookEventJob`) and reminder jobs. Without this, Stripe webhooks will be stored but not applied.

Notes:
- If you see overlapping executions, switch to `queue:work --once` and increase frequency, or use `withoutOverlapping()` patterns (requires code changes).

## 4) Web server / routing requirements
- **Document root** must be Laravel `public/`.
- Ensure HTTPS is enabled.
- Ensure the webhook endpoints are reachable publicly:
  - `POST /api/stripe/webhook`
  - `POST /api/line/webhook`

## 5) Stripe configuration checklist (production readiness)
- In Stripe dashboard:
  - Add webhook endpoint: `https://<domain>/api/stripe/webhook`
  - Subscribe at minimum:
    - `checkout.session.completed`
    - `customer.subscription.created`
    - `customer.subscription.updated`
    - `customer.subscription.deleted`
    - `invoice.paid`
    - `invoice.payment_failed`
    - (recommended) `invoice.finalized`, `invoice.updated`
- In app admin portal:
  - Confirm Stripe readiness shows **Ready**.
  - Confirm all active plans have `stripe_price_id`.

## 6) File permissions & storage
- Ensure server can write to:
  - `storage/` (all)
  - `bootstrap/cache/`
- Configure Plesk PHP settings so `open_basedir` doesn’t block storage writing.

## 7) Operational checks (no CLI)
- Add/enable basic health checks you can access via browser:
  - App up: `GET /up`
  - Billing page accessible: `GET /app/billing`
- Observability:
  - Use Laravel logs via Plesk log viewer.
  - Confirm `failed_jobs` table is empty/monitored.

## 7.1 One-time / per-deploy Plesk Tasks (recommended)
- **Composer install/update** (via Plesk Git + Composer):
  - Ensure `stripe/stripe-php` is installed server-side.
- **Migrate** (per deploy):
  - `<php_path> /var/www/vhosts/<domain>/httpdocs/artisan migrate --force`
- **(Optional) Cache warmup**:
  - `<php_path> /var/www/vhosts/<domain>/httpdocs/artisan config:cache`
  - `<php_path> /var/www/vhosts/<domain>/httpdocs/artisan route:cache`
  - If caching causes issues on shared hosting, skip and rely on runtime config.

## 8) “Points likely needing code change” for shared hosting
Only do these if Plesk can’t run the required commands.
- **Protected ops endpoints** (admin-only + signed URL) to run:
  - `migrate --force`
  - `queue:work --once`
  - `schedule:run`
- **Queue strategy**: if cron-based worker is too slow, consider `QUEUE_CONNECTION=sync` for some paths (tradeoff: slower web requests).

## 9) Acceptance criteria for Phase 8
- Cron `schedule:run` executes every minute.
- Cron queue worker processes jobs; Stripe webhooks update tenant subscription status.
- Billing page shows invoices populated from webhook events.
- Trial expiry reminders and contract/invoice reminders run on schedule.
