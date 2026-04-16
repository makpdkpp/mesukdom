<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDocument extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'document_type',
        'original_name',
        'file_path',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public static array $types = [
        'id_card'       => 'บัตรประชาชน',
        'profile_photo' => 'รูปถ่าย',
        'contract'      => 'สัญญา',
        'other'         => 'อื่นๆ',
    ];

    public function typeLabelAttribute(): string
    {
        return self::$types[$this->document_type] ?? $this->document_type;
    }
}
