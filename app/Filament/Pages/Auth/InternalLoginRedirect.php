<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Pages\Auth\Login;

class InternalLoginRedirect extends Login
{
    public function mount(): void
    {
        $user = auth()->user();

        if (! $user) {
            $this->redirectRoute('employee.login');

            return;
        }

        if ($user->isStoreAdmin()) {
            $this->redirect('/admin');

            return;
        }

        if ($user->isCashier()) {
            $this->redirect(route('pos.index'));

            return;
        }

        // Pembeli atau role lain tidak boleh mengakses panel admin.
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('employee.login');
    }
}