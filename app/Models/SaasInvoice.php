<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SaasInvoice extends Model
{
    protected $fillable = [
        'tenant_id',
        'plan_id',
        'stripe_invoice_id',
        'stripe_customer_id',
        'stripe_subscription_id',
        'stripe_payment_intent_id',
        'status',
        'currency',
        'amount_due',
        'amount_paid',
        'amount_remaining',
        'period_start',
        'period_end',
        'issued_at',
        'paid_at',
        'hosted_invoice_url',
        'invoice_pdf_url',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'amount_due' => 'integer',
            'amount_paid' => 'integer',
            'amount_remaining' => 'integer',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
