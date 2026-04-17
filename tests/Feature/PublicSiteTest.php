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
        $response->assertSee('จัดการหอพักครบตั้งแต่ห้องว่างจนถึงบิลค้างชำระ');
        $response->assertSee(route('pricing'), false);
        $response->assertSee(route('register'), false);
        $response->assertSee(route('login'), false);
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