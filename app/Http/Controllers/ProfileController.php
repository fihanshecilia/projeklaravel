<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit', [
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'current_password' => [
                'nullable',
                'string',
                'min:8',
                function (string $attribute, mixed $value, \Closure $fail) use ($request, $user): void {
                    if (filled($request->input('password')) && ! Hash::check((string) $value, $user->password)) {
                        $fail('Kata sandi lama tidak sesuai.');
                    }
                },
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    if (filled($request->input('password')) && empty($request->input('current_password'))) {
                        $fail('Silakan masukkan kata sandi lama terlebih dahulu.');
                    }
                },
            ],
        ]);

        $user->name = $validated['name'];
        $user->email = mb_strtolower($validated['email']);

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();

        return back()->with('status', 'Profilsuccessfully diperbarui.');
    }
}