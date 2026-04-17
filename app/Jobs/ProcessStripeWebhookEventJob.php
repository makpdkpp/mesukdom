<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Plan;
use App\Models\SaasInvoice;
use App\Models\StripeWebhookEvent;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessStripeWebhookEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private readonly int $eventRowId)
    {
        $this->onConnection((string) config('queue.default', 'database'));
        $this->onQueue((string) config('queue.stripe.queue', 'stripe'));
    }

    public function handle(): void
    {
        $eventRow = StripeWebhookEvent::query()->find($this->eventRowId);

        if (! $eventRow) {
            return;
        }

        if ($eventRow->status === 'processed') {
            return;
        }

        $event = (array) ($eventRow->payload ?? []);
        $type = (string) ($event['type'] ?? $eventRow->event_type);

        try {
            $this->applyEvent($type, $event);
            $eventRow->update(['status' => 'processed', 'last_error' => null]);
        } catch (\Throwable $e) {
            $eventRow->update(['status' => 'failed', 'last_error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $event
     */
    private function applyEvent(string $type, array $event): void
    {
        $object = (array) data_get($event, 'data.object', []);

        if ($type === 'checkout.session.completed') {
            $subscriptionId = data_get($object, 'subscription');
            $customerId = data_get($object, 'customer');
            $tenantId = data_get($object, 'metadata.tenant_id');
            $planId = data_get($object, 'metadata.plan_id');

            if ($tenantId) {
                $tenant = Tenant::query()->find((int) $tenantId);

                if ($tenant) {
                    $updates = [
                        'stripe_customer_id' => is_string($customerId) ? $customerId : $tenant->stripe_customer_id,
                        'stripe_subscription_id' => is_string($subscriptionId) ? $subscriptionId : $tenant->stripe_subscription_id,
                    ];

                    if ($planId && Plan::query()->whereKey((int) $planId)->exists()) {
                        $updates['plan_id'] = (int) $planId;
                        $plan = Plan::query()->find((int) $planId);
                        $updates['plan'] = $plan?->slug;
                    }

                    $tenant->update($updates);
                }
            }

            return;
        }

        if (str_starts_with($type, 'customer.subscription.')) {
            $subscriptionId = data_get($object, 'id');
            $customerId = data_get($object, 'customer');
            $status = data_get($object, 'status');
            $currentPeriodEnd = data_get($object, 'current_period_end');
            $tenantId = data_get($object, 'metadata.tenant_id');

            $tenant = null;

            if ($tenantId) {
                $tenant = Tenant::query()->find((int) $tenantId);
            }

            if (! $tenant && is_string($customerId)) {
                $tenant = Tenant::query()->where('stripe_customer_id', $customerId)->first();
            }

            if (! $tenant) {
                return;
            }

            $tenant->update([
                'stripe_customer_id' => is_string($customerId) ? $customerId : $tenant->stripe_customer_id,
                'stripe_subscription_id' => is_string($subscriptionId) ? $subscriptionId : $tenant->stripe_subscription_id,
                'subscription_status' => is_string($status) ? $status : $tenant->subscription_status,
                'subscription_current_period_end' => is_numeric($currentPeriodEnd) ? now()->setTimestamp((int) $currentPeriodEnd) : $tenant->subscription_current_period_end,
            ]);

            return;
        }

        if ($type === 'invoice.payment_failed') {
            $subscriptionId = data_get($object, 'subscription');
            $customerId = data_get($object, 'customer');

            $tenant = null;

            if (is_string($subscriptionId)) {
                $tenant = Tenant::query()->where('stripe_subscription_id', $subscriptionId)->first();
            }

            if (! $tenant && is_string($customerId)) {
                $tenant = Tenant::query()->where('stripe_customer_id', $customerId)->first();
            }

            if (! $tenant) {
                return;
            }

            $tenant->update([
                'subscription_status' => 'past_due',
            ]);

            $this->upsertInvoiceArtifact($tenant, $object);

            return;
        }

        if ($type === 'invoice.paid') {
            $subscriptionId = data_get($object, 'subscription');
            $customerId = data_get($object, 'customer');

            $tenant = null;

            if (is_string($subscriptionId)) {
                $tenant = Tenant::query()->where('stripe_subscription_id', $subscriptionId)->first();
            }

            if (! $tenant && is_string($customerId)) {
                $tenant = Tenant::query()->where('stripe_customer_id', $customerId)->first();
            }

            if (! $tenant) {
                return;
            }

            if (! $tenant->hasActiveSubscription()) {
                $tenant->update([
                    'subscription_status' => 'active',
                ]);
            }

            $this->upsertInvoiceArtifact($tenant, $object);
        }

        if (in_array($type, ['invoice.finalized', 'invoice.created', 'invoice.updated'], true)) {
            $customerId = data_get($object, 'customer');
            $tenant = is_string($customerId)
                ? Tenant::query()->where('stripe_customer_id', $customerId)->first()
                : null;

            if ($tenant) {
                $this->upsertInvoiceArtifact($tenant, $object);
            }
        }
    }

    /**
     * @param array<string, mixed> $invoiceObject
     */
    private function upsertInvoiceArtifact(Tenant $tenant, array $invoiceObject): void
    {
        $stripeInvoiceId = data_get($invoiceObject, 'id');

        if (! is_string($stripeInvoiceId) || $stripeInvoiceId === '') {
            return;
        }

        $issuedAt = data_get($invoiceObject, 'created');
        $paidAt = data_get($invoiceObject, 'status_transitions.paid_at');

        SaasInvoice::query()->updateOrCreate(
            ['stripe_invoice_id' => $stripeInvoiceId],
            [
                'tenant_id' => $tenant->id,
                'plan_id' => $tenant->plan_id,
                'stripe_customer_id' => is_string(data_get($invoiceObject, 'customer')) ? data_get($invoiceObject, 'customer') : null,
                'stripe_subscription_id' => is_string(data_get($invoiceObject, 'subscription')) ? data_get($invoiceObject, 'subscription') : null,
                'stripe_payment_intent_id' => is_string(data_get($invoiceObject, 'payment_intent')) ? data_get($invoiceObject, 'payment_intent') : null,
                'status' => is_string(data_get($invoiceObject, 'status')) ? data_get($invoiceObject, 'status') : null,
                'currency' => is_string(data_get($invoiceObject, 'currency')) ? data_get($invoiceObject, 'currency') : null,
                'amount_due' => is_numeric(data_get($invoiceObject, 'amount_due')) ? (int) data_get($invoiceObject, 'amount_due') : null,
                'amount_paid' => is_numeric(data_get($invoiceObject, 'amount_paid')) ? (int) data_get($invoiceObject, 'amount_paid') : null,
                'amount_remaining' => is_numeric(data_get($invoiceObject, 'amount_remaining')) ? (int) data_get($invoiceObject, 'amount_remaining') : null,
                'period_start' => is_numeric(data_get($invoiceObject, 'period_start')) ? now()->setTimestamp((int) data_get($invoiceObject, 'period_start')) : null,
                'period_end' => is_numeric(data_get($invoiceObject, 'period_end')) ? now()->setTimestamp((int) data_get($invoiceObject, 'period_end')) : null,
                'issued_at' => is_numeric($issuedAt) ? now()->setTimestamp((int) $issuedAt) : null,
                'paid_at' => is_numeric($paidAt) ? now()->setTimestamp((int) $paidAt) : null,
                'hosted_invoice_url' => is_string(data_get($invoiceObject, 'hosted_invoice_url')) ? data_get($invoiceObject, 'hosted_invoice_url') : null,
                'invoice_pdf_url' => is_string(data_get($invoiceObject, 'invoice_pdf')) ? data_get($invoiceObject, 'invoice_pdf') : null,
                'payload' => $invoiceObject,
            ]
        );
    }
}
