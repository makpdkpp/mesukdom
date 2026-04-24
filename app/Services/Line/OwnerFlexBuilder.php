<?php

declare(strict_types=1);

namespace App\Services\Line;

use Illuminate\Support\Collection;

final class OwnerFlexBuilder
{
    public function revenueSummary(string $tenantName, float $total, int $count, string $period, string $dashboardUrl): array
    {
        return $this->bubble(
            altText: 'สรุปรายรับเดือนนี้',
            header: 'สรุปรายรับ',
            subheader: $tenantName.' | '.$period,
            metrics: [
                ['label' => 'รายรับรวม', 'value' => number_format($total, 2).' บาท'],
                ['label' => 'รายการชำระสำเร็จ', 'value' => (string) $count],
            ],
            footerLabel: 'เปิดแดชบอร์ด',
            footerUrl: $dashboardUrl,
            quickReply: $this->ownerQuickReplyItems(),
        );
    }

    /**
     * @param array{customer:string,room:string,amount:float,date:string,status:string} $details
     * @return array<string,mixed>
     */
    public function paymentReceived(string $tenantName, array $details, string $url): array
    {
        return $this->bubble(
            altText: '💰 รับชำระเงินแล้ว',
            header: '💰 รับชำระเงินแล้ว',
            subheader: $tenantName,
            metrics: [
                ['label' => 'ผู้เช่า', 'value' => $details['customer']],
                ['label' => 'ห้อง', 'value' => $details['room']],
                ['label' => 'ยอด', 'value' => number_format($details['amount'], 2).' บาท'],
                ['label' => 'วันที่', 'value' => $details['date']],
                ['label' => 'สถานะ', 'value' => $details['status']],
            ],
            footerLabel: 'เปิดรายการรับชำระ',
            footerUrl: $url,
            quickReply: $this->ownerQuickReplyItems(),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function utilityReminderDay(string $tenantName, string $url): array
    {
        return $this->bubble(
            altText: '📋 วันบันทึกค่าน้ำ-ไฟ',
            header: '📋 บันทึกค่าน้ำ-ไฟ',
            subheader: $tenantName,
            metrics: [
                ['label' => 'งาน', 'value' => 'บันทึกค่ามิเตอร์รอบนี้'],
                ['label' => 'ก่อน', 'value' => 'สร้างบิลรอบใหม่'],
            ],
            footerLabel: 'เปิดหน้าค่าน้ำ-ไฟ',
            footerUrl: $url,
            quickReply: $this->ownerQuickReplyItems(),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function invoiceCreateDay(string $tenantName, int $created, string $url): array
    {
        return $this->bubble(
            altText: '🧾 สร้างใบแจ้งหนี้',
            header: '🧾 สร้างใบแจ้งหนี้แล้ว',
            subheader: $tenantName,
            metrics: [
                ['label' => 'จำนวน', 'value' => number_format($created).' รายการ'],
            ],
            footerLabel: 'เปิดรายการบิล',
            footerUrl: $url,
            quickReply: $this->ownerQuickReplyItems(),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function invoiceSendDay(string $tenantName, int $sent, string $url): array
    {
        return $this->bubble(
            altText: '📨 ส่งใบแจ้งหนี้',
            header: '📨 ส่งใบแจ้งหนี้',
            subheader: $tenantName,
            metrics: [
                ['label' => 'ส่งทั้งหมด', 'value' => number_format($sent).' รายการ'],
            ],
            footerLabel: 'เปิดรายการบิล',
            footerUrl: $url,
            quickReply: $this->ownerQuickReplyItems(),
        );
    }

    /**
     * @param Collection<int, array{customer:string,room:string,amount:float,days_overdue:int}> $entries
     */
    public function overdueDigest(string $tenantName, Collection $entries, string $url): array
    {
        $rows = $entries->take(10)->map(static function (array $entry): array {
            return [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'xs',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => 'ห้อง '.$entry['room'].' | '.$entry['customer'],
                        'weight' => 'bold',
                        'size' => 'sm',
                        'wrap' => true,
                    ],
                    [
                        'type' => 'text',
                        'text' => 'ยอด '.number_format($entry['amount'], 2).' บาท | เลย '.$entry['days_overdue'].' วัน',
                        'size' => 'xs',
                        'color' => '#dc2626',
                        'wrap' => true,
                    ],
                ],
            ];
        })->values()->all();

        if ($entries->count() > 10) {
            $rows[] = [
                'type' => 'text',
                'text' => '… และอีก '.($entries->count() - 10).' รายการ',
                'size' => 'xs',
                'color' => '#6b7280',
                'align' => 'end',
            ];
        }

        return $this->listBubble(
            altText: '⚠️ สรุปบิลค้างชำระ',
            header: '⚠️ สรุปบิลค้างชำระ',
            subheader: $tenantName.' | รวม '.$entries->count().' รายการ',
            rows: $rows,
            emptyText: 'ไม่มีบิลค้างชำระในขณะนี้',
            footerLabel: 'เปิดรายการค้างชำระ',
            footerUrl: $url,
        );
    }

    /**
     * @param Collection<int, array{name:string,room:string,amount:float}> $rows
     */
    public function paidList(string $tenantName, Collection $rows, string $dashboardUrl): array
    {
        $contents = $rows->take(10)->map(function (array $row): array {
            return [
                'type' => 'box',
                'layout' => 'horizontal',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => $row['room'],
                        'weight' => 'bold',
                        'size' => 'sm',
                        'flex' => 2,
                    ],
                    [
                        'type' => 'text',
                        'text' => $row['name'],
                        'size' => 'sm',
                        'flex' => 3,
                    ],
                    [
                        'type' => 'text',
                        'text' => number_format($row['amount'], 2),
                        'size' => 'sm',
                        'align' => 'end',
                        'flex' => 2,
                    ],
                ],
                'spacing' => 'sm',
            ];
        })->values()->all();

        return $this->listBubble(
            altText: 'ผู้ที่ชำระแล้วเดือนนี้',
            header: 'ผู้ที่ชำระแล้ว',
            subheader: $tenantName,
            rows: $contents,
            emptyText: 'ยังไม่มีรายการชำระสำเร็จในเดือนนี้',
            footerLabel: 'เปิดรายการรับชำระ',
            footerUrl: $dashboardUrl,
        );
    }

    /**
     * @param Collection<int, array{name:string,room:string,amount:float,due_date:string}> $rows
     */
    public function overdueList(string $tenantName, Collection $rows, string $dashboardUrl): array
    {
        $contents = $rows->take(10)->map(function (array $row): array {
            return [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => $row['room'].' | '.$row['name'],
                        'weight' => 'bold',
                        'size' => 'sm',
                        'wrap' => true,
                    ],
                    [
                        'type' => 'text',
                        'text' => 'ยอด '.number_format($row['amount'], 2).' บาท | ครบกำหนด '.$row['due_date'],
                        'size' => 'xs',
                        'color' => '#6b7280',
                        'wrap' => true,
                    ],
                ],
                'spacing' => 'xs',
            ];
        })->values()->all();

        return $this->listBubble(
            altText: 'รายชื่อค้างชำระ',
            header: 'รายชื่อค้างชำระ',
            subheader: $tenantName,
            rows: $contents,
            emptyText: 'ไม่มีบิลค้างชำระในขณะนี้',
            footerLabel: 'เปิดรายการค้างชำระ',
            footerUrl: $dashboardUrl,
        );
    }

    /**
     * Welcome bubble shown right after a tenant owner links their LINE account.
     * Carries Quick Reply chips so the owner can immediately tap to view the dashboard.
     */
    public function welcome(string $tenantName, string $dashboardUrl): array
    {
        return [
            'type' => 'flex',
            'altText' => 'ผูกบัญชี LINE ของเจ้าของหอสำเร็จ',
            'contents' => [
                'type' => 'bubble',
                'header' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        ['type' => 'text', 'text' => 'ผูกบัญชีสำเร็จ', 'weight' => 'bold', 'size' => 'xl', 'color' => '#16a34a'],
                        ['type' => 'text', 'text' => $tenantName, 'size' => 'sm', 'color' => '#6b7280'],
                    ],
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'md',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => 'ตอนนี้คุณจะได้รับการแจ้งเตือนผ่านช่องทางนี้',
                            'wrap' => true,
                            'size' => 'sm',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'แตะปุ่มด้านล่างเพื่อดูแดชบอร์ดได้ทันที',
                            'wrap' => true,
                            'size' => 'sm',
                            'color' => '#6b7280',
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [[
                        'type' => 'button',
                        'style' => 'primary',
                        'action' => [
                            'type' => 'uri',
                            'label' => 'เปิดแดชบอร์ด',
                            'uri' => $dashboardUrl,
                        ],
                    ]],
                ],
            ],
            'quickReply' => ['items' => $this->ownerQuickReplyItems()],
        ];
    }

    private function bubble(string $altText, string $header, string $subheader, array $metrics, string $footerLabel, string $footerUrl, array $quickReply): array
    {
        return [
            'type' => 'flex',
            'altText' => $altText,
            'contents' => [
                'type' => 'bubble',
                'header' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        ['type' => 'text', 'text' => $header, 'weight' => 'bold', 'size' => 'xl'],
                        ['type' => 'text', 'text' => $subheader, 'size' => 'sm', 'color' => '#6b7280'],
                    ],
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'md',
                    'contents' => array_map(static fn (array $metric): array => [
                        'type' => 'box',
                        'layout' => 'horizontal',
                        'contents' => [
                            ['type' => 'text', 'text' => $metric['label'], 'size' => 'sm', 'color' => '#6b7280', 'flex' => 3],
                            ['type' => 'text', 'text' => $metric['value'], 'size' => 'sm', 'align' => 'end', 'weight' => 'bold', 'flex' => 4],
                        ],
                    ], $metrics),
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [[
                        'type' => 'button',
                        'style' => 'primary',
                        'action' => [
                            'type' => 'uri',
                            'label' => $footerLabel,
                            'uri' => $footerUrl,
                        ],
                    ]],
                ],
            ],
            'quickReply' => ['items' => $quickReply],
        ];
    }

    /**
     * @param list<array<string,mixed>> $rows
     */
    private function listBubble(string $altText, string $header, string $subheader, array $rows, string $emptyText, string $footerLabel, string $footerUrl): array
    {
        return [
            'type' => 'flex',
            'altText' => $altText,
            'contents' => [
                'type' => 'bubble',
                'header' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        ['type' => 'text', 'text' => $header, 'weight' => 'bold', 'size' => 'lg'],
                        ['type' => 'text', 'text' => $subheader, 'size' => 'sm', 'color' => '#6b7280'],
                    ],
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'md',
                    'contents' => $rows === []
                        ? [[
                            'type' => 'text',
                            'text' => $emptyText,
                            'wrap' => true,
                            'size' => 'sm',
                            'color' => '#6b7280',
                        ]]
                        : $rows,
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [[
                        'type' => 'button',
                        'style' => 'primary',
                        'action' => [
                            'type' => 'uri',
                            'label' => $footerLabel,
                            'uri' => $footerUrl,
                        ],
                    ]],
                ],
            ],
            'quickReply' => ['items' => $this->ownerQuickReplyItems()],
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function ownerQuickReplyItems(): array
    {
        return [
            $this->quickReply('สรุปรายรับ', 'owner_revenue'),
            $this->quickReply('ผู้ที่ชำระแล้ว', 'owner_paid_list'),
            $this->quickReply('รายชื่อค้างชำระ', 'owner_overdue'),
        ];
    }

    private function quickReply(string $label, string $action): array
    {
        return [
            'type' => 'action',
            'action' => [
                'type' => 'postback',
                'label' => $label,
                'data' => 'action='.$action,
                'displayText' => $label,
            ],
        ];
    }
}