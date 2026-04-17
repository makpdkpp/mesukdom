# Go To SaaS

This document tracks the phased implementation from the current dormitory MVP toward a full multi-tenant SaaS, including trial read-only enforcement (A) and Stripe full billing (B).

## Phase 1 (A1) — Enforce Trial Expiry as Read-only

**Status**: Implemented

- Added `App\Http\Middleware\EnsureTenantCanWrite` and registered as `tenant.write` middleware alias in `bootstrap/app.php`.
- Applied `tenant.write` to the `/app/*` route group in `routes/web.php`.
- Added `Tenant::isTrialExpired()` and `Tenant::canWrite()` helpers in `app/Models/Tenant.php`.

## Phase 2 (A2) — Enforce Usage Limits (Rooms first, then expand)

**Status**: Implemented (Rooms limit)

- Enforced plan room quota in `DashboardController::storeRoom()` using `Tenant->resolvedPlan()->roomsLimit()`.
- When limit is reached, creation is blocked and the request is redirected back with validation error.

## Phase 3 (A3) — Auto-governance Jobs (Trial expiry checks + notifications)

**Status**: Implemented (Trial expiry reminders)

- Added command `tenants:send-trial-expiry-reminders` (`app/Console/Commands/SendTrialExpiryReminders.php`).
- Scheduled the command in `routes/console.php` (daily, default `--days=3`).
- Added mail `App\Mail\TrialExpiryReminder` + view `resources/views/mail/trial-expiry-reminder.blade.php`.

## Phase 4 (B1) — Stripe Foundations (Platform config + Plan mapping)

**Status**: Implemented (Readiness + plan mapping)

- Added Stripe readiness helpers in `App\Models\PlatformSetting` (`hasStripeCredentials()`, `stripeReadinessPayload()`).
- Enhanced `AdminPortalController@index()` to pass Stripe readiness and list plans missing `stripe_price_id` to the view.
- Displayed readiness + missing mappings in `resources/views/dashboard/admin.blade.php` without exposing secrets.

## Phase 5 (B2) — Tenant Checkout & Subscription Creation

**Status**: Implemented (Checkout start + success/cancel + tenant state persistence)

- Added Stripe tenant subscription fields migration `2026_04_17_000016_add_stripe_fields_to_tenants_table.php`.
- Added `BillingController` with endpoints:
  - `POST /app/billing/checkout`
  - `GET /app/billing/success`
  - `GET /app/billing/cancel`
- Added views:
  - `resources/views/dashboard/billing-success.blade.php`
  - `resources/views/dashboard/billing-cancel.blade.php`
- Updated `Tenant::canWrite()` to allow writes when `subscription_status` is `active`/`trialing`.

## Phase 6 (B3) — Webhooks (source of truth)

**Status**: Implemented (Webhook receiver + idempotency + processing job)

- Added `POST /api/stripe/webhook` (`StripeWebhookController`) with signature verification.
- Added `stripe_webhook_events` table + `StripeWebhookEvent` model for idempotent event storage.
- Added `ProcessStripeWebhookEventJob` to update tenant subscription state from events.
- Exempted `stripe/webhook` from CSRF validation in `bootstrap/app.php`.

## Phase 7 (B4) — Full Billing (Invoices/Receipts/Portal)

**Status**: Implemented (Artifacts + Billing page + Customer Portal)

- Added `saas_invoices` table + `App\Models\SaasInvoice` to store Stripe invoice artifacts.
- Extended `ProcessStripeWebhookEventJob` to upsert SaaS invoice records for Stripe invoice events.
- Added billing UI `GET /app/billing` and portal button `POST /app/billing/portal`.
- Added `dashboard.billing` view and sidebar nav link to Billing.

## Phase 8 — Production Runbook

**Status**: Implemented (Linux/Plesk shared hosting runbook)

- Runbook created: `C:\Users\B_kon\.windsurf\plans\phase8-linux-shared-hosting-runbook-466e82.md`.
