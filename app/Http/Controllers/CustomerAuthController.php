<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CustomerAuthController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        $clientId = config('services.google.client_id');
        if (blank($clientId) || blank(config('services.google.client_secret'))) {
            return redirect()->route('employee.login')
                ->withErrors(['google' => 'Google login belum dikonfigurasi. Isi GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET di .env.']);
        }

        $state = Str::random(40);
        session(['google_oauth_state' => $state]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => route('customer.google.callback'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]));
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        $expectedState = $request->session()->pull('google_oauth_state');
        if (! is_string($expectedState)
            || ! hash_equals($expectedState, (string) $request->query('state'))
            || $request->filled('error')
            || ! $request->filled('code')) {
            return redirect()->route('employee.login')
                ->withErrors(['google' => 'Proses masuk Google dibatalkan atau tidak valid. Silakan coba lagi.']);
        }

        try {
            $tokenResponse = Http::asForm()->acceptJson()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'code' => $request->string('code')->toString(),
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => route('customer.google.callback'),
                'grant_type' => 'authorization_code',
            ]);
            if (! $tokenResponse->successful() || ! is_string($tokenResponse->json('access_token'))) {
                Log::warning('Google OAuth token exchange failed.', ['status' => $tokenResponse->status()]);

                return redirect()->route('employee.login')
                    ->withErrors(['google' => 'Google tidak dapat memverifikasi sesi ini. Silakan coba lagi.']);
            }

            $profileResponse = Http::acceptJson()
                ->withToken($tokenResponse->json('access_token'))
                ->timeout(10)
                ->get('https://openidconnect.googleapis.com/v1/userinfo');
        } catch (ConnectionException $exception) {
            Log::warning('Google OAuth request failed.', ['message' => $exception->getMessage()]);

            return redirect()->route('employee.login')
                ->withErrors(['google' => 'Layanan Google sedang tidak dapat dijangkau. Silakan coba lagi.']);
        }

        $profile = $profileResponse->json();
        if (! $profileResponse->successful()
            || ! is_array($profile)
            || ($profile['email_verified'] ?? false) !== true
            || ! filter_var($profile['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Google OAuth returned an unverified customer profile.', ['status' => $profileResponse->status()]);

            return redirect()->route('employee.login')
                ->withErrors(['google' => 'Akun Google harus memiliki email terverifikasi.']);
        }

        $email = mb_strtolower($profile['email']);
        $user = User::query()->where('email', $email)->first();
        if ($user && ! $user->isBuyer()) {
            return redirect()->route('employee.login')
                ->withErrors(['google' => 'Email ini digunakan untuk akun toko. Gunakan formulir masuk di halaman login.']);
        }

        $user ??= User::create([
            'name' => is_string($profile['name'] ?? null) ? $profile['name'] : $email,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Str::random(64),
            'role' => 'pembeli',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return $this->afterLogin($user);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('storefront.index');
    }

    private function afterLogin(User $user): RedirectResponse
    {
        if ($user->isCashier()) {
            return redirect()->route('pos.index');
        }

        if ($user->isStoreAdmin()) {
            return redirect('/admin');
        }

        return redirect()->to(route('storefront.index').'#keranjang');
    }
}
