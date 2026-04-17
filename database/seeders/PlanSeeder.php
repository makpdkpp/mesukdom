<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Trial',
                'slug' => 'trial',
                'price_monthly' => 0,
                'description' => 'ทดลองใช้งานระบบสำหรับหอพักขนาดเล็ก',
                'limits' => ['rooms' => 20, 'staff' => 1, 'slipok_enabled' => false, 'slipok_monthly_limit' => 0],
                'sort_order' => 1,
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'price_monthly' => 499,
                'description' => 'เหมาะสำหรับหอพักทั่วไป พร้อมแจ้งเตือนพื้นฐาน',
                'limits' => ['rooms' => 80, 'staff' => 3, 'slipok_enabled' => true, 'slipok_monthly_limit' => 30],
                'sort_order' => 2,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price_monthly' => 999,
                'description' => 'สำหรับหอพักหลายชั้น รองรับทีมงานและการแจ้งเตือนเต็มรูปแบบ',
                'limits' => ['rooms' => 300, 'staff' => 10, 'slipok_enabled' => true, 'slipok_monthly_limit' => 150],
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                $plan,
            );
        }
    }
}
