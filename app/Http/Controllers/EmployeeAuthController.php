<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EmployeeAuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->isEmployee()) {
            return Auth::user()->isStoreAdmin()
                ? redirect('/admin')
                : redirect()->route('pos.index');
        }

        return view('auth.employee');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $employee = User::query()
            ->where('email', mb_strtolower($credentials['email']))
            ->whereIn('role', ['admin_toko', 'owner', 'admin', 'kasir', 'cashier'])
            ->first();

        if (! $employee || ! Hash::check($credentials['password'], $employee->password) || ! $employee->isActiveAccount()) {
            Log::notice('Staff login failed.', ['email' => mb_strtolower($credentials['email'])]);

            return back()->withErrors(['email' => 'Email atau kata sandi tidak sesuai.'])->onlyInput('email');
        }

        Auth::login($employee);
        $request->session()->regenerate();

        return $employee->isStoreAdmin() ? redirect('/admin') : redirect()->route('pos.index');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('employee.login');
    }
}
