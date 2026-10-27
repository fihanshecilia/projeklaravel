<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
use App\Models\Product;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Pengadaan';

    protected static ?string $modelLabel = 'pembelian & restok';

    protected static ?string $pluralModelLabel = 'pembelian barang';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Pembelian')
                ->schema([
                    Forms\Components\Select::make('supplier_id')
                        ->label('Pemasok / Supplier')
                        ->relationship('supplier', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\DatePicker::make('purchase_date')
                        ->label('Tanggal pembelian')
                        ->default(today())
                        ->required(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan pembelian / faktur')
                        ->maxLength(500)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Item Produk yang Dibeli')
                ->schema([
                    Forms\Components\Repeater::make('details')
                        ->relationship('details')
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->label('Produk')
                                ->options(Product::query()->orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set): void {
                                    if ($state) {
                                        $product = Product::find($state);
                                        if ($product) {
                                            $set('product_name', $product->name);
                                            $set('unit', $product->unit);
                                            $set('purchase_price', (float) $product->purchase_price);
                                        }
                                    }
                                })
                                ->columnSpan(2),

                            Forms\Components\Hidden::make('product_name')
                                ->required(),

                            Forms\Components\TextInput::make('unit')
                                ->label('Satuan')
                                ->disabled()
                                ->dehydrated()
                                ->default('pcs'),

                            Forms\Components\TextInput::make('quantity')
                                ->label('Jumlah')
                                ->numeric()
                                ->minValue(0.001)
                                ->step(0.001)
                                ->default(1)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set): void {
                                    $qty = (float) $get('quantity');
                                    $price = (float) $get('purchase_price');
                                    $set('subtotal', $qty * $price);
                                }),

                            Forms\Components\TextInput::make('purchase_price')
                                ->label('Harga beli per unit')
                                ->prefix('Rp')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set): void {
                                    $qty = (float) $get('quantity');
                                    $price = (float) $get('purchase_price');
                                    $set('subtotal', $qty * $price);
                                }),

                            Forms\Components\TextInput::make('subtotal')
                                ->label('Subtotal')
                                ->prefix('Rp')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(),
                        ])
                        ->columns(6)
                        ->minItems(1)
                        ->defaultItems(1)
                        ->addActionLabel('+ Tambah Item Pembelian'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['supplier', 'user']))
            ->columns([
                Tables\Columns\TextColumn::make('purchase_number')
                    ->label('Nomor PO')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total biaya')
                    ->formatStateUsing(fn ($state): string => 'Rp' . number_format((float) $state, 0, ',', '.'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ordered' => 'Dipesan (Menunggu Barang)',
                        'received' => 'Barang Diterima & Stok Masuk',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'received' => 'success',
                        'ordered' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('received_at')
                    ->label('Waktu penerimaan')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('Belum diterima'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'ordered' => 'Dipesan',
                        'received' => 'Diterima',
                        'cancelled' => 'Dibatalkan',
                    ]),
                Tables\Filters\SelectFilter::make('supplier')
                    ->label('Supplier')
                    ->relationship('supplier', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat Detail'),

                Tables\Actions\Action::make('receive')
                    ->label('Terima Barang')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Purchase $record): bool => $record->status === 'ordered')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Penerimaan Barang')
                    ->modalDescription('Apakah Anda yakin barang pesanan ini sudah tiba lengkap? Stok produk akan otomatis bertambah ke inventori.')
                    ->action(function (Purchase $record): void {
                        app(PurchaseService::class)->receivePurchase($record, auth()->user());

                        Notification::make()
                            ->title('Barang berhasil diterima dan stok toko telah bertambah')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Purchase $record): bool => $record->status === 'ordered')
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Pesanan Pembelian')
                    ->action(function (Purchase $record): void {
                        $record->update(['status' => 'cancelled']);

                        Notification::make()
                            ->title('Pesanan pembelian telah dibatalkan')
                            ->warning()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isStoreAdmin() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'view' => Pages\ViewPurchase::route('/{record}'),
        ];
    }
}
