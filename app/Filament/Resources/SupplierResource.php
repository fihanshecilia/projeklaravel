<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Pengadaan';

    protected static ?string $modelLabel = 'supplier';

    protected static ?string $pluralModelLabel = 'supplier';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nama supplier / distributor')
                ->required()
                ->maxLength(150),

            Forms\Components\TextInput::make('contact_person')
                ->label('Nama kontak (PIC)')
                ->maxLength(150),

            Forms\Components\TextInput::make('phone')
                ->label('Nomor telepon / WhatsApp')
                ->tel()
                ->maxLength(40),

            Forms\Components\TextInput::make('email')
                ->label('Alamat email')
                ->email()
                ->maxLength(150),

            Forms\Components\Textarea::make('address')
                ->label('Alamat gudang / kantor')
                ->maxLength(500)
                ->columnSpanFull(),

            Forms\Components\Toggle::make('is_active')
                ->label('Pemasok aktif')
                ->default(true)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama supplier')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Kontak (PIC)')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon / WA')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ditambahkan')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status supplier'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isStoreAdmin() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
