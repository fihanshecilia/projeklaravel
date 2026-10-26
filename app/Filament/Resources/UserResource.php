<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Manajemen Akun';

    protected static ?string $modelLabel = 'akun kasir';

    protected static ?string $pluralModelLabel = 'akun kasir';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nama kasir')
                ->required()
                ->maxLength(150),

            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),

            Forms\Components\Select::make('role')
                ->label('Peran')
                ->options(['kasir' => 'Kasir'])
                ->default('kasir')
                ->required()
                ->disabled()
                ->dehydrated(),

            Forms\Components\Toggle::make('is_active')
                ->label('Akun aktif')
                ->default(true)
                ->required(),

            Forms\Components\TextInput::make('password')
                ->label('Kata sandi')
                ->password()
                ->revealable()
                ->required(
                    fn (string $operation): bool =>
                        $operation === 'create'
                )
                ->dehydrated(
                    fn (?string $state): bool => filled($state)
                )
                ->minLength(8)
                ->confirmed(),

            Forms\Components\TextInput::make('password_confirmation')
                ->label('Ulangi kata sandi')
                ->password()
                ->revealable()
                ->dehydrated(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn (User $record): bool =>
                            $record->id !== auth()->id()
                            && $record->isCashier()
                    )
                    ->before(function (User $record): void {
                        if ($record->isLastStoreAdmin()) {
                            throw new \RuntimeException('Akun Super Admin terakhir tidak boleh dihapus.');
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', 'kasir')
            ->withoutGlobalScopes();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isStoreAdmin() ?? false;
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

