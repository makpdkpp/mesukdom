<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        if ($user?->canAccessAdminPortal()) {
            return redirect()->intended('/admin');
        }

        return redirect()->intended('/app/dashboard');
    }
}