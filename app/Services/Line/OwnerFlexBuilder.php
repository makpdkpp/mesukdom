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