<?php

declare(strict_types=1);

namespace App\Services\Line;

use App\Models\Customer;
use App\Models\CustomerLineLink;
use App\Models\Invoice;
use App\Models\LineMessage;
use App\Models\NotificationLog;
use App\Models\OwnerLineLink;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenant;
use App\Services\OwnerLineLinkService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

final class LineWebhookHandler
{
    public function __construct(
        private readonly LineService $lineService,
        private readonly CommandRouter $commandRouter,
        private readonly MessageBuilder $messageBuilder,
        private readonly OwnerLineLinkService $ownerLineLinkService,
        private readonly OwnerCommandHandler $ownerCommandHandler,
        private readonly OwnerFlexBuilder $ownerFlexBuilder,
        private readonly ResidentFlexBuilder $residentFlexBuilder,
    ) {}

    /**
      * @param array<string, mixed> $event
     * @return array{message:string,status:string,payload:array<string,mixed>}
     */
    public function handle(Tenant $tenant, array $event): array
    {
          $typeValue = data_get($event, 'type', 'unknown');
          $userIdValue = data_get($event, 'source.userId', 'guest');
          $replyTokenValue = data_get($event, 'replyToken');

          $type = is_string($typeValue) ? $typeValue : 'unknown';
          $userId = is_string($userIdValue) ? $userIdValue : 'guest';
          $replyToken = is_string($replyTokenValue) && $replyTokenValue !== '' ? $replyTokenValue : null;

        if ($type === 'follow') {
            $replyText = $this->messageBuilder->welcome();
            $linkUrl = URL::temporarySignedRoute(
                'resident.line.link.create',
                now()->addHours(12),
                ['tenant' => $tenant->id, 'line_user_id' => $userId]
            );
            $replyPayload = $this->lineService->replyLinkPrompt($tenant, $replyToken, $replyText, $linkUrl);
            $this->recordOutboundMessage($tenant, $userId, 'template', $replyText, $replyPayload);

            return [
                'message' => 'New LINE follower connected',
                'status' => 'received',
                'payload' => ['reply' => $replyPayload],
            ];
        }

        if ($type === 'unfollow') {
            return [
                'message' => 'LINE follower disconnected',
                'status' => 'received',
                'payload' => [],
            ];
        }

        if ($type === 'message') {
            $text = trim((string) data_get($event, 'message.text', ''));
            $messageType = (string) data_get($event, 'message.type', 'unknown');
            $this->recordInboundMessage($tenant, $userId, $messageType, $event);

            return $this->handleCommand($tenant, $userId, $replyToken, $text, true);
        }

        if ($type === 'postback') {
            $postbackData = (string) data_get($event, 'postback.data', '');
            $this->recordInboundMessage($tenant, $userId, 'postback', $event);

            return $this->handleCommand($tenant, $userId, $replyToken, $postbackData, false);
        }

        return [
            'message' => 'LINE event received',
            'status' => 'received',
            'payload' => [],
        ];
    }

    /**
        * @return array{message:string,status:string,payload:array<string,mixed>}
     */
    private function handleCommand(
        Tenant $tenant,
        string $userId,
        ?string $replyToken,
        string $input,
        bool $fromText
    ): array {
        if ($input === '') {
            return [
                'message' => 'LINE message received without text',
                'status' => 'received',
                'payload' => [],
            ];
        }

        if ($fromText && ($ownerLinkMessage = $this->attemptOwnerLink($tenant, $userId, $input)) !== null) {
            $isSuccess = str_contains($ownerLinkMessage, 'สำเร็จ');

            if ($isSuccess) {
                $welcomeFlex = $this->ownerFlexBuilder->welcome($tenant->name, route('app.dashboard'));
                $replyPayload = $this->lineService->replyFlex($tenant, $replyToken, $welcomeFlex);
                $this->recordOutboundMessage($tenant, $userId, 'flex', $ownerLinkMessage, $replyPayload);

                return [
                    'message' => 'Owner LINE account linked',
                    'status' => 'owner_linked',
                    'payload' => ['reply' => $replyPayload],
                ];
            }

            $replyPayload = $this->lineService->replyText($tenant, $replyToken, $ownerLinkMessage);
            $this->recordOutboundMessage($tenant, $userId, 'text', $ownerLinkMessage, $replyPayload);

            $status = match (true) {
                str_contains($ownerLinkMessage, 'Platform LINE OA') => 'wrong_scope_admin_token',
                default => 'invalid_owner_link_token',
            };

            return [
                'message' => 'Owner LINE account linked',
                'status' => $status,
                'payload' => ['reply' => $replyPayload],
            ];
        }

        $ownerCommand = $this->ownerCommandHandler->handle($tenant, $userId, $input, $fromText);

        if (($ownerCommand['handled'] ?? false) === true) {
            $replyPayload = $ownerCommand['flex'] !== null
                ? $this->lineService->replyFlex($tenant, $replyToken, $ownerCommand['flex'])
                : $this->lineService->replyText($tenant, $replyToken, (string) $ownerCommand['message']);

            $outboundMessage = $ownerCommand['message'] ?? ((string) ($ownerCommand['flex']['altText'] ?? 'Owner command reply'));
            $this->recordOutboundMessage($tenant, $userId, $ownerCommand['flex'] !== null ? 'flex' : 'text', (string) $outboundMessage, $replyPayload);

            return [
                'message' => (string) ($ownerCommand['message'] ?? 'Owner command handled'),
                'status' => (string) ($ownerCommand['status'] ?? 'replied'),
                'payload' => ['reply' => $replyPayload],
            ];
        }

        if ($fromText && ($linkMessage = $this->attemptLink($tenant, $userId, $input)) !== null) {
            $replyPayload = $this->lineService->replyText($tenant, $replyToken, $linkMessage);
            $this->recordOutboundMessage($tenant, $userId, 'text', $linkMessage, $replyPayload);

            return [
                'message' => 'Resident LINE account linked',
                'status' => str_contains($linkMessage, 'สำเร็จ') ? 'linked' : 'invalid_link_token',
                'payload' => ['reply' => $replyPayload],
            ];
        }

        $customer = Customer::query()
            ->where('tenant_id', $tenant->id)
            ->where('line_user_id', $userId)
            ->first();

        if (! $customer) {
            $pendingReply = $this->messageBuilder->pendingLink();
            $linkUrl = URL::temporarySignedRoute(
                'resident.line.link.create',
                now()->addHours(12),
                ['tenant' => $tenant->id, 'line_user_id' => $userId]
            );
            $replyPayload = $this->lineService->replyLinkPrompt($tenant, $replyToken, $pendingReply, $linkUrl);
            $this->recordOutboundMessage($tenant, $userId, 'template', $pendingReply, $replyPayload);

            return [
                'message' => 'LINE user not linked to resident',
                'status' => 'pending_link',
                'payload' => ['reply' => $replyPayload],
            ];
        }

        $command = $fromText
            ? $this->commandRouter->fromText($input)
            : $this->commandRouter->fromPostback($input);

        $flex = $this->buildFlexByCommand($customer, $command);

        if ($flex !== null) {
            $replyPayload = $this->lineService->replyFlex($tenant, $replyToken, $flex);
            $this->recordOutboundMessage($tenant, $userId, 'flex', (string) ($flex['altText'] ?? $command), $replyPayload, $customer->id);

            return [
                'message' => $this->eventMessageByCommand($command),
                'status' => 'replied',
                'payload' => [
                    'command' => $command,
                    'reply' => $replyPayload,
                ],
            ];
        }

        $replyText = $this->buildReplyByCommand($customer, $command);
        $replyPayload = $this->lineService->replyText($tenant, $replyToken, $replyText);
        $this->recordOutboundMessage($tenant, $userId, 'text', $replyText, $replyPayload, $customer->id);

        return [
            'message' => $this->eventMessageByCommand($command),
            'status' => 'replied',
            'payload' => [
                'command' => $command,
                'reply' => $replyPayload,
            ],
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function buildFlexByCommand(Customer $customer, string $command): ?array
    {
        if ($command === 'invoice' || $command === 'pay') {
            $invoice = Invoice::query()
                ->where('tenant_id', $customer->tenant_id)
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['draft', 'sent', 'overdue'])
                ->orderByDesc('due_date')
                ->first();

            return $command === 'invoice'
                ? $this->residentFlexBuilder->latestInvoice($invoice)
                : $this->residentFlexBuilder->paymentLinkBubble($invoice);
        }

        if ($command === 'history') {
            $invoiceIds = Invoice::query()
                ->where('tenant_id', $customer->tenant_id)
                ->where('customer_id', $customer->id)
                ->pluck('id');

            $payments = Payment::query()
                ->where('tenant_id', $customer->tenant_id)
                ->whereIn('invoice_id', $invoiceIds)
                ->orderByDesc('payment_date')
                ->limit(10)
                ->get();

            return $this->residentFlexBuilder->paymentHistory($payments);
        }

        return null;
    }

    private function buildReplyByCommand(Customer $customer, string $command): string
    {
        if ($command === 'invoice') {
            $invoice = Invoice::query()
                ->where('tenant_id', $customer->tenant_id)
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['draft', 'sent', 'overdue'])
                ->orderByDesc('due_date')
                ->first();

            return $this->messageBuilder->latestInvoice($invoice);
        }

        if ($command === 'pay') {
            $invoice = Invoice::query()
                ->where('tenant_id', $customer->tenant_id)
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['draft', 'sent', 'overdue'])
                ->orderByDesc('due_date')
                ->first();

            return $this->messageBuilder->paymentLink($invoice);
        }

        if ($command === 'history') {
            $invoiceIds = Invoice::query()
                ->where('tenant_id', $customer->tenant_id)
                ->where('customer_id', $customer->id)
                ->pluck('id');

            $payments = Payment::query()
                ->where('tenant_id', $customer->tenant_id)
                ->whereIn('invoice_id', $invoiceIds)
                ->orderByDesc('payment_date')
                ->limit(3)
                ->get();

            return $this->messageBuilder->paymentHistory($payments);
        }

        if ($command === 'repair') {
            $url = URL::temporarySignedRoute(
                'resident.line.repair.create',
                now()->addDays(7),
                ['customer' => $customer->id]
            );

            return $this->messageBuilder->repairLink($url);
        }

        if ($command === 'announcements') {
            /** @var Collection<int, string> $announcements */
            $announcements = NotificationLog::query()
                ->where('tenant_id', $customer->tenant_id)
                ->where('event', 'broadcast_sent')
                ->latest()
                ->limit(3)
                ->get()
                ->map(function (NotificationLog $log): string {
                    return '- '.$log->message;
                });

            return $this->messageBuilder->announcements($announcements);
        }

        if ($command === 'contact') {
            $tenant = Tenant::query()->find($customer->tenant_id);

            return $this->messageBuilder->contactOwner(
                $tenant?->support_contact_name,
                $tenant?->support_contact_phone,
                $tenant?->support_line_id,
            );
        }

        return $this->messageBuilder->unsupported();
    }

    private function eventMessageByCommand(string $command): string
    {
        return match ($command) {
            'invoice' => 'Resident requested latest invoice',
            'pay' => 'Resident requested payment link',
            'history' => 'Resident requested payment history',
            'repair' => 'Resident requested repair channel',
            'announcements' => 'Resident requested latest announcements',
            'contact' => 'Resident requested owner contact details',
            default => 'Resident sent unsupported command',
        };
    }

    private function attemptOwnerLink(Tenant $tenant, string $userId, string $text): ?string
    {
        $parsed = OwnerLineLinkService::parseInboundToken($text);

        if ($parsed === null || $userId === '') {
            return null;
        }

        if ($parsed['scope'] !== OwnerLineLink::SCOPE_TENANT) {
            return 'รหัส ADMIN ใช้สำหรับผูกผู้ดูแลแพลตฟอร์มผ่าน Platform LINE OA ไม่ใช่ช่องนี้ กรุณาส่งรหัสนี้ไปที่ Platform LINE OA แทน';
        }

        $token = $parsed['token'];

        $link = $this->ownerLineLinkService->consume(OwnerLineLink::SCOPE_TENANT, $token, $userId, $tenant->id);

        if ($link === null) {
            return 'รหัสผูกบัญชีเจ้าของไม่ถูกต้องหรือหมดอายุ กรุณาขอรหัสใหม่จากหน้า /app/settings';
        }

        return 'ผูกบัญชี LINE ของเจ้าของหอสำเร็จ ระบบจะส่งการแจ้งเตือนผ่านช่องทางนี้';
    }

    private function attemptLink(Tenant $tenant, string $userId, string $text): ?string
    {
        if ($userId === '') {
            return null;
        }

        if (! preg_match('/^(?:link|bind)?\s*:?-?\s*([A-Z0-9]{6,})$/i', $text, $matches)) {
            return null;
        }

        $token = strtoupper((string) $matches[1]);

        $lineLink = CustomerLineLink::query()
            ->where('tenant_id', $tenant->id)
            ->where('link_token', $token)
            ->whereNull('used_at')
            ->where('expired_at', '>', now())
            ->first();

        if (! $lineLink) {
            return $this->messageBuilder->linkTokenInvalid();
        }

        $customer = $lineLink->customer()->with('room')->first();

        if (! $customer instanceof Customer) {
            return $this->messageBuilder->linkTokenInvalid();
        }

        $customer->update([
            'line_user_id' => $userId,
            'line_linked_at' => now(),
        ]);

        $lineLink->update(['used_at' => now()]);

        $roomNumber = $customer->room_id
            ? Room::query()->whereKey($customer->room_id)->value('room_number')
            : null;

        return $this->messageBuilder->linkSuccess($customer->name, is_string($roomNumber) ? $roomNumber : null);
    }

    /**
     * @param array<string, mixed> $event
     */
    private function recordInboundMessage(Tenant $tenant, string $lineUserId, string $messageType, array $event): void
    {
        $customerId = Customer::query()
            ->where('tenant_id', $tenant->id)
            ->where('line_user_id', $lineUserId)
            ->value('id');

        LineMessage::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customerId,
            'direction' => 'inbound',
            'message_type' => $messageType,
            'payload' => $event,
            'sent_at' => now(),
        ]);
    }

    private function recordOutboundMessage(
        Tenant $tenant,
        string $lineUserId,
        string $messageType,
        string $message,
        array $response,
        ?int $customerId = null
    ): void {
        $resolvedCustomerId = $customerId;

        if ($resolvedCustomerId === null) {
            $resolvedCustomerId = Customer::query()
                ->where('tenant_id', $tenant->id)
                ->where('line_user_id', $lineUserId)
                ->value('id');
        }

        LineMessage::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $resolvedCustomerId,
            'direction' => 'outbound',
            'message_type' => $messageType,
            'payload' => [
                'message' => $message,
                'response' => $response,
            ],
            'sent_at' => now(),
        ]);
    }
}
