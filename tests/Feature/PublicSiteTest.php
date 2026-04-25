<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_can_be_rendered(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('MesukDorm');
        $response->assertSee('จัดการหอพัก ออกบิล ส่ง LINE');
        $response->assertSee('แพ็กเกจและราคา');
        $response->assertSee('งานแจ้งซ่อมเปิดอยู่');
        $response->assertDontSee('รายได้เดือนนี้');
        $response->assertSee(route('pricing'), false);
    }

    public function test_landing_page_sends_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), fullscreen=(self)');
        $response->assertHeader('Content-Security-Policy');

        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
    }

    public function test_pricing_page_displays_active_plans(): void
    {
        Plan::query()->create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price_monthly' => 299,
            'description' => 'เหมาะสำหรับหอพักขนาดเล็ก',
            'limits' => ['rooms' => 30, 'staff' => 1],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get('/pricing');

        $response->assertOk();
        $response->assertSee('Starter');
        $response->assertSee('299');
        $response->assertSee('เลือกแพ็กเกจนี้');
    }
}