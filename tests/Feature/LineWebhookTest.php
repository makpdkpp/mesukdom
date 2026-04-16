<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LineWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_line_webhook_accepts_follow_event(): void
    {
        $response = $this->postJson('/line/webhook', [
            'events' => [
                [
                    'type' => 'follow',
                    'source' => [
                        'userId' => 'U-demo-user',
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true]);

        $this->assertDatabaseHas('notification_logs', [
            'channel' => 'line',
            'event' => 'follow',
        ]);
    }
}
