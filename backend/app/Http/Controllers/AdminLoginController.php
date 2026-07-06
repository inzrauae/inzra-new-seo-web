<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminLoginController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('admin_authed')) {
            return redirect()->route('admin.orders');
        }

        return view('admin.login', [
            'error' => session('login_error'),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $configuredUsername = trim((string) config('admin.username'));
        $configuredHash     = trim((string) config('admin.password_hash'));

        if ($configuredUsername === '' || $configuredHash === '') {
            abort(503, 'Admin credentials are not configured.');
        }

        $inputUsername = trim((string) $request->input('username'));
        $inputPassword = (string) $request->input('password');

        $usernameOk = hash_equals($configuredUsername, $inputUsername);
        $passwordOk = Hash::check($inputPassword, $configuredHash);

        if (! $usernameOk || ! $passwordOk) {
            return back()
                ->withInput($request->only('username'))
                ->with('login_error', 'Invalid username or password.');
        }

        $request->session()->regenerate();
        $request->session()->put('admin_authed', true);
        $request->session()->put('admin_user', $configuredUsername);

        return redirect()->intended(route('admin.orders'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->flush();
        $request->session()->regenerate();

        return redirect()->route('admin.login');
    }
}
