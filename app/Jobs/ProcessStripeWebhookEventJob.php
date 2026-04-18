<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Plan;
use App\Models\SaasInvoice;
use App\Models\StripeWebhookEvent;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
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
        $this->onConnection($this->stringValue(config('queue.default'), 'database'));
        $this->onQueue($this->stringValue(config('queue.stripe.queue'), 'stripe'));
    }

    public function handle(): void
    {
        try {
            DB::transaction(function (): void {
                $eventRow = StripeWebhookEvent::query()
                    ->lockForUpdate()
                    ->find($this->eventRowId);

                if (! $eventRow) {
                    return;
                }

                if ($eventRow->status === 'processed') {
                    return;
                }

                $event = $this->arrayValue($eventRow->payload);
                $type = $this->stringValue($event['type'] ?? null, $this->stringValue($eventRow->event_type));

                $eventRow->update([
                    'status' => 'processing',
                    'last_error' => null,
                ]);

                $this->applyEvent($type, $event);

                $eventRow->update([
                    'status' => 'processed',
                    'last_error' => null,
                ]);
            }, 3);
        } catch (\Throwable $e) {
            StripeWebhookEvent::query()
                ->whereKey($this->eventRowId)
                ->update([
                    'status' => 'failed',
                    'last_error' => $e->getMessage(),
                ]);

            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $event
     */
    private function applyEvent(string $type, array $event): void
    {
        $object = $this->arrayValue(data_get($event, 'data.object', []));

        if ($type === 'checkout.session.completed') {
            $subscriptionId = data_get($object, 'subscription');
            $customerId = data_get($object, 'customer');
            $tenantId = data_get($object, 'metadata.tenant_id');
            $planId = data_get($object, 'metadata.plan_id');
            $paymentStatus = data_get($object, 'payment_status');

            $tenantIdValue = $this->intValue($tenantId);
            $planIdValue = $this->intValue($planId);

            if ($tenantIdValue !== null) {
                $tenant = Tenant::query()->find($tenantIdValue);

                if ($tenant) {
                    $updates = [
                        'stripe_customer_id' => is_string($customerId) ? $customerId : $tenant->stripe_customer_id,
                        'stripe_subscription_id' => is_string($subscriptionId) ? $subscriptionId : $tenant->stripe_subscription_id,
                        'status' => 'active',
                        'subscription_status' => is_string($paymentStatus) && $paymentStatus === 'paid'
                            ? 'active'
                            : $tenant->subscription_status,
                    ];

                    if ($planIdValue !== null && Plan::query()->whereKey($planIdValue)->exists()) {
                        $updates['plan_id'] = $planIdValue;
                        $plan = Plan::query()->find($planIdValue);
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

            $tenantIdValue = $this->intValue($tenantId);

            if ($tenantIdValue !== null) {
                $tenant = Tenant::query()->find($tenantIdValue);
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

            if (in_array($tenant->subscription_status, ['active', 'trialing'], true)) {
                $tenant->update(['status' => 'active']);
            }

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

            $tenant->update(['status' => 'active']);

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

        $issuedAt = $this->intValue(data_get($invoiceObject, 'created'));
        $paidAt = $this->intValue(data_get($invoiceObject, 'status_transitions.paid_at'));
        $periodStart = $this->intValue(data_get($invoiceObject, 'period_start'));
        $periodEnd = $this->intValue(data_get($invoiceObject, 'period_end'));
        $amountDue = $this->intValue(data_get($invoiceObject, 'amount_due'));
        $amountPaid = $this->intValue(data_get($invoiceObject, 'amount_paid'));
        $amountRemaining = $this->intValue(data_get($invoiceObject, 'amount_remaining'));

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
                'amount_due' => $amountDue,
                'amount_paid' => $amountPaid,
                'amount_remaining' => $amountRemaining,
                'period_start' => $periodStart !== null ? now()->setTimestamp($periodStart) : null,
                'period_end' => $periodEnd !== null ? now()->setTimestamp($periodEnd) : null,
                'issued_at' => $issuedAt !== null ? now()->setTimestamp($issuedAt) : null,
                'paid_at' => $paidAt !== null ? now()->setTimestamp($paidAt) : null,
                'hosted_invoice_url' => is_string(data_get($invoiceObject, 'hosted_invoice_url')) ? data_get($invoiceObject, 'hosted_invoice_url') : null,
                'invoice_pdf_url' => is_string(data_get($invoiceObject, 'invoice_pdf')) ? data_get($invoiceObject, 'invoice_pdf') : null,
                'payload' => $invoiceObject,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    private function intValue(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '' && is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function stringValue(mixed $value, string $default = ''): string
    {
        return is_string($value) ? $value : $default;
    }
}
