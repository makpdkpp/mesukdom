<?php

declare(strict_types=1);

namespace App\Services\Line;

use App\Models\Customer;
use App\Models\CustomerLineLink;
use App\Models\Invoice;
use App\Models\LineMessage;
use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

final class LineWebhookHandler
{
    public function __construct(
        private readonly LineService $lineService,
        private readonly CommandRouter $commandRouter,
        private readonly MessageBuilder $messageBuilder,
    ) {}

    /**
     * @return array{message:string,status:string,payload:array}
     */
    public function handle(Tenant $tenant, array $event): array
    {
        $type = (string) data_get($event, 'type', 'unknown');
        $userId = (string) data_get($event, 'source.userId', 'guest');
        $replyToken = data_get($event, 'replyToken');

        if ($type === 'follow') {
            $replyText = $this->messageBuilder->welcome();
            $replyPayload = $this->lineService->replyText($tenant, $replyToken, $replyText);
            $this->recordOutboundMessage($tenant, $userId, 'text', $replyText, $replyPayload);

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
     * @return array{message:string,status:string,payload:array}
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
            $replyPayload = $this->lineService->replyText($tenant, $replyToken, $pendingReply);
            $this->recordOutboundMessage($tenant, $userId, 'text', $pendingReply, $replyPayload);

            return [
                'message' => 'LINE user not linked to resident',
                'status' => 'pending_link',
                'payload' => ['reply' => $replyPayload],
            ];
        }

        $command = $fromText
            ? $this->commandRouter->fromText($input)
            : $this->commandRouter->fromPostback($input);

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
            $payments = Payment::query()
                ->where('tenant_id', $customer->tenant_id)
                ->whereHas('invoice', function ($query) use ($customer): void {
                    $query->where('customer_id', $customer->id);
                })
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
            $tenant = $customer->tenant;

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

    private function attemptLink(Tenant $tenant, string $userId, string $text): ?string
    {
        if ($userId === '') {
            return null;
        }

        if (! preg_match('/^(?:link|bind)?\s*:?-?\s*([A-Z0-9]{6,})$/i', $text, $matches)) {
            return null;
        }

        $token = strtoupper($matches[1]);

        $lineLink = CustomerLineLink::query()
            ->where('tenant_id', $tenant->id)
            ->where('link_token', $token)
            ->whereNull('used_at')
            ->where('expired_at', '>', now())
            ->first();

        if (! $lineLink) {
            return $this->messageBuilder->linkTokenInvalid();
        }

        $customer = $lineLink->customer;
        $customer->update([
            'line_user_id' => $userId,
            'line_linked_at' => now(),
        ]);

        $lineLink->update(['used_at' => now()]);

        return $this->messageBuilder->linkSuccess($customer->name, $customer->room?->room_number);
    }

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
