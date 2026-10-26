<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Product;
use App\Models\StockLog;
use App\Models\Transaction;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon =
        'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Penjualan';

    protected static ?string $modelLabel = 'transaksi';

    protected static ?string $pluralModelLabel =
        'transaksi & pesanan';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder =>
                    $query->with(['user', 'handledBy', 'details'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('transaction_number')
                    ->label('Nomor transaksi')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Pelanggan')
                    ->description(
                        fn (Transaction $record): ?string => collect([
                            $record->customer_phone,
                            $record->shipping_address,
                        ])->filter()->implode(' · ') ?: null
                    )
                    ->placeholder('Penjualan kasir')
                    ->searchable(),

                Tables\Columns\TextColumn::make('sales_channel')
                    ->label('Kanal')
                    ->formatStateUsing(
                        fn (string $state): string =>
                            $state === 'storefront'
                                ? 'Toko online'
                                : 'Kasir'
                    )
                    ->badge()
                    ->color(
                        fn (string $state): string =>
                            $state === 'storefront' ? 'info' : 'gray'
                    ),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(
                        fn ($state): string =>
                            'Rp'.number_format(
                                (float) $state,
                                0,
                                ',',
                                '.'
                            )
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string => match ($state) {
                            'completed' => 'Selesai / Sudah Diambil',
                            'pending' => 'Menunggu konfirmasi',
                            'pending_pickup' => 'Pending Pickup',
                            'processing' => 'Diproses',
                            'delivering' => 'Diantar',
                            'cancelled' => 'Batal',
                            default => ucfirst($state),
                        }
                    )
                    ->color(
                        fn (string $state): string => match ($state) {
                            'completed' => 'success',
                            'pending' => 'warning',
                            'cancelled' => 'danger',
                            default => 'gray',
                        }
                    ),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Pembayaran')
                    ->badge()
                    ->formatStateUsing(
                        fn (
                            ?string $state,
                            Transaction $record
                        ): string => match ($state) {
                            'paid' => 'Terkonfirmasi',
                            'refunded' => 'Dikembalikan',
                            'refund_required' =>
                                'Perlu pengembalian dana',
                            default =>
                                $record->payment_method === 'cash_on_pickup'
                                    ? 'Bayar saat diterima'
                                    : 'Menunggu verifikasi',
                        }
                    )
                    ->color(
                        fn (?string $state): string =>
                            $state === 'paid'
                                ? 'success'
                                : (
                                    in_array(
                                        $state,
                                        ['refunded', 'refund_required'],
                                        true
                                    )
                                        ? 'danger'
                                        : 'warning'
                                )
                    ),

                Tables\Columns\TextColumn::make('handledBy.name')
                    ->label('Kasir')
                    ->placeholder('—')
                    ->toggleable()
                    ->searchable(query: fn (Builder $query, string $search): Builder =>
                        $query->whereHas('handledBy', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Admin')
                    ->placeholder('—')
                    ->toggleable()
                    ->searchable(query: fn (Builder $query, string $search): Builder =>
                        $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu konfirmasi',
                        'pending_pickup' => 'Pending Pickup',
                        'processing' => 'Diproses',
                        'delivering' => 'Diantar',
                        'completed' => 'Selesai / Sudah Diambil',
                        'cancelled' => 'Batal',
                    ]),

                Tables\Filters\SelectFilter::make('sales_channel')
                    ->label('Kanal penjualan')
                    ->options([
                        'pos' => 'Kasir',
                        'storefront' => 'Toko online',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('viewOrder')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(
                        fn (Transaction $record): string =>
                            'Pesanan '.$record->transaction_number
                    )
                    ->modalDescription(
                        fn (Transaction $record): string =>
                            collect([
                                'Pelanggan: '.(
                                    $record->customer_name
                                    ?: 'Penjualan kasir'
                                ),
                                'WhatsApp: '.(
                                    $record->customer_phone ?: '—'
                                ),
                                'Pemenuhan: '.(
                                    $record->delivery_type === 'delivery'
                                        ? 'Diantar'
                                        : 'Ambil di toko'
                                ),
                                'Alamat: '.(
                                    $record->shipping_address ?: '—'
                                ),
                                'Catatan: '.(
                                    $record->pickup_note
                                    ?: $record->notes
                                    ?: '—'
                                ),
                                'Pembayaran: '.strtoupper(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $record->payment_method
                                    )
                                ),
                                ...$record->details->map(
                                    fn ($item): string =>
                                        "{$item->product_name} × "
                                        ."{$item->quantity} {$item->unit} — Rp"
                                        .number_format(
                                            (float) $item->subtotal,
                                            0,
                                            ',',
                                            '.'
                                        )
                                )->all(),
                                'TOTAL: Rp'.number_format(
                                    (float) $record->total,
                                    0,
                                    ',',
                                    '.'
                                ),
                            ])->implode("\n")
                    )
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                Tables\Actions\Action::make('processOrder')
                    ->label('Mulai proses')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(
                        fn (Transaction $record): bool =>
                            $record->sales_channel === 'storefront'
                            && in_array(
                                $record->status,
                                ['pending', 'pending_pickup'],
                                true
                            )
                    )
                    ->requiresConfirmation()
                    ->action(
                        fn (Transaction $record) =>
                            self::transitionOrder(
                                $record,
                                'processing'
                            )
                    ),

                Tables\Actions\Action::make('dispatchOrder')
                    ->label('Mulai antar')
                    ->icon('heroicon-o-truck')
                    ->color('primary')
                    ->visible(
                        fn (Transaction $record): bool =>
                            $record->sales_channel === 'storefront'
                            && $record->delivery_type === 'delivery'
                            && $record->status === 'processing'
                    )
                    ->requiresConfirmation()
                    ->action(
                        fn (Transaction $record) =>
                            self::transitionOrder(
                                $record,
                                'delivering'
                            )
                    ),

                Tables\Actions\Action::make('editCustomer')
                    ->label('Edit pelanggan')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->visible(
                        fn (Transaction $record): bool =>
                            $record->sales_channel === 'storefront'
                    )
                    ->form([
                        Forms\Components\TextInput::make('customer_name')
                            ->label('Nama pelanggan')
                            ->required()
                            ->maxLength(150),

                        Forms\Components\TextInput::make('customer_phone')
                            ->label('Nomor WhatsApp')
                            ->tel()
                            ->required()
                            ->maxLength(40),

                        Forms\Components\Textarea::make('shipping_address')
                            ->label('Alamat pengantaran')
                            ->maxLength(2000),

                        Forms\Components\Textarea::make('pickup_note')
                            ->label('Catatan pesanan')
                            ->maxLength(2000),
                    ])
                    ->fillForm(
                        fn (Transaction $record): array => [
                            'customer_name' => $record->customer_name,
                            'customer_phone' => $record->customer_phone,
                            'shipping_address' =>
                                $record->shipping_address,
                            'pickup_note' => $record->pickup_note,
                        ]
                    )
                    ->action(
                        function (
                            Transaction $record,
                            array $data
                        ): void {
                            $record->update($data);

                            Notification::make()
                                ->title('Data pelanggan diperbarui')
                                ->success()
                                ->send();
                        }
                    ),

                Tables\Actions\Action::make('verifyPayment')
                    ->label('Verifikasi pembayaran')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->visible(
                        fn (Transaction $record): bool =>
                            $record->sales_channel === 'storefront'
                            && in_array(
                                $record->status,
                                [
                                    'pending',
                                    'pending_pickup',
                                    'processing',
                                    'delivering',
                                ],
                                true
                            )
                            && in_array(
                                $record->payment_method,
                                ['bank_transfer', 'qris'],
                                true
                            )
                            && $record->payment_status === 'pending'
                    )
                    ->requiresConfirmation()
                    ->modalDescription(
                        'Pastikan dana benar-benar sudah diterima '
                        .'melalui mutasi rekening atau dashboard QRIS '
                        .'sebelum melanjutkan.'
                    )
                    ->action(
                        function (Transaction $record): void {
                            DB::transaction(function () use ($record): void {
                                $transaction = Transaction::query()
                                    ->lockForUpdate()
                                    ->findOrFail($record->id);

                                if (
                                    $transaction->sales_channel
                                        !== 'storefront'
                                    || in_array(
                                        $transaction->status,
                                        ['completed', 'cancelled'],
                                        true
                                    )
                                    || ! in_array(
                                        $transaction->payment_method,
                                        ['bank_transfer', 'qris'],
                                        true
                                    )
                                ) {
                                    throw ValidationException::withMessages([
                                        'payment_status' =>
                                            'Pembayaran tidak dapat diverifikasi.',
                                    ]);
                                }

                                $transaction->update([
                                    'payment_status' => 'paid',
                                    'amount_paid' => $transaction->total,
                                    'change_amount' => 0,
                                    'handled_by' => auth()->id(),
                                ]);
                            });

                            Notification::make()
                                ->title(
                                    'Pembayaran ditandai diterima'
                                )
                                ->body(
                                    'Pastikan verifikasi dana sudah '
                                    .'dilakukan secara manual.'
                                )
                                ->success()
                                ->send();
                        }
                    ),

                Tables\Actions\Action::make('completeOrder')
                    ->label('Selesaikan pesanan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(
                        fn (Transaction $record): bool =>
                            $record->sales_channel === 'storefront'
                            && (
                                (
                                    $record->delivery_type === 'pickup'
                                    && in_array(
                                        $record->status,
                                        [
                                            'pending_pickup',
                                            'processing',
                                        ],
                                        true
                                    )
                                )
                                || (
                                    $record->delivery_type === 'delivery'
                                    && $record->status === 'delivering'
                                )
                            )
                    )
                    ->requiresConfirmation()
                    ->modalDescription(
                        'Gunakan setelah barang diterima dan '
                        .'pembayaran tunai sudah diterima.'
                    )
                    ->action(
                        function (Transaction $record): void {
                            DB::transaction(function () use ($record): void {
                                $transaction = Transaction::query()
                                    ->lockForUpdate()
                                    ->findOrFail($record->id);

                                $validCompletionStage =
                                    $transaction->delivery_type === 'delivery'
                                        ? $transaction->status === 'delivering'
                                        : in_array(
                                            $transaction->status,
                                            [
                                                'pending_pickup',
                                                'processing',
                                            ],
                                            true
                                        );

                                if (! $validCompletionStage) {
                                    throw ValidationException::withMessages([
                                        'status' =>
                                            'Pesanan ini tidak lagi aktif.',
                                    ]);
                                }

                                if (
                                    $transaction->payment_method
                                        !== 'cash_on_pickup'
                                    && $transaction->payment_status
                                        !== 'paid'
                                ) {
                                    throw ValidationException::withMessages([
                                        'payment_status' =>
                                            'Verifikasi pembayaran transfer/QRIS sebelum menyelesaikan pesanan.',
                                    ]);
                                }

                                $transaction->update([
                                    'status' => 'completed',
                                    'handled_by' => auth()->id(),
                                    'payment_status' => 'paid',
                                    'amount_paid' => $transaction->total,
                                    'change_amount' => 0,
                                    'completed_at' => now(),
                                ]);

                                StockLog::query()
                                    ->where(
                                        'transaction_id',
                                        $transaction->id
                                    )
                                    ->where('type', 'reservation')
                                    ->update(['type' => 'sale']);
                            });

                            Notification::make()
                                ->title('Pesanan ditandai selesai')
                                ->success()
                                ->send();
                        }
                    ),

                Tables\Actions\Action::make('cancelOrder')
                    ->label('Batalkan pesanan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(
                        fn (Transaction $record): bool =>
                            in_array(
                                $record->status,
                                [
                                    'pending',
                                    'pending_pickup',
                                    'processing',
                                    'delivering',
                                ],
                                true
                            )
                    )
                    ->requiresConfirmation()
                    ->modalDescription(
                        'Stok yang sudah dialokasikan akan dikembalikan '
                        .'ke persediaan.'
                    )
                    ->action(
                        function (Transaction $record): void {
                            DB::transaction(function () use ($record): void {
                                $transaction = Transaction::query()
                                    ->with('details')
                                    ->lockForUpdate()
                                    ->findOrFail($record->id);

                                if (! in_array(
                                    $transaction->status,
                                    [
                                        'pending',
                                        'pending_pickup',
                                        'processing',
                                        'delivering',
                                    ],
                                    true
                                )) {
                                    throw ValidationException::withMessages([
                                        'status' =>
                                            'Pesanan ini tidak lagi aktif.',
                                    ]);
                                }

                                $productIds = $transaction->details
                                    ->pluck('product_id')
                                    ->filter()
                                    ->unique()
                                    ->sort()
                                    ->values();

                                $products = Product::query()
                                    ->withTrashed()
                                    ->whereIn('id', $productIds)
                                    ->orderBy('id')
                                    ->lockForUpdate()
                                    ->get()
                                    ->keyBy('id');

                                foreach ($transaction->details as $detail) {
                                    $product = $products->get(
                                        $detail->product_id
                                    );

                                    if (! $product) {
                                        continue;
                                    }

                                    $before = (float) $product->stock;
                                    $quantity = (float) $detail->quantity;
                                    $after = $before + $quantity;

                                    if (
                                        DB::connection()->getDriverName()
                                        === 'mysql'
                                    ) {
                                        DB::statement(
                                            'SET @silaturizky_user_id = ?, @silaturizky_transaction_id = ?, @silaturizky_stock_type = ?',
                                            [
                                                auth()->id(),
                                                $transaction->id,
                                                'cancellation',
                                            ]
                                        );

                                        try {
                                            $product->update([
                                                'stock' => $after,
                                            ]);
                                        } finally {
                                            DB::statement(
                                                'SET @silaturizky_user_id = NULL, @silaturizky_transaction_id = NULL, @silaturizky_stock_type = NULL'
                                            );
                                        }
                                    } else {
                                        $product->update([
                                            'stock' => $after,
                                        ]);

                                        StockLog::create([
                                            'product_id' => $product->id,
                                            'user_id' => auth()->id(),
                                            'transaction_id' =>
                                                $transaction->id,
                                            'type' => 'cancellation',
                                            'quantity_change' => $quantity,
                                            'stock_before' => $before,
                                            'stock_after' => $after,
                                            'reference' =>
                                                'cancel:'
                                                .$transaction->id.':'
                                                .$product->id.':'
                                                .Str::uuid(),
                                            'note' =>
                                                'Pembatalan pesanan '
                                                .$transaction->transaction_number,
                                        ]);
                                    }
                                }

                                $transaction->update([
                                    'status' => 'cancelled',
                                    'handled_by' => auth()->id(),
                                    'payment_status' =>
                                        $transaction->payment_status === 'paid'
                                        && in_array(
                                            $transaction->payment_method,
                                            ['bank_transfer', 'qris'],
                                            true
                                        )
                                            ? 'refund_required'
                                            : $transaction->payment_status,
                                ]);

                                StockLog::query()
                                    ->where(
                                        'transaction_id',
                                        $transaction->id
                                    )
                                    ->where('type', 'reservation')
                                    ->update(['type' => 'cancelled']);
                            });

                            Notification::make()
                                ->title(
                                    'Pesanan dibatalkan dan stok dikembalikan'
                                )
                                ->success()
                                ->send();
                        }
                    ),
            ])
            ->poll('10s')
            ->defaultSort('created_at', 'desc');
    }

    private static function transitionOrder(
        Transaction $record,
        string $status
    ): void {
        DB::transaction(function () use ($record, $status): void {
            $transaction = Transaction::query()
                ->lockForUpdate()
                ->findOrFail($record->id);

            $transitions = [
                'pending' => 'processing',
                'pending_pickup' => 'processing',
                'processing' => 'delivering',
            ];

            if (
                $transaction->sales_channel !== 'storefront'
                || ($transitions[$transaction->status] ?? null) !== $status
                || (
                    $status === 'delivering'
                    && $transaction->delivery_type !== 'delivery'
                )
            ) {
                throw ValidationException::withMessages([
                    'status' =>
                        'Status pesanan telah berubah atau transisi ini tidak tersedia.',
                ]);
            }

            $transaction->update([
                'status' => $status,
                'handled_by' => auth()->id(),
            ]);
        });

        Notification::make()
            ->title(
                $status === 'processing'
                    ? 'Pesanan mulai diproses'
                    : 'Pesanan mulai diantar'
            )
            ->success()
            ->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
        ];
    }
}