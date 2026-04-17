<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'amount',
        'payment_date',
        'method',
        'status',
        'slip_path',
        'notes',
        'receipt_no',
        'verification_provider',
        'verification_status',
        'verification_note',
        'verification_qr_code',
        'verification_payload',
        'verification_checked_at',
    ];

    public static function generateReceiptNo(int $tenantId): string
    {
        $year = now()->year;
        $last = self::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('receipt_no')
            ->where('receipt_no', 'like', "REC-{$year}-%")
            ->orderByDesc('receipt_no')
            ->value('receipt_no');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return sprintf('REC-%s-%04d', $year, $seq);
    }

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'verification_payload' => 'array',
            'verification_checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
