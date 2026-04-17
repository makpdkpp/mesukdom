<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerLineLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_generate_a_six_character_line_link_code(): void
    {
        $tenant = Tenant::create([
            'name' => 'Link Code Dorm',
            'domain' => 'link-code.local',
            'line_basic_id' => '@linkcodedorm',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $room = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'building' => 'Main',
            'room_number' => 'A-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 3500,
            'status' => 'occupied',
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Resident Link',
        ]);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('app.customers.line-link.store', $customer))
            ->assertRedirect()
            ->assertSessionHas('status_card', function (array $statusCard) use ($customer): bool {
                $linkUrl = (string) ($statusCard['link_url'] ?? '');

                return ($statusCard['customer'] ?? null) === $customer->name
                    && ($statusCard['title'] ?? null) === 'LINE link code ready'
                    && ($statusCard['add_friend_url'] ?? null) === 'https://line.me/R/ti/p/@linkcodedorm'
                    && str_contains($linkUrl, '/resident/line/link/')
                    && str_contains($linkUrl, 'signature=')
                    && preg_match('/^[A-HJ-NP-Z2-9]{6}$/', (string) ($statusCard['code'] ?? '')) === 1;
            });

        $link = $customer->lineLinks()->latest('id')->firstOrFail();

        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{6}$/', $link->link_token);
    }
}
