<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Str;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripePackagePricingService
{
    /**
     * @return array{price_id:string,product_id:string}
     *
     * @throws ApiErrorException
     */
    public function createMonthlyCatalog(PlatformSetting $setting, string $name, string $slug, float $priceMonthly, ?string $description = null): array
    {
        $stripe = $this->stripeClient((string) $setting->stripe_secret_key);

        $productPayload = [
            'name' => $name,
            'metadata' => [
                'plan_slug' => $slug,
                'source' => 'admin_packages',
            ],
        ];

        if ($description !== null && trim($description) !== '') {
            $productPayload['description'] = $description;
        }

        $product = $stripe->products->create($productPayload);

        $price = $stripe->prices->create([
            'currency' => 'thb',
            'unit_amount' => (int) round($priceMonthly * 100),
            'recurring' => [
                'interval' => 'month',
            ],
            'product' => (string) $product->id,
            'nickname' => Str::limit($name.' Monthly', 40, ''),
            'metadata' => [
                'plan_slug' => $slug,
                'billing_interval' => 'monthly',
                'source' => 'admin_packages',
            ],
        ]);

        return [
            'price_id' => (string) $price->id,
            'product_id' => (string) $product->id,
        ];
    }

    /**
     * @throws ApiErrorException
     */
    public function createMonthlyPriceId(PlatformSetting $setting, string $name, string $slug, float $priceMonthly, ?string $description = null): string
    {
        return $this->createMonthlyCatalog($setting, $name, $slug, $priceMonthly, $description)['price_id'];
    }

    /**
     * @throws ApiErrorException
     */
    public function archiveCatalog(PlatformSetting $setting, ?string $priceId, ?string $productId = null): void
    {
        $stripe = $this->stripeClient((string) $setting->stripe_secret_key);

        if ($priceId !== null && $priceId !== '') {
            $price = $stripe->prices->retrieve($priceId, []);

            $stripe->prices->update($priceId, [
                'active' => false,
            ]);

            if (($productId === null || $productId === '') && is_string($price->product)) {
                $productId = $price->product;
            }
        }

        if ($productId !== null && $productId !== '') {
            $stripe->products->update($productId, [
                'active' => false,
            ]);
        }
    }

    private function stripeClient(string $secretKey): StripeClient
    {
        if (! class_exists(StripeClient::class)) {
            abort(503, 'Stripe billing is temporarily unavailable. Please run composer install for stripe/stripe-php on this server.');
        }

        return new StripeClient($secretKey);
    }
}