<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AdminIdentity extends Widget
{
    protected static string $view = 'filament.widgets.admin-identity';

    protected static bool $isLazy = false;

    public function getAdmin(): ?User
    {
        return Auth::user();
    }

    protected function getData(): array
    {
        return [
            'admin' => $this->getAdmin(),
        ];
    }
}