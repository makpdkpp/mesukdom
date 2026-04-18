<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
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
        'room_fee',
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
            'room_fee' => 'decimal:2',
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

    public function signedResidentUrl(): string
    {
        return URL::signedRoute('resident.invoice', ['invoice' => $this->public_id]);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
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

    public function shouldBeOverdue(): bool
    {
        return $this->status === 'sent'
            && $this->due_date !== null
            && $this->due_date->lt(Carbon::today());
    }

    public function markAsOverdueIfNecessary(): bool
    {
        if (! $this->shouldBeOverdue()) {
            return false;
        }

        $this->update(['status' => 'overdue']);

        return true;
    }

    public static function markDueInvoicesAsOverdue(): int
    {
        return static::query()
            ->where('status', 'sent')
            ->whereDate('due_date', '<', Carbon::today())
            ->update(['status' => 'overdue']);
    }
}
