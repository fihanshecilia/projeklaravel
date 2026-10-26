<?php

namespace App\Filament\Actions;

use App\Exceptions\ProtectedCategoryDeletionException;
use Closure;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\QueryException;

class SafeDeleteBulkAction extends DeleteBulkAction
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
                        : 'Satu atau beberapa kategori masih digunakan oleh produk atau memiliki relasi yang mencegah penghapusan.'
                )
                ->danger()
                ->send();

            return false;
        }
    }
}
