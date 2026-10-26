<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Navigation\NavigationItem;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = -1;

    public static function getNavigationItems(): array
    {
        $user = auth()->user();
        $items = [];

        if ($user && $user->can('use-pos')) {
            $items[] = NavigationItem::make('Kasir POS')
                ->icon('heroicon-o-credit-card')
                ->url(fn (): string => route('pos.index'))
                ->group('Navigasi Utama');

            $items[] = NavigationItem::make('Histori Transaksi')
                ->icon('heroicon-o-clock')
                ->url(fn (): string => route('pos.history'))
                ->group('Navigasi Utama');
        }

        if ($user && $user->can('manage-store')) {
            $items[] = NavigationItem::make('Laporan')
                ->icon('heroicon-o-chart-bar')
                ->url(fn (): string => route('reports.index'))
                ->group('Navigasi Utama');

            $items[] = NavigationItem::make('Stok')
                ->icon('heroicon-o-archive-box')
                ->url(fn (): string => route('reports.stock'))
                ->group('Navigasi Utama');

            $items[] = NavigationItem::make('Panel Admin')
                ->icon('heroicon-o-cog-6-tooth')
                ->url(fn (): string => url('/admin'))
                ->group('Navigasi Utama');
        }

        return $items;
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\StoreOverview::class,
        ];
    }
}