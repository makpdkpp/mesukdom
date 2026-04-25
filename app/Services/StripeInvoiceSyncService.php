<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SaasInvoice;
use App\Models\Tenant;

class StripeInvoiceSyncService
{
    public function upsertInvoiceArtifact(Tenant $tenant, array|object $invoiceObject): bool
    {
        $invoicePayload = $this->normalizePayload($invoiceObject);
        $stripeInvoiceId = $this->stringValue($invoicePayload['id'] ?? null);

        if ($stripeInvoiceId === null) {
            return false;
        }

        $issuedAt = $this->intValue(data_get($invoicePayload, 'created'));
        $paidAt = $this->intValue(data_get($invoicePayload, 'status_transitions.paid_at'));
        $periodStart = $this->intValue(data_get($invoicePayload, 'period_start'));
        $periodEnd = $this->intValue(data_get($invoicePayload, 'period_end'));
        $amountDue = $this->intValue(data_get($invoicePayload, 'amount_due'));
        $amountPaid = $this->intValue(data_get($invoicePayload, 'amount_paid'));
        $amountRemaining = $this->intValue(data_get($invoicePayload, 'amount_remaining'));

        SaasInvoice::query()->updateOrCreate(
            ['stripe_invoice_id' => $stripeInvoiceId],
            [
                'tenant_id' => $tenant->id,
                'plan_id' => $tenant->plan_id,
                'stripe_customer_id' => $this->stringValue(data_get($invoicePayload, 'customer')),
                'stripe_subscription_id' => $this->stringValue(data_get($invoicePayload, 'subscription')),
                'stripe_payment_intent_id' => $this->stringValue(data_get($invoicePayload, 'payment_intent')),
                'status' => $this->stringValue(data_get($invoicePayload, 'status')),
                'currency' => $this->stringValue(data_get($invoicePayload, 'currency')),
                'amount_due' => $amountDue,
                'amount_paid' => $amountPaid,
                'amount_remaining' => $amountRemaining,
                'period_start' => $periodStart !== null ? now()->setTimestamp($periodStart) : null,
                'period_end' => $periodEnd !== null ? now()->setTimestamp($periodEnd) : null,
                'issued_at' => $issuedAt !== null ? now()->setTimestamp($issuedAt) : null,
                'paid_at' => $paidAt !== null ? now()->setTimestamp($paidAt) : null,
                'hosted_invoice_url' => $this->stringValue(data_get($invoicePayload, 'hosted_invoice_url')),
                'invoice_pdf_url' => $this->stringValue(data_get($invoicePayload, 'invoice_pdf')),
                'payload' => $invoicePayload,
            ]
        );

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizePayload(array|object $invoiceObject): array
    {
        if (is_array($invoiceObject)) {
            return $invoiceObject;
        }

        $decoded = json_decode(json_encode($invoiceObject, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
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

    private function stringValue(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
