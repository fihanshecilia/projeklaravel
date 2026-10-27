<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\StockLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Katalog';

    protected static ?string $modelLabel = 'produk';

    protected static ?string $pluralModelLabel = 'produk';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('category_id')
                ->label('Kategori')
                ->relationship('category', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\TextInput::make('name')
                ->label('Nama produk')
                ->required()
                ->maxLength(150)
                ->columnSpan(2),

            Forms\Components\TextInput::make('sku')
                ->label('SKU')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),

            Forms\Components\Select::make('unit')
                ->label('Satuan')
                ->options([
                    'kg' => 'Kilogram (kg)',
                    'liter' => 'Liter',
                    'pcs' => 'Pcs',
                    'tabung' => 'Tabung',
                ])
                ->required()
                ->default('pcs')
                ->live(),

            Forms\Components\TextInput::make('purchase_price')
                ->label('Harga beli')
                ->prefix('Rp')
                ->required()
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->live(),

            Forms\Components\TextInput::make('selling_price')
                ->label('Harga jual')
                ->prefix('Rp')
                ->required()
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->rules([
                    fn (Forms\Get $get): array => [
                        function (
                            string $attribute,
                            mixed $value,
                            \Closure $fail
                        ) use ($get): void {
                            $sellingPrice = is_numeric($value)
                                ? (float) $value
                                : null;

                            $purchasePrice = is_numeric(
                                $get('purchase_price')
                            )
                                ? (float) $get('purchase_price')
                                : null;

                            if (
                                $sellingPrice !== null
                                && $purchasePrice !== null
                                && $sellingPrice < $purchasePrice
                            ) {
                                $fail(
                                    'Harga jual tidak boleh lebih rendah dari harga modal.'
                                );
                            }
                        },
                    ],
                ]),

            Forms\Components\TextInput::make('stock')
                ->label('Stok awal')
                ->numeric()
                ->minValue(0)
                ->step(0.001)
                ->rules(
                    fn (Forms\Get $get): array =>
                        in_array(
                            $get('unit'),
                            ['pcs', 'tabung'],
                            true
                        )
                            ? ['integer', 'min:0']
                            : ['decimal:0,3', 'min:0']
                )
                ->default(0)
                ->visible(
                    fn (string $operation): bool =>
                        $operation === 'create'
                )
                ->required(
                    fn (string $operation): bool =>
                        $operation === 'create'
                ),

            Forms\Components\TextInput::make('min_stock')
                ->label('Batas stok menipis')
                ->required()
                ->numeric()
                ->minValue(0)
                ->step(0.001)
                ->rules(
                    fn (Forms\Get $get): array =>
                        in_array(
                            $get('unit'),
                            ['pcs', 'tabung'],
                            true
                        )
                            ? ['integer', 'min:0']
                            : ['decimal:0,3', 'min:0']
                )
                ->default(0),

            Forms\Components\FileUpload::make('image_path')
                ->label('Foto produk')
                ->image()
                ->disk('public')
                ->directory('products')
                ->imagePreviewHeight('140'),

            Forms\Components\Toggle::make('is_active')
                ->label('Tampilkan di toko')
                ->default(true)
                ->required(),

            Forms\Components\Section::make('Multi-Satuan (Pilihan Satuan Tambahan)')
                ->description('Kelola satuan lain untuk produk ini (misal: Dus, Karton, Pack, Renteng) beserta faktor konversi ke satuan dasar.')
                ->schema([
                    Forms\Components\Repeater::make('units')
                        ->relationship('units')
                        ->schema([
                            Forms\Components\TextInput::make('unit_name')
                                ->label('Nama satuan')
                                ->placeholder('Contoh: Dus, Pack, Renteng')
                                ->required()
                                ->maxLength(50),

                            Forms\Components\TextInput::make('conversion_factor')
                                ->label('Faktor konversi (isi)')
                                ->helperText('Isi satuan dasar dalam 1 satuan ini (misal 1 Dus = 24 pcs, isi 24)')
                                ->numeric()
                                ->minValue(0.001)
                                ->step(0.001)
                                ->required(),

                            Forms\Components\TextInput::make('selling_price')
                                ->label('Harga jual')
                                ->prefix('Rp')
                                ->numeric()
                                ->minValue(0)
                                ->required(),

                            Forms\Components\TextInput::make('purchase_price')
                                ->label('Harga modal (opsional)')
                                ->prefix('Rp')
                                ->numeric()
                                ->minValue(0)
                                ->nullable(),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Aktif')
                                ->default(true),
                        ])
                        ->columns(5)
                        ->defaultItems(0)
                        ->addActionLabel('+ Tambah Satuan Produk'),
                ])
                ->columnSpanFull()
                ->collapsible(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder =>
                    $query->withoutGlobalScopes([
                        SoftDeletingScope::class,
                    ])
            )
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Satuan'),

                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Harga beli')
                    ->formatStateUsing(
                        fn ($state): string =>
                            'Rp' . number_format(
                                (float) $state,
                                0,
                                ',',
                                '.'
                            )
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Harga jual')
                    ->formatStateUsing(
                        fn ($state): string =>
                            'Rp' . number_format(
                                (float) $state,
                                0,
                                ',',
                                '.'
                            )
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Stok')
                    ->formatStateUsing(
                        fn ($state, Product $record): string =>
                            number_format(
                                (float) $state,
                                3,
                                ',',
                                '.'
                            ) . ' ' . $record->unit
                    )
                    ->badge()
                    ->color(
                        fn ($state, Product $record): string =>
                            (float) $state <= 0
                                ? 'danger'
                                : (
                                    (float) $state <= (float) $record->min_stock
                                        ? 'warning'
                                        : 'success'
                                )
                    )
                    ->sortable(),

                // Perbaikan: gunakan URL gambar yang sama dengan Kasir.
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Foto')
                    ->disk('public')
                    ->width(60)
                    ->height(60)
                    ->square(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategori')
                    ->relationship('category', 'name'),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Stok menipis')
                    ->query(
                        fn (Builder $query): Builder =>
                            $query->where(function (
                                Builder $lowStock
                            ): void {
                                $lowStock->where('stock', '<=', 0)
                                    ->orWhereColumn(
                                        'stock',
                                        '<=',
                                        'min_stock'
                                    );
                            })
                    ),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status produk'),

                Tables\Filters\TrashedFilter::make()
                    ->label('Arsip produk'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama produk')
                            ->disabled(),

                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->disabled(),

                        Forms\Components\Placeholder::make('category_name')
                            ->label('Kategori')
                            ->content(
                                fn (Product $record): string =>
                                    $record->category?->name ?? 'Tanpa kategori'
                            ),

                        Forms\Components\Section::make('Foto produk')
                            ->schema([
                                Forms\Components\FileUpload::make('image_path')
                                    ->label('Foto')
                                    ->image()
                                    ->disk('public')
                                    ->directory('products')
                                    ->downloadable()
                                    ->openable()
                                    ->disabled()
                                    ->default(
                                        fn ($record): ?string =>
                                            $record->image_path
                                    ),
                            ])
                            ->columns(1),

                        Forms\Components\TextInput::make('purchase_price')
                            ->label('Harga modal')
                            ->prefix('Rp')
                            ->disabled(),

                        Forms\Components\TextInput::make('selling_price')
                            ->label('Harga jual')
                            ->prefix('Rp')
                            ->disabled(),

                        Forms\Components\TextInput::make('stock')
                            ->label('Stok')
                            ->disabled(),
                    ]),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->label('Arsipkan')
                    ->requiresConfirmation()
                    ->modalDescription(
                        'Produk diarsipkan dan tetap tersimpan untuk menjaga riwayat transaksi.'
                    ),

                Tables\Actions\RestoreAction::make()
                    ->label('Pulihkan')
                    ->visible(
                        fn (Product $record): bool =>
                            $record->trashed()
                    ),

                Tables\Actions\Action::make('adjustStock')
                    ->label('Restok')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Jumlah barang datang')
                            ->numeric()
                            ->minValue(0.001)
                            ->step(0.001)
                            ->rules(['decimal:0,3'])
                            ->required(),

                        Forms\Components\TextInput::make('note')
                            ->label('Pemasok / keterangan barang')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Barang datang / restok')
                    ->action(function (
                        Product $record,
                        array $data
                    ): void {
                        app(\App\Services\InventoryService::class)->restock(
                            $record,
                            (float) $data['quantity'],
                            $data['note'],
                            auth()->user()
                        );

                        Notification::make()
                            ->title('Stok berhasil diperbarui')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
