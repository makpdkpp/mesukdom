<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'contract_id',
        'customer_id',
        'room_id',
        'public_id',
        'invoice_no',
        'total_amount',
        'water_fee',
        'electricity_fee',
        'service_fee',
        'status',
        'issued_at',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'due_date' => 'date',
            'total_amount' => 'decimal:2',
            'water_fee' => 'decimal:2',
            'electricity_fee' => 'decimal:2',
            'service_fee' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice): void {
            $invoice->public_id ??= (string) Str::ulid();
            $invoice->invoice_no ??= 'INV-'.now()->format('Ym').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $invoice->issued_at ??= now();
        });
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
