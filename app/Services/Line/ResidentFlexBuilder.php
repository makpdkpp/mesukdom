<?php

declare(strict_types=1);

namespace App\Services\Line;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Resident-facing Flex Message builders.
 *
 * Mirrors the textual variants in MessageBuilder. Each method returns a LINE
 * Messaging API "flex" message payload (with altText fallback so old clients
 * still see a sensible preview) so we can keep using the same SendLineMessageJob.
 */
final class ResidentFlexBuilder
{
    private const ACCENT_PRIMARY = '#2563eb';
    private const ACCENT_WARNING = '#d97706';
    private const ACCENT_DANGER = '#dc2626';
    private const ACCENT_SUCCESS = '#16a34a';
    private const TEXT_MUTED = '#6b7280';

    /**
     * @return array<string, mixed>
     */
    public function invoiceLink(Invoice $invoice, string $altText): array
    {
        $invoice->loadMissing(['room']);
        $url = $invoice->signedResidentUrl();
        $due = $invoice->due_date instanceof Carbon
            ? $invoice->due_date->format('d/m/Y')
            : '-';

        return $this->bubble(
            altText: $altText,
            accent: self::ACCENT_PRIMARY,
            header: 'บิลใหม่พร้อมชำระ',
            subheader: 'เลขที่ '.$invoice->invoice_no,
            metrics: [
                ['label' => 'ห้อง', 'value' => (string) ($invoice->room?->room_number ?? '-')],
                ['label' => 'ยอดรวม', 'value' => number_format((float) $invoice->total_amount, 2).' บาท'],
                ['label' => 'ครบกำหนด', 'value' => $due],
            ],
            primaryLabel: 'ดูบิลและชำระเงิน',
            primaryUrl: $url,
            quickReplies: $this->residentQuickReplies(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function paymentReminder(Invoice $invoice, string $altText): array
    {
        $invoice->loadMissing(['room']);
        $url = $invoice->signedResidentUrl();
        $due = $invoice->due_date instanceof Carbon
            ? $invoice->due_date->format('d/m/Y')
            : '-';

        return $this->bubble(
            altText: $altText,
            accent: self::ACCENT_WARNING,
            header: 'แจ้งเตือนใกล้ครบกำหนด',
            subheader: 'เลขที่ '.$invoice->invoice_no,
            metrics: [
                ['label' => 'ห้อง', 'value' => (string) ($invoice->room?->room_number ?? '-')],
                ['label' => 'ยอดรวม', 'value' => number_format((float) $invoice->total_amount, 2).' บาท'],
                ['label' => 'ครบกำหนด', 'value' => $due],
            ],
            primaryLabel: 'ชำระเงิน',
            primaryUrl: $url,
            quickReplies: $this->residentQuickReplies(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function overdueWarning(Invoice $invoice, int $daysOverdue, string $altText): array
    {
        $invoice->loadMissing(['room']);
        $url = $invoice->signedResidentUrl();
        $due = $invoice->due_date instanceof Carbon
            ? $invoice->due_date->format('d/m/Y')
            : '-';

        return $this->bubble(
            altText: $altText,
            accent: self::ACCENT_DANGER,
            header: 'บิลค้างชำระ',
            subheader: 'เลขที่ '.$invoice->invoice_no,
            metrics: [
                ['label' => 'ห้อง', 'value' => (string) ($invoice->room?->room_number ?? '-')],
                ['label' => 'ยอดค้าง', 'value' => number_format((float) $invoice->total_amount, 2).' บาท'],
                ['label' => 'ครบกำหนด', 'value' => $due],
                ['label' => 'เลยกำหนด', 'value' => $daysOverdue.' วัน'],
            ],
            primaryLabel: 'ชำระเงินทันที',
            primaryUrl: $url,
            quickReplies: $this->residentQuickReplies(),
        );
    }

    /**
     * On-demand reply when resident asks for the latest invoice.
     *
     * @return array<string, mixed>
     */
    public function latestInvoice(?Invoice $invoice): array
    {
        if (! $invoice) {
            return $this->infoBubble('ยังไม่มีบิลที่ต้องชำระ', 'ระบบยังไม่มีบิลค้างให้คุณในขณะนี้');
        }

        return $this->invoiceLink($invoice, 'บิลล่าสุดของคุณ');
    }

    /**
     * @return array<string, mixed>
     */
    public function paymentLinkBubble(?Invoice $invoice): array
    {
        if (! $invoice) {
            return $this->infoBubble('ยังไม่มีบิลที่ต้องชำระ', 'ขณะนี้ไม่มีบิลที่รอชำระเงิน');
        }

        return $this->invoiceLink($invoice, 'ลิงก์ชำระเงิน');
    }

    /**
     * @param Collection<int, Payment> $payments
     * @return array<string, mixed>
     */
    public function paymentHistory(Collection $payments): array
    {
        if ($payments->isEmpty()) {
            return $this->infoBubble('ประวัติการชำระเงิน', 'ยังไม่มีประวัติการชำระเงิน');
        }

        $rows = $payments->take(10)->map(function (Payment $payment): array {
            $date = $payment->payment_date instanceof Carbon
                ? $payment->payment_date->format('d/m/Y')
                : Carbon::parse((string) $payment->payment_date)->format('d/m/Y');

            return [
                'type' => 'box',
                'layout' => 'horizontal',
                'spacing' => 'sm',
                'contents' => [
                    ['type' => 'text', 'text' => $date, 'size' => 'sm', 'flex' => 3],
                    [
                        'type' => 'text',
                        'text' => number_format((float) $payment->amount, 2).' บาท',
                        'size' => 'sm',
                        'align' => 'end',
                        'weight' => 'bold',
                        'flex' => 4,
                    ],
                    [
                        'type' => 'text',
                        'text' => (string) $payment->status,
                        'size' => 'xs',
                        'color' => self::TEXT_MUTED,
                        'align' => 'end',
                        'flex' => 3,
                    ],
                ],
            ];
        })->values()->all();

        return [
            'type' => 'flex',
            'altText' => 'ประวัติการชำระเงิน',
            'contents' => [
                'type' => 'bubble',
                'header' => $this->headerBox('ประวัติการชำระเงิน', 'รายการล่าสุด '.min(10, $payments->count()).' รายการ', self::ACCENT_PRIMARY),
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'md',
                    'contents' => $rows,
                ],
            ],
            'quickReply' => ['items' => $this->residentQuickReplies()],
        ];
    }

    /**
     * @param array<string,mixed>|null $primaryActionUrl
     * @param array{label:string,value:string}[] $metrics
     * @return array<string, mixed>
     */
    private function bubble(
        string $altText,
        string $accent,
        string $header,
        string $subheader,
        array $metrics,
        string $primaryLabel,
        string $primaryUrl,
        array $quickReplies,
    ): array {
        return [
            'type' => 'flex',
            'altText' => $altText,
            'contents' => [
                'type' => 'bubble',
                'header' => $this->headerBox($header, $subheader, $accent),
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'md',
                    'contents' => array_map(static fn (array $metric): array => [
                        'type' => 'box',
                        'layout' => 'horizontal',
                        'contents' => [
                            ['type' => 'text', 'text' => $metric['label'], 'size' => 'sm', 'color' => self::TEXT_MUTED, 'flex' => 3],
                            ['type' => 'text', 'text' => $metric['value'], 'size' => 'sm', 'align' => 'end', 'weight' => 'bold', 'flex' => 5, 'wrap' => true],
                        ],
                    ], $metrics),
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [[
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => $accent,
                        'action' => [
                            'type' => 'uri',
                            'label' => $primaryLabel,
                            'uri' => $primaryUrl,
                        ],
                    ]],
                ],
            ],
            'quickReply' => ['items' => $quickReplies],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function infoBubble(string $header, string $body): array
    {
        return [
            'type' => 'flex',
            'altText' => $header,
            'contents' => [
                'type' => 'bubble',
                'header' => $this->headerBox($header, '', self::ACCENT_PRIMARY),
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [[
                        'type' => 'text',
                        'text' => $body,
                        'wrap' => true,
                        'size' => 'sm',
                        'color' => self::TEXT_MUTED,
                    ]],
                ],
            ],
            'quickReply' => ['items' => $this->residentQuickReplies()],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function headerBox(string $title, string $subtitle, string $accent): array
    {
        $contents = [
            ['type' => 'text', 'text' => $title, 'weight' => 'bold', 'size' => 'lg', 'color' => $accent],
        ];

        if ($subtitle !== '') {
            $contents[] = ['type' => 'text', 'text' => $subtitle, 'size' => 'sm', 'color' => self::TEXT_MUTED];
        }

        return [
            'type' => 'box',
            'layout' => 'vertical',
            'contents' => $contents,
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function residentQuickReplies(): array
    {
        return [
            $this->quickReply('บิล', 'invoice'),
            $this->quickReply('จ่าย', 'pay'),
            $this->quickReply('ประวัติ', 'history'),
            $this->quickReply('แจ้งซ่อม', 'repair'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function quickReply(string $label, string $command): array
    {
        return [
            'type' => 'action',
            'action' => [
                'type' => 'message',
                'label' => $label,
                'text' => $label,
            ],
        ];
    }
}
