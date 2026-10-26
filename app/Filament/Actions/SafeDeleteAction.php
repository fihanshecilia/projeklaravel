<?php

namespace App\Filament\Actions;

use App\Exceptions\ProtectedCategoryDeletionException;
use Closure;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\QueryException;

class SafeDeleteAction extends DeleteAction
{
    public function process(?Closure $default, array $parameters = []): mixed
    {
        try {
            return parent::process($default, $parameters);
        } catch (ProtectedCategoryDeletionException | QueryException $exception) {
            report($exception);

            Notification::make()
                ->title('Kategori tidak dapat dihapus')
                ->body(
                    $exception instanceof ProtectedCategoryDeletionException
                        ? $exception->getMessage()
                        : 'Kategori masih digunakan oleh produk atau memiliki relasi yang mencegah penghapusan.'
                )
                ->danger()
                ->send();

            return false;
        }
    }
}
