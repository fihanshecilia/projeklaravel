<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['purchase_number'] = 'PO-' . now()->format('ymdHis') . '-' . Str::upper(Str::random(4));
        $data['user_id'] = auth()->id();
        $data['status'] = 'ordered';

        $total = 0;
        if (! empty($data['details'])) {
            foreach ($data['details'] as $detail) {
                $total += ((float) ($detail['quantity'] ?? 0)) * ((float) ($detail['purchase_price'] ?? 0));
            }
        }
        $data['total_amount'] = $total;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
