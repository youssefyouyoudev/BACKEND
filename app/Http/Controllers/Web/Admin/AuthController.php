<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\AdminLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function store(AdminLoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);
        $remember = (bool) $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            return back()
                ->withInput($request->safe()->except('password'))
                ->withErrors([
                    'email' => 'The provided admin credentials are invalid.',
                ]);
        }

        $request->session()->regenerate();

        if (! $request->user()?->isAdmin()) {
            Auth::logout();

            return back()
                ->withInput($request->safe()->except('password'))
                ->withErrors([
                    'email' => 'This account does not have admin access.',
                ]);
        }

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Welcome back to the RiFi Media TV control center.');
    }

    public function destroy(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()
            ->route('admin.login')
            ->with('status', 'You have been signed out.');
    }
}
