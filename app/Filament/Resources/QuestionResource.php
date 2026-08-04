<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionResource\Pages;
use App\Models\Question;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static \UnitEnum|string|null $navigationGroup = 'Konten';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'dosen']) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('set')
                ->options(['A' => 'Set A', 'B' => 'Set B', 'C' => 'Set C'])
                ->required(),
            Forms\Components\Textarea::make('question')->required()->maxLength(1000)->rows(3)->columnSpanFull(),
            Forms\Components\TextInput::make('option_a')->label('Option A')->required()->maxLength(1000),
            Forms\Components\TextInput::make('option_b')->label('Option B')->required()->maxLength(1000),
            Forms\Components\TextInput::make('option_c')->label('Option C')->required()->maxLength(1000),
            Forms\Components\TextInput::make('option_d')->label('Option D')->required()->maxLength(1000),
            Forms\Components\Select::make('correct_answer')
                ->options(['a'=>'A','b'=>'B','c'=>'C','d'=>'D'])->required(),
            Forms\Components\Select::make('type')
                ->options(['grammar'=>'Grammar','vocabulary'=>'Vocabulary'])->required(),
            Forms\Components\Select::make('tag')
                ->options(['basic'=>'Basic','intermediate'=>'Intermediate','advanced'=>'Advanced'])->required(),
            Forms\Components\TextInput::make('difficulty')->numeric()->minValue(1)->maxValue(5)->default(1)->required(),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('module_id'))
            ->columns([
                Tables\Columns\TextColumn::make('set')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'A' => 'primary', 'B' => 'warning', 'C' => 'success', default => 'gray',
                    })->sortable(),
                Tables\Columns\TextColumn::make('question')->limit(60)->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'grammar' => 'info', 'vocabulary' => 'success', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('tag')->badge(),
                Tables\Columns\TextColumn::make('difficulty')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d M Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('set')
                    ->options(['A' => 'Set A', 'B' => 'Set B', 'C' => 'Set C']),
                Tables\Filters\SelectFilter::make('type')
                    ->options(['grammar'=>'Grammar','vocabulary'=>'Vocabulary']),
                Tables\Filters\SelectFilter::make('tag')
                    ->options(['basic'=>'Basic','intermediate'=>'Intermediate','advanced'=>'Advanced']),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->recordActions([\Filament\Actions\ViewAction::make(), \Filament\Actions\EditAction::make(), \Filament\Actions\DeleteAction::make()])
            ->toolbarActions([\Filament\Actions\BulkActionGroup::make([\Filament\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'view'   => Pages\ViewQuestion::route('/{record}'),
            'edit'   => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }
}
