<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price_monthly',
        'description',
        'limits',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'limits' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function supportsSlipOk(): bool
    {
        return (bool) Arr::get($this->limitsArray(), 'slipok_enabled', false);
    }

    public function slipOkMonthlyLimit(): int
    {
        $value = Arr::get($this->limitsArray(), 'slipok_monthly_limit', 0);

        return max(0, is_numeric($value) ? (int) $value : 0);
    }

    /**
     * @return array<string, string>
     */
    public function displayLimits(): array
    {
        $limits = $this->limitsArray();
        $display = [];

        if (array_key_exists('rooms', $limits)) {
            $display['Rooms'] = is_scalar($limits['rooms']) ? (string) $limits['rooms'] : '-';
        }

        if (array_key_exists('staff', $limits)) {
            $display['Staff'] = is_scalar($limits['staff']) ? (string) $limits['staff'] : '-';
        }

        $display['SlipOK addon'] = $this->supportsSlipOk()
            ? ($this->slipOkMonthlyLimit() > 0
                ? number_format($this->slipOkMonthlyLimit()).' verifications / month'
                : 'Enabled')
            : 'Manual review only';

        return $display;
    }

    /**
     * @return array<string, mixed>
     */
    private function limitsArray(): array
    {
        $limits = $this->getAttribute('limits');

        return is_array($limits) ? $limits : [];
    }
}
