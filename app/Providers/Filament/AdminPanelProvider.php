<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\InternalLoginRedirect;
use App\Filament\Widgets\StoreOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(InternalLoginRedirect::class)
            ->brandName('TOKO SILATURIZKY')
            ->font('Plus Jakarta Sans')
            ->darkMode(false)
            ->maxContentWidth(MaxWidth::Full)
            ->colors([
                'primary' => [
                    50 => '#F4F7F1',
                    100 => '#E5EFE3',
                    200 => '#DCE6DA',
                    300 => '#C1DEC5',
                    400 => '#A1CCA6',
                    500 => '#7CAE83',
                    600 => '#527D59',
                    700 => '#355B3E',
                    800 => '#263D2C',
                    900 => '#1A2C1F',
                    950 => '#0F1A12',
                ],
                'gray' => [
                    50 => '#F8FAF7',
                    100 => '#F4F7F1',
                    200 => '#E5EFE3',
                    300 => '#DCE6DA',
                    400 => '#A7B3A9',
                    500 => '#78847A',
                    600 => '#5B685D',
                    700 => '#434F45',
                    800 => '#263D2C',
                    900 => '#1A2C1F',
                    950 => '#0F1A12',
                ],
                'warning' => Color::Amber,
                'success' => [
                    50 => '#F2F7F3',
                    100 => '#E5EFE3',
                    200 => '#C4E0C6',
                    300 => '#A1CCA6',
                    400 => '#7CAE83',
                    500 => '#527D59',
                    600 => '#355B3E',
                    700 => '#263D2C',
                    800 => '#1D2E22',
                    900 => '#142017',
                    950 => '#0B120D',
                ],
                'danger' => Color::Rose,
                'info' => [
                    50 => '#F0F6F5',
                    100 => '#DEECE9',
                    200 => '#BFDCD7',
                    300 => '#95C5BD',
                    400 => '#6DAAA0',
                    500 => '#528F85',
                    600 => '#3F736A',
                    700 => '#325B54',
                    800 => '#294944',
                    900 => '#1D3531',
                    950 => '#0E1D1B',
                ],
            ])               
            ->theme(url('css/filament/admin/theme.css'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                StoreOverview::class,
            ])
            ->navigationItems([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
