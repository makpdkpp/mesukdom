<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLineLink;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LineWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('x-line-signature', '');
        $tenant = $this->resolveTenant($payload, $signature);

        if (! $tenant) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid LINE signature.',
            ], 401);
        }

        app(TenantContext::class)->set($tenant);

        foreach ($request->json('events', []) as $event) {
            $this->handleEvent($tenant, $event);
        }

        return response()->json(['ok' => true]);
    }

    protected function handleEvent(Tenant $tenant, array $event): void
    {
        $type = (string) data_get($event, 'type', 'unknown');
        $userId = (string) data_get($event, 'source.userId', 'guest');
        $replyToken = data_get($event, 'replyToken');
        $message = 'LINE event received';
        $status = 'received';
        $payload = ['event' => $event];

        if ($type === 'follow') {
            $message = 'New LINE follower connected';
            $payload['reply'] = $this->replyText(
                $tenant,
                $replyToken,
                "ยินดีต้อนรับครับ\nกรุณาส่งรหัสเชื่อมต่อห้องพักในรูปแบบ: LINK ABC123"
            );
        }

        if ($type === 'unfollow') {
            $message = 'LINE follower disconnected';
        }

        if ($type === 'message') {
            [$message, $status, $responsePayload] = $this->handleMessageEvent($tenant, $event);
            $payload = array_merge($payload, $responsePayload);
        }

        if ($type === 'postback') {
            $message = 'LINE postback received';
        }

        NotificationLog::create([
            'tenant_id' => $tenant->id,
            'channel' => 'line',
            'event' => $type,
            'target' => $userId,
            'message' => $message,
            'status' => $status,
            'payload' => $payload,
        ]);
    }

    protected function handleMessageEvent(Tenant $tenant, array $event): array
    {
        $text = trim((string) data_get($event, 'message.text', ''));
        $userId = (string) data_get($event, 'source.userId', '');
        $replyToken = data_get($event, 'replyToken');

        if ($text === '') {
            return ['LINE message received without text', 'received', []];
        }

        if ($linkMessage = $this->attemptLink($tenant, $userId, $text)) {
            return [
                'Resident LINE account linked',
                'linked',
                ['reply' => $this->replyText($tenant, $replyToken, $linkMessage)],
            ];
        }

        $customer = Customer::query()->where('line_user_id', $userId)->first();

        if (! $customer) {
            return [
                'LINE user not linked to resident',
                'pending_link',
                ['reply' => $this->replyText($tenant, $replyToken, 'บัญชียังไม่ถูกเชื่อมกับห้องพัก กรุณาส่งรหัสในรูปแบบ: LINK ABC123')],
            ];
        }

        $normalized = mb_strtolower($text);

        if (in_array($normalized, ['บิล', 'invoice'], true)) {
            $invoice = Invoice::query()
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['draft', 'sent', 'overdue'])
                ->orderByDesc('due_date')
                ->first();

            $reply = $invoice
                ? sprintf(
                    'บิลล่าสุด %s ห้อง %s ยอด %s บาท ครบกำหนด %s\n%s',
                    $invoice->invoice_no,
                    $invoice->room?->room_number ?? '-',
                    number_format((float) $invoice->total_amount, 2),
                    optional($invoice->due_date)->format('d/m/Y'),
                    $invoice->signedResidentUrl()
                )
                : 'ยังไม่มีบิลที่ต้องชำระ';

            return ['Resident requested latest invoice', 'replied', ['reply' => $this->replyText($tenant, $replyToken, $reply)]];
        }

        if (in_array($normalized, ['จ่าย', 'pay'], true)) {
            $invoice = Invoice::query()
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['draft', 'sent', 'overdue'])
                ->orderByDesc('due_date')
                ->first();

            $reply = $invoice
                ? 'ชำระเงินได้ที่: '.$invoice->signedResidentUrl()
                : 'ยังไม่มีบิลที่ต้องชำระ';

            return ['Resident requested payment link', 'replied', ['reply' => $this->replyText($tenant, $replyToken, $reply)]];
        }

        if (in_array($normalized, ['ประวัติ', 'history'], true)) {
            $payments = Payment::query()
                ->whereHas('invoice', function ($query) use ($customer): void {
                    $query->where('customer_id', $customer->id);
                })
                ->orderByDesc('payment_date')
                ->limit(3)
                ->get();

            $reply = $payments->isEmpty()
                ? 'ยังไม่มีประวัติการชำระเงิน'
                : 'ประวัติการจ่ายล่าสุด'."\n".$payments->map(function (Payment $payment): string {
                    return sprintf(
                        '- %s %s บาท (%s)',
                        optional($payment->payment_date)->format('d/m/Y'),
                        number_format((float) $payment->amount, 2),
                        $payment->status
                    );
                })->implode("\n");

            return ['Resident requested payment history', 'replied', ['reply' => $this->replyText($tenant, $replyToken, $reply)]];
        }

        return [
            'Resident sent unsupported command',
            'replied',
            ['reply' => $this->replyText($tenant, $replyToken, "คำสั่งที่รองรับ:\n- บิล\n- จ่าย\n- ประวัติ\n- LINK ABC123")],
        ];
    }

    protected function attemptLink(Tenant $tenant, string $userId, string $text): ?string
    {
        if ($userId === '') {
            return null;
        }

        if (! preg_match('/^(?:link|bind)?\s*:?-?\s*([A-Z0-9]{6,})$/i', $text, $matches)) {
            return null;
        }

        $token = strtoupper($matches[1]);

        $lineLink = CustomerLineLink::query()
            ->where('link_token', $token)
            ->whereNull('used_at')
            ->where('expired_at', '>', now())
            ->first();

        if (! $lineLink) {
            return 'รหัสเชื่อมต่อไม่ถูกต้องหรือหมดอายุแล้ว';
        }

        $customer = $lineLink->customer;
        $customer->update([
            'line_user_id' => $userId,
            'line_linked_at' => now(),
        ]);

        $lineLink->update(['used_at' => now()]);

        return sprintf(
            'เชื่อม LINE สำเร็จ: %s ห้อง %s',
            $customer->name,
            $customer->room?->room_number ?? '-'
        );
    }

    protected function replyText(Tenant $tenant, ?string $replyToken, string $message): array
    {
        if (! $replyToken || ! $tenant->line_channel_access_token) {
            return ['status' => 'skipped'];
        }

        $response = Http::withToken($tenant->line_channel_access_token)
            ->post('https://api.line.me/v2/bot/message/reply', [
                'replyToken' => $replyToken,
                'messages' => [
                    [
                        'type' => 'text',
                        'text' => $message,
                    ],
                ],
            ]);

        return [
            'status' => $response->successful() ? 'sent' : 'failed',
            'response' => $response->json() ?: $response->body(),
        ];
    }

    protected function resolveTenant(string $payload, string $signature): ?Tenant
    {
        if ($signature === '') {
            return null;
        }

        foreach (Tenant::query()->whereNotNull('line_channel_secret')->get() as $tenant) {
            $expected = base64_encode(hash_hmac('sha256', $payload, $tenant->line_channel_secret, true));

            if (hash_equals($expected, $signature)) {
                return $tenant;
            }
        }

        return null;
    }
}
