<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Category $category): void {
            if ($category->products()->count() > 0) {
                throw new \App\Exceptions\ProtectedCategoryDeletionException(
                    'Kategori "' . $category->name . '" tidak dapat dihapus karena masih digunakan oleh '
                    . $category->products()->count() . ' produk.'
                );
            }
        });
    }
}