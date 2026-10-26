<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Arsipkan produk')
                ->requiresConfirmation()
                ->modalDescription('Produk diarsipkan dan tetap tersimpan untuk menjaga riwayat transaksi.'),
            Actions\RestoreAction::make()
                ->label('Pulihkan')
                ->visible(
                    fn (): bool => $this->record->trashed()
                ),
        ];
    }
}