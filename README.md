# MesukDorm

MesukDorm is a multi-tenant dormitory and apartment management platform built with Laravel 13.

The system is designed for Thai dorm operators who need one application to manage rooms, tenants, contracts, monthly invoices, payments, utility records, repair requests, broadcasting, and LINE OA communication. In the same codebase, the platform also includes an admin portal for SaaS operations such as tenant management, package management, billing controls, and platform-wide LINE communication.

## Project Overview

MesukDorm has 3 main surfaces:

1. Public site
	- Landing page and pricing page
	- Product marketing pages for new customers

2. Tenant portal at `/app`
	- For `owner` and `staff`
	- Used to operate an individual dormitory / tenant business

3. Admin portal at `/admin`
	- For `super_admin` and `support_admin`
	- Used to manage the SaaS platform itself

The application is multi-tenant. A `Tenant` is the core business account, and most operational data such as rooms, customers, contracts, invoices, payments, repair requests, LINE activity, and notification logs belong to a tenant.

## Core Functions

### Tenant operations

- Room and building management
- Resident / customer management
- Contract management
- Monthly invoice creation and follow-up
- Payment recording, slip review, approval, rejection, and receipt export
- Utility meter entry and utility-based billing
- Repair request tracking
- Broadcast messaging to residents
- LINE OA activity monitoring
- Tenant-level settings for PromptPay, LINE OA, automation, and notifications

### Billing and finance

- PromptPay QR generation for invoices
- Payment slip verification workflow
- Stripe support for SaaS billing controls
- Monthly invoice scheduling
- Invoice send-day automation
- Overdue reminder automation
- Receipt PDF generation

### LINE OA features

- Resident LINE account linking
- Owner LINE linking for operational alerts
- Tenant LINE webhook processing
- Platform LINE webhook processing
- Owner notifications for:
  - payment received
  - utility reminder day
  - invoice create day
  - invoice send day
  - overdue digest
- Owner on-demand dashboard commands through LINE:
  - `สรุปรายรับ`
  - `ผู้ที่ชำระแล้ว`
  - `รายชื่อค้างชำระ`
- Platform LINE broadcast to owners

### Admin / SaaS functions

- Admin dashboard and monitoring
- Tenant management
- Package / pricing management
- Platform-level notification defaults
- Platform SlipOK settings
- Platform Stripe settings
- Platform LINE OA settings and admin linking
- Platform broadcast queueing
- Migration tools for maintenance

## Important Business Concepts

### Roles

- `owner`: tenant operator with business access in `/app`
- `staff`: tenant-side operational user
- `super_admin`: platform administrator
- `support_admin`: support / operations admin for the platform

### Tenant-aware behavior

The app uses tenant context resolution so the tenant portal works against the currently selected tenant. Admin pages can still inspect platform-level data while tenant pages stay scoped to the active tenant.

### Owner notification model

Owner notification behavior is implemented as a cascade:

- Platform default is configured in `/admin/notifications`
- Tenant override is configured in `/app/settings`
- Delivery can use tenant LINE OA or platform LINE OA depending on the feature

## Tech Stack

- PHP 8.3
- Laravel 13
- Laravel Jetstream
- Laravel Fortify
- Livewire 3
- MySQL / MariaDB
- Predis / Redis support
- Stripe PHP SDK
- DomPDF for PDF generation
- Vite + Tailwind CSS

## Main Routes

### Public

- `/`
- `/pricing`

### Tenant portal

- `/app/dashboard`
- `/app/room-status`
- `/app/utility`
- `/app/buildings`
- `/app/rooms`
- `/app/customers`
- `/app/contracts`
- `/app/invoices`
- `/app/payments`
- `/app/repairs`
- `/app/line-activity`
- `/app/broadcasts`
- `/app/settings`

### Admin portal

- `/admin`
- `/admin/dbmigration`
- `/admin/tenants`
- `/admin/packages`
- `/admin/platform`
- `/admin/notifications`
- `/admin/platform-line`

### Webhooks

- `/api/line/webhook`
- `/api/line/platform-webhook`
- `/api/stripe/webhook`

## Project Structure

Important directories:

- `app/Http/Controllers` : tenant, admin, webhook, and public controllers
- `app/Jobs` : queue jobs for webhook and messaging workflows
- `app/Models` : Eloquent models for business entities
- `app/Services` : domain integrations such as LINE, PromptPay, QR, slip verification
- `app/Support` : tenant context, notification preferences, audits, helper logic
- `database/migrations` : schema definitions
- `resources/views` : Blade templates for public, tenant, and admin UIs
- `routes/web.php` : browser routes
- `routes/api.php` : webhook and API routes
- `routes/console.php` : scheduled command registration
- `deploy/supervisor` : production worker and scheduler config examples

## Local Development Setup

### Requirements

- PHP 8.3
- Composer
- Node.js + npm
- MySQL / MariaDB
- Optional: Redis if you want to run Redis-backed queues locally

### Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

If you are on Windows + Laragon and `php` is not on PATH, use the Laragon PHP binary directly:

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan key:generate
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan migrate
```

### Run the application locally

Backend:

```bash
php artisan serve
```

Frontend dev server:

```bash
npm run dev
```

If you are not using the Vite dev server, build the assets first:

```bash
npm run build
```

### Local queue / cache notes

The codebase supports Redis-backed queue workers, but local development may also use database-backed cache and queue drivers if Redis is not running.

Typical local fallback values:

```env
CACHE_STORE=database
QUEUE_CONNECTION=database
LINE_QUEUE_CONNECTION=database
```

If you do want Redis locally, set the app back to Redis-backed values and ensure Redis is running on the configured host and port.

## Environment Notes

Common important environment values:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mesuk
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database

CACHE_STORE=database
QUEUE_CONNECTION=database

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

LINE_QUEUE_CONNECTION=line-redis
LINE_QUEUE=line
LINE_QUEUE_REDIS_CONNECTION=line
REDIS_LINE_DB=2
```

Choose the queue/cache values that match your environment. Production usually uses Redis. Local development may use database if Redis is not installed.

## Build, Test, and Analysis

### Frontend build

```bash
npm run build
```

### Run tests

```bash
php artisan test
```

### Static analysis

```bash
vendor/bin/phpstan analyse --memory-limit=1G
```

### Formatting

```bash
vendor/bin/pint
```

## Background Jobs and Scheduling

The app uses scheduled commands and queued jobs for billing and messaging flows.

Examples of background activity:

- processing tenant LINE webhook events
- processing platform LINE webhook events
- sending LINE push messages
- generating monthly invoices
- sending invoice links
- sending overdue warnings
- sending utility reminders
- owner notification automation

## LINE Queue Worker

LINE webhook processing and outbound LINE pushes use a dedicated queue name: `line`.

Redis-oriented settings:

- `LINE_QUEUE_CONNECTION=line-redis`
- `LINE_QUEUE=line`
- `LINE_QUEUE_REDIS_CONNECTION=line`
- `REDIS_LINE_DB=2`

Example worker command:

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan queue:work line-redis --queue=line --tries=3 --sleep=1
```

If local development is using database-backed queues instead of Redis, use:

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan queue:work database --queue=line --tries=3 --sleep=1
```

## Deployment Guide

This project is intended to run as a long-lived Laravel application with:

- web server
- PHP runtime
- MySQL / MariaDB
- queue worker
- scheduler
- built Vite assets

### Recommended production stack

- Ubuntu / Debian Linux
- Nginx or Apache
- PHP-FPM 8.3
- MySQL or MariaDB
- Redis
- Supervisor

### Production deployment checklist

1. Clone the repository onto the server.
2. Install PHP extensions required by Laravel and your integrations.
3. Install Composer dependencies.
4. Create and configure the production `.env`.
5. Generate the app key.
6. Run database migrations.
7. Install Node dependencies and build frontend assets.
8. Start queue worker and scheduler under Supervisor.
9. Configure webhook endpoints for LINE and Stripe.

### Example production commands

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
npm ci
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Required production responsibilities

#### 1. Web server

Serve the Laravel `public` directory.

#### 2. Queue worker

Required for:

- LINE webhook jobs
- outbound messaging
- background notification workflows

#### 3. Scheduler

Required for:

- billing automation
- reminder jobs
- periodic notification jobs

#### 4. Asset build

Production must contain built Vite assets, especially:

- `public/build/manifest.json`
- generated CSS / JS files under `public/build/assets`

If the manifest is missing, Laravel will throw `ViteManifestNotFoundException`.

## Supervisor Setup

Sample Supervisor configs are included in:

- `deploy/supervisor/mesukdome-line-worker.conf`
- `deploy/supervisor/mesukdome-scheduler.conf`

Typical rollout:

```bash
sudo cp deploy/supervisor/mesukdome-line-worker.conf /etc/supervisor/conf.d/
sudo cp deploy/supervisor/mesukdome-scheduler.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

### Included worker config

Worker file:

- `deploy/supervisor/mesukdome-line-worker.conf`

Command inside sample config:

```bash
/usr/bin/php artisan queue:work line-redis --queue=line --tries=3 --sleep=1 --timeout=120 --max-time=3600
```

### Included scheduler config

Scheduler file:

- `deploy/supervisor/mesukdome-scheduler.conf`

Command inside sample config:

```bash
/usr/bin/php artisan schedule:work
```

Before enabling Supervisor in production, update these values to match your server:

- `directory`
- `command`
- `user`
- `stdout_logfile`
- environment values

## Webhook Deployment Notes

### Tenant LINE webhook

- Endpoint: `/api/line/webhook`
- Verified against each tenant's `line_channel_secret`

### Platform LINE webhook

- Endpoint: `/api/line/platform-webhook`
- Verified against `PlatformSetting.platform_line_channel_secret`

### Stripe webhook

- Endpoint: `/api/stripe/webhook`
- Rate-limited and signature-checked before processing

When deploying, make sure the public HTTPS URLs are reachable by LINE and Stripe, and store the correct secrets in production configuration.

## Operational Notes

- Queue workers must be running, otherwise LINE webhook jobs will queue but not process.
- Scheduler must be running, otherwise billing and reminder automation will not execute.
- Vite assets must be built on production, otherwise public pages and dashboards may fail to render correctly.
- If Redis is configured in production, ensure it is running before enabling cache / queue Redis drivers.
- If local login or throttling fails with Redis connection errors, switch cache and queue drivers to database for local development.

## License

This project is distributed under the MIT license.
