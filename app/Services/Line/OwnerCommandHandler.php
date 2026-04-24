<?php

declare(strict_types=1);

namespace App\Services\Line;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class OwnerCommandHandler
{
    public function __construct(
        private readonly OwnerFlexBuilder $flexBuilder,
    ) {}

    /**
     * @return array{handled:bool,authorized:bool,status:?string,message:?string,flex:array<string,mixed>|null}
     */
    public function handle(Tenant $tenant, string $lineUserId, string $input, bool $fromText): array
    {
        $command = $fromText ? $this->fromText($input) : $this->fromPostback($input);

        if ($command === null) {
            return [
                'handled' => false,
                'authorized' => false,
                'status' => null,
                'message' => null,
                'flex' => null,
            ];
        }

        $owner = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('role', 'owner')
            ->where('line_user_id_hash', hash('sha256', $lineUserId))
            ->first();

        if (! $owner instanceof User) {
            return [
                'handled' => true,
                'authorized' => false,
                'status' => 'owner_command_denied',
                'message' => 'ยังไม่ได้ผูก LINE บัญชีเจ้าของหอสำหรับคำสั่งนี้ กรุณาไปที่ /app/settings แล้วสร้างรหัส OWNER ก่อน',
                'flex' => null,
            ];
        }

        return match ($command) {
            'owner_revenue' => $this->revenue($tenant),
            'owner_paid_list' => $this->paidList($tenant),
            'owner_overdue' => $this->overdueList($tenant),
            default => [
                'handled' => false,
                'authorized' => true,
                'status' => null,
                'message' => null,
                'flex' => null,
            ],
        };
    }

    private function revenue(Tenant $tenant): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $payments = Payment::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'approved')
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        return [
            'handled' => true,
            'authorized' => true,
            'status' => 'owner_command_replied',
            'message' => 'Owner requested revenue summary',
            'flex' => $this->flexBuilder->revenueSummary(
                $tenant->name,
                (float) $payments->sum('amount'),
                $payments->count(),
                $start->translatedFormat('F Y'),
                route('app.dashboard'),
            ),
        ];
    }

    private function paidList(Tenant $tenant): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $rows = Payment::query()
            ->with(['invoice.customer', 'invoice.room'])
            ->where('tenant_id', $tenant->id)
            ->where('status', 'approved')
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->latest('payment_date')
            ->limit(10)
            ->get()
            ->map(static fn (Payment $payment): array => [
                'name' => (string) ($payment->invoice?->customer?->name ?? '-'),
                'room' => (string) ($payment->invoice?->room?->room_number ?? '-'),
                'amount' => (float) $payment->amount,
            ]);

        return [
            'handled' => true,
            'authorized' => true,
            'status' => 'owner_command_replied',
            'message' => 'Owner requested paid list',
            'flex' => $this->flexBuilder->paidList($tenant->name, $rows, route('app.payments')),
        ];
    }

    private function overdueList(Tenant $tenant): array
    {
        $rows = Invoice::query()
            ->with(['customer', 'room'])
            ->where('tenant_id', $tenant->id)
            ->where('status', 'overdue')
            ->orderBy('due_date')
            ->limit(10)
            ->get()
            ->map(static fn (Invoice $invoice): array => [
                'name' => (string) ($invoice->customer?->name ?? '-'),
                'room' => (string) ($invoice->room?->room_number ?? '-'),
                'amount' => (float) $invoice->total_amount,
                'due_date' => $invoice->due_date instanceof Carbon ? $invoice->due_date->format('d/m/Y') : '-',
            ]);

        return [
            'handled' => true,
            'authorized' => true,
            'status' => 'owner_command_replied',
            'message' => 'Owner requested overdue list',
            'flex' => $this->flexBuilder->overdueList($tenant->name, $rows, route('app.invoices', ['status' => 'overdue'])),
        ];
    }

    private function fromText(string $text): ?string
    {
        $normalized = trim(mb_strtolower($text));

        return match ($normalized) {
            'สรุปรายรับ' => 'owner_revenue',
            'ผู้ที่ชำระแล้ว' => 'owner_paid_list',
            'รายชื่อค้างชำระ', 'ค้างชำระ' => 'owner_overdue',
            default => null,
        };
    }

    private function fromPostback(string $input): ?string
    {
        parse_str($input, $parsed);
        $action = $parsed['action'] ?? null;

        return in_array($action, ['owner_revenue', 'owner_paid_list', 'owner_overdue'], true)
            ? $action
            : null;
    }
}