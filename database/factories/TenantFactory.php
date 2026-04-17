<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
final class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
        * @return array{name:string,domain:string,plan:string,status:string}
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'domain' => fake()->unique()->domainName(),
            'plan' => 'trial',
            'status' => 'active',
        ];
    }
}
