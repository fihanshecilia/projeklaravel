<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchase extends ViewRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('receive')
                ->label('Terima Barang & Tambah Stok')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (Purchase $record): bool => $record->status === 'ordered')
                ->requiresConfirmation()
                ->action(function (Purchase $record): void {
                    app(PurchaseService::class)->receivePurchase($record, auth()->user());

                    Notification::make()
                        ->title('Barang berhasil diterima dan stok toko telah bertambah')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'received_at']);
                }),
        ];
    }
}
