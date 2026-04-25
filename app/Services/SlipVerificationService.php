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

        $plan = $tenant->resolvedPlan();

        if (! $payment->slip_path || ! Storage::disk('local')->exists($payment->slip_path)) {
            return $this->markPayment($payment, 'skipped', 'Slip verification skipped: no slip file found.');
        }

        if (! $plan || ! $tenant->slipOkAddonEnabled()) {
            return $this->markPayment($payment, 'skipped', 'SlipOK addon is not included in the current package.');
        }

        $slipOkMonthlyLimit = $tenant->effectiveSlipOkMonthlyLimit();

        if ($slipOkMonthlyLimit > 0 && $this->monthlyUsage($tenant->id, $plan->id) >= $slipOkMonthlyLimit) {
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
            return [$result['status'], $this->normalizeFailureMessage($payment, $result['message'])];
        }

        $tenant = Tenant::withoutGlobalScopes()->find($payment->tenant_id);

        if (! $tenant instanceof Tenant) {
            return ['failed', 'SlipOK verification failed: tenant not found for destination validation.'];
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

        if (! $this->matchesExpectedPromptPayDestination($tenant, $result['response'])) {
            return ['failed', 'Slip receiver mismatch. This slip was not transferred to the tenant PromptPay destination.'];
        }

        $duplicateReference = $this->findDuplicateSlipReference($payment, $result['response']);

        if ($duplicateReference !== null) {
            return [
                'failed',
                sprintf(
                    'Duplicate slip reference detected. This transfer reference was already used by payment #%d.',
                    $duplicateReference->id
                ),
            ];
        }

        return [$result['status'], $result['message']];
    }

    private function normalizeFailureMessage(Payment $payment, string $message): string
    {
        $normalized = strtolower(trim($message));

        if (str_contains($normalized, 'slip not found')) {
            $invoice = Invoice::withoutGlobalScopes()->find($payment->invoice_id);

            if ($invoice instanceof Invoice) {
                return sprintf(
                    'SlipOK could not verify this slip. The uploaded image may be for a different transfer or the QR data could not be recognized. Expected invoice amount: %s THB.',
                    number_format((float) $invoice->total_amount, 2, '.', '')
                );
            }

            return 'SlipOK could not verify this slip. The uploaded image may be for a different transfer or the QR data could not be recognized.';
        }

        return $message;
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

    /**
     * @param array<string,mixed> $response
     */
    private function matchesExpectedPromptPayDestination(Tenant $tenant, array $response): bool
    {
        $expected = $this->normalizePromptPayValue($tenant->promptpay_number);

        if ($expected === null) {
            return true;
        }

        foreach ($this->receiverDestinationCandidates($response) as $candidate) {
            if ($this->normalizePromptPayValue($candidate) === $expected) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string,mixed> $response
     * @return list<string>
     */
    private function receiverDestinationCandidates(array $response): array
    {
        $candidates = [];

        foreach ([
            'data.receiver.account.proxy.account',
            'data.receiver.account.bank.account',
            'receiver.account.proxy.account',
            'receiver.account.bank.account',
            'data.receiver.proxy.account',
            'data.receiver.account.account',
        ] as $path) {
            $value = data_get($response, $path);

            if (is_scalar($value)) {
                $candidates[] = (string) $value;
            }
        }

        return array_values(array_unique(array_filter($candidates, static fn (string $value): bool => trim($value) !== '')));
    }

    private function normalizePromptPayValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', (string) $value);

        return is_string($normalized) && $normalized !== '' ? $normalized : null;
    }

    /**
     * @param array<string,mixed> $response
     */
    private function findDuplicateSlipReference(Payment $payment, array $response): ?Payment
    {
        $references = $this->extractSlipReferences($response);

        if ($references === []) {
            return null;
        }

        /** @var Payment|null $duplicate */
        $duplicate = Payment::withoutGlobalScopes()
            ->where('id', '!=', $payment->id)
            ->whereNotNull('verification_payload')
            ->get()
            ->first(function (Payment $candidate) use ($references): bool {
                $payload = $candidate->verification_payload;

                if (! is_array($payload)) {
                    return false;
                }

                $candidateReferences = $this->extractSlipReferences($payload);

                return $candidateReferences !== [] && array_intersect($references, $candidateReferences) !== [];
            });

        return $duplicate;
    }

    /**
     * @param array<string,mixed> $response
     * @return list<string>
     */
    private function extractSlipReferences(array $response): array
    {
        $references = [];

        foreach ([
            'data.referenceId',
            'referenceId',
            'data.transRef',
            'transRef',
            'data.ref1',
            'ref1',
        ] as $path) {
            $value = data_get($response, $path);

            if (is_scalar($value)) {
                $normalized = trim((string) $value);

                if ($normalized !== '') {
                    $references[] = strtolower($normalized);
                }
            }
        }

        return array_values(array_unique($references));
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