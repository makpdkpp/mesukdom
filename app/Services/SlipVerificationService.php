<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\SlipVerificationUsage;
use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;

final class SlipVerificationService
{
    public function __construct(
        private readonly SlipQrDecoder $qrDecoder,
        private readonly SlipOkService $slipOkService,
    ) {}

    /**
     * @return array{status:string,message:string}
     */
    public function verifyPayment(Payment $payment): array
    {
        $tenant = Tenant::query()->with('subscriptionPlan')->find($payment->tenant_id);

        if (! $tenant instanceof Tenant) {
            return $this->markPayment($payment, 'skipped', 'Slip verification skipped: tenant not found.');
        }

        $plan = $tenant->plan_id ? Plan::query()->find($tenant->plan_id) : null;

        if (! $payment->slip_path || ! Storage::disk('local')->exists($payment->slip_path)) {
            return $this->markPayment($payment, 'skipped', 'Slip verification skipped: no slip file found.');
        }

        if (! $plan || ! $plan->supportsSlipOk()) {
            return $this->markPayment($payment, 'skipped', 'SlipOK addon is not included in the current package.');
        }

        if ($plan->slipOkMonthlyLimit() > 0 && $this->monthlyUsage($tenant->id, $plan->id) >= $plan->slipOkMonthlyLimit()) {
            return $this->markPayment($payment, 'skipped', 'SlipOK monthly quota has been reached for this package.');
        }

        $platformSetting = PlatformSetting::current();

        if (! $platformSetting->hasSlipOkCredentials()) {
            return $this->markPayment($payment, 'skipped', 'SlipOK is not configured by Platform Admin.');
        }

        $absolutePath = Storage::disk('local')->path($payment->slip_path);
        $qrBase64 = $this->readBase64Slip($absolutePath);
        $qrCode = $this->requiresDecodedQrCode($platformSetting)
            ? $this->qrDecoder->decodeFromFile($absolutePath)
            : null;

        if ($this->requiresDecodedQrCode($platformSetting) && $qrCode === null) {
            return $this->markPayment($payment, 'failed', 'SlipOK could not read a QR code from this file. Use image slips for automatic verification.');
        }

        $result = $this->slipOkService->verifySlip($platformSetting, $qrCode, $qrBase64);
        [$finalStatus, $finalMessage] = $this->applyInvoiceAmountValidation($payment, $result);

        SlipVerificationUsage::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'payment_id' => $payment->id,
            'provider' => 'slipok',
            'usage_month' => now()->format('Y-m'),
            'status' => $finalStatus,
            'request_payload' => $result['request'],
            'response_payload' => $result['response'],
        ]);

        $payment->update([
            'verification_provider' => 'slipok',
            'verification_status' => $finalStatus,
            'verification_note' => $finalMessage,
            'verification_qr_code' => $qrCode,
            'verification_payload' => $result['response'],
            'verification_checked_at' => now(),
        ]);

        return [
            'status' => $finalStatus,
            'message' => $finalMessage,
        ];
    }

    /**
     * @param array{status:string,message:string,request:array<string,mixed>,response:array<string,mixed>} $result
     * @return array{0:string,1:string}
     */
    private function applyInvoiceAmountValidation(Payment $payment, array $result): array
    {
        if ($result['status'] !== 'verified') {
            return [$result['status'], $result['message']];
        }

        $invoice = Invoice::withoutGlobalScopes()->find($payment->invoice_id);

        if (! $invoice instanceof Invoice) {
            return ['failed', 'SlipOK verification failed: invoice not found for amount validation.'];
        }

        $expectedAmount = round((float) $invoice->total_amount, 2);
        $receivedAmount = $this->extractAmountFromResponse($result['response']);

        if ($receivedAmount === null) {
            return ['failed', 'SlipOK verification failed: amount was not found in provider response.'];
        }

        if (abs($receivedAmount - $expectedAmount) > 0.009) {
            return [
                'failed',
                sprintf(
                    'Slip amount mismatch. Expected %s THB but received %s THB.',
                    number_format($expectedAmount, 2, '.', ''),
                    number_format($receivedAmount, 2, '.', '')
                ),
            ];
        }

        return [$result['status'], $result['message']];
    }

    /**
     * @param array<string,mixed> $response
     */
    private function extractAmountFromResponse(array $response): ?float
    {
        $candidatePaths = [
            'data.amount',
            'amount',
            'result.amount',
            'payload.amount',
        ];

        foreach ($candidatePaths as $path) {
            $value = data_get($response, $path);

            if (is_int($value) || is_float($value)) {
                return round((float) $value, 2);
            }

            if (is_string($value) && is_numeric($value)) {
                return round((float) $value, 2);
            }
        }

        return null;
    }

    private function requiresDecodedQrCode(PlatformSetting $setting): bool
    {
        return ! str_contains(strtolower((string) $setting->slipok_api_url), '/qr-base64/');
    }

    private function readBase64Slip(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false || $contents === '') {
            return null;
        }

        $mimeType = mime_content_type($path);

        if (! is_string($mimeType) || $mimeType === '') {
            $mimeType = 'image/jpeg';
        }

        return sprintf('data:%s;base64,%s', $mimeType, base64_encode($contents));
    }

    private function monthlyUsage(int $tenantId, int $planId): int
    {
        return SlipVerificationUsage::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('plan_id', $planId)
            ->where('provider', 'slipok')
            ->where('usage_month', now()->format('Y-m'))
            ->count();
    }

    /**
     * @return array{status:string,message:string}
     */
    private function markPayment(Payment $payment, string $status, string $message): array
    {
        $payment->update([
            'verification_provider' => 'slipok',
            'verification_status' => $status,
            'verification_note' => $message,
            'verification_checked_at' => now(),
        ]);

        return ['status' => $status, 'message' => $message];
    }
}