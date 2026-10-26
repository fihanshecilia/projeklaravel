<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Katalog';

    protected static ?string $modelLabel = 'kategori';

    protected static ?string $pluralModelLabel = 'kategori';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nama kategori')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),

            Forms\Components\Toggle::make('is_active')
                ->label('Aktif di toko')
                ->helperText(
                    'Jika dinonaktifkan, kategori tidak ditampilkan kepada pelanggan.'
                )
                ->default(true)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Jumlah produk')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status kategori')
                    ->placeholder('Semua kategori')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit'),

                Action::make('toggle_status')
                    ->label(
                        fn (Category $record): string =>
                            $record->is_active
                                ? 'Nonaktifkan'
                                : 'Aktifkan'
                    )
                    ->icon(
                        fn (Category $record): string =>
                            $record->is_active
                                ? 'heroicon-o-eye-slash'
                                : 'heroicon-o-eye'
                    )
                    ->color(
                        fn (Category $record): string =>
                            $record->is_active
                                ? 'warning'
                                : 'success'
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        fn (Category $record): string =>
                            $record->is_active
                                ? 'Nonaktifkan kategori?'
                                : 'Aktifkan kategori?'
                    )
                    ->modalDescription(
                        fn (Category $record): string =>
                            $record->is_active
                                ? 'Kategori tidak akan ditampilkan di katalog pelanggan setelah filter katalog diterapkan. Produk dan riwayat tetap tersimpan.'
                                : 'Kategori akan diaktifkan kembali.'
                    )
                    ->modalSubmitActionLabel(
                        fn (Category $record): string =>
                            $record->is_active
                                ? 'Ya, nonaktifkan'
                                : 'Ya, aktifkan'
                    )
                    ->action(function (Category $record): void {
                        $record->update([
                            'is_active' => ! $record->is_active,
                        ]);
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
