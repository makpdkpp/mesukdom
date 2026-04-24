<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();
        $intended = $request->session()->pull('url.intended');

        if ($user?->canAccessAdminPortal()) {
            return redirect()->to($this->allowedIntendedPath($intended, '/admin') ?? '/admin');
        }

        return redirect()->to($this->allowedIntendedPath($intended, '/app') ?? '/app/dashboard');
    }

    private function allowedIntendedPath(mixed $intended, string $allowedPrefix): ?string
    {
        if (! is_string($intended) || $intended === '') {
            return null;
        }

        $path = parse_url($intended, PHP_URL_PATH);

        if (! is_string($path) || ! str_starts_with($path, $allowedPrefix)) {
            return null;
        }

        return $intended;
    }
}