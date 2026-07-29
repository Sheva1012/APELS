<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-users';
    protected static \UnitEnum|string|null $navigationGroup = 'Manajemen';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['roles']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->required()->maxLength(255),
            Forms\Components\TextInput::make('email')
                ->email()->required()->unique(ignoreRecord: true)->maxLength(255),
            Forms\Components\TextInput::make('nim')
                ->label('NIM')->maxLength(20)->unique(ignoreRecord: true)->nullable(),
            Forms\Components\TextInput::make('password')
                ->password()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrateStateUsing(fn (?string $state): ?string => $state ? Hash::make($state) : null)
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->minLength(8)->maxLength(255),
            Forms\Components\Select::make('role')
                ->label('Role')
                ->options(['admin' => 'Admin', 'dosen' => 'Dosen', 'mahasiswa' => 'Mahasiswa'])
                ->required()->default('mahasiswa')
                ->afterStateHydrated(function (Forms\Components\Select $component, ?User $record) {
                    if ($record) {
                        $component->state($record->getRoleNames()->first());
                    }
                }),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('nim')->label('NIM')->searchable(),
                Tables\Columns\TextColumn::make('roles.name')->label('Role')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'admin'     => 'Admin',
                        'dosen'     => 'Dosen',
                        'mahasiswa' => 'Mahasiswa',
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        return $query->when(
                            $data['value'] ?? null,
                            fn ($q, $role) => $q->whereHas('roles', fn ($r) => $r->where('name', $role))
                        );
                    }),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([\Filament\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view'   => Pages\ViewUser::route('/{record}'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
