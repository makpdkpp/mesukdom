<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
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
            'room_count' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'slipok_addon_enabled' => ['nullable', 'boolean'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $plan = Plan::query()->findOrFail((int) $input['plan_id']);
            $isTrialPlan = $plan->slug === 'trial';
            $rawRoomCount = isset($input['room_count']) ? (int) $input['room_count'] : null;
            $selectedRoomCount = $plan->usesCustomRoomPricing()
                ? $plan->normalizedRoomCount((int) ($input['room_count'] ?? $plan->minimumRoomCount()))
                : null;
            $slipOkAddonEnabled = $plan->usesCustomRoomPricing()
                ? filter_var($input['slipok_addon_enabled'] ?? false, FILTER_VALIDATE_BOOL)
                : false;

            if ($plan->usesCustomRoomPricing() && $selectedRoomCount === null) {
                throw ValidationException::withMessages([
                    'room_count' => 'Please select how many rooms you want to subscribe before continuing.',
                ]);
            }

            if ($plan->usesCustomRoomPricing() && $rawRoomCount !== null && $rawRoomCount < $plan->minimumRoomCount()) {
                throw ValidationException::withMessages([
                    'room_count' => 'Custom package requires at least '.$plan->minimumRoomCount().' rooms.',
                ]);
            }

            if ($slipOkAddonEnabled && ! $plan->supportsSlipOk()) {
                throw ValidationException::withMessages([
                    'slipok_addon_enabled' => 'SlipOK addon is not available for the selected package.',
                ]);
            }

            $tenant = Tenant::create([
                'plan_id' => $plan->id,
                'name' => $input['tenant_name'],
                'domain' => null,
                'plan' => $plan->slug,
                'status' => $isTrialPlan ? 'active' : 'pending_checkout',
                'subscription_status' => $isTrialPlan ? 'trialing' : 'incomplete',
                'trial_ends_at' => $isTrialPlan ? now()->addDays(14) : null,
                'subscribed_room_limit' => $plan->usesCustomRoomPricing() ? $selectedRoomCount : null,
                'subscribed_slipok_enabled' => $plan->usesCustomRoomPricing() ? $slipOkAddonEnabled : false,
                'subscribed_slipok_monthly_limit' => $plan->usesCustomRoomPricing() && $slipOkAddonEnabled
                    ? $selectedRoomCount * $plan->slipAddonRightsPerRoom()
                    : null,
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
