<?php

namespace App\Actions\Fortify;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'tenant_name' => ['required', 'string', 'max:255'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $plan = Plan::query()->findOrFail((int) $input['plan_id']);

            $tenant = Tenant::create([
                'plan_id' => $plan->id,
                'name' => $input['tenant_name'],
                'domain' => null,
                'plan' => $plan->slug,
                'status' => 'active',
                'trial_ends_at' => $plan->slug === 'trial' ? now()->addDays(14) : null,
            ]);

            return User::create([
                'tenant_id' => $tenant->id,
                'role' => 'owner',
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
            ]);
        });
    }
}
