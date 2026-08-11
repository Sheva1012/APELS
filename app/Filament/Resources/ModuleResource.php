<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModuleResource\Pages;
use App\Filament\Resources\ModuleResource\RelationManagers;
use App\Models\Module;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ModuleResource extends Resource
{
    protected static ?string $model = Module::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-book-open';
    protected static \UnitEnum|string|null $navigationGroup = 'Konten';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->hasAnyRole(['admin', 'dosen']) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $pathKeys = array_keys(config('learning_paths', []));
        $pathOptions = array_combine($pathKeys, array_map(
            fn ($key) => config("learning_paths.{$key}.label", $key), $pathKeys
        ));

        return $schema->components([
            Tabs::make('Module Builder')->tabs([
                Tab::make('Module Info')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('title')->required()->maxLength(255),
                        Forms\Components\Textarea::make('description')->maxLength(5000)->rows(3),
                        Forms\Components\Select::make('path_key')->label('Learning Path')
                            ->options($pathOptions)->required()->searchable(),
                        Forms\Components\Select::make('level')
                            ->options(['basic'=>'Basic','intermediate'=>'Intermediate','advanced'=>'Advanced'])
                            ->required(),
                        Forms\Components\Select::make('tag')
                            ->options(['grammar'=>'Grammar','vocabulary'=>'Vocabulary'])->required(),
                        Forms\Components\TextInput::make('order_index')->numeric()->default(0)->minValue(0),
                        Forms\Components\Toggle::make('is_active')->default(true),
                    ])->columns(2),
                Tab::make('AI Open Prompt')
                    ->icon('heroicon-o-sparkles')
                    ->schema([
                        Forms\Components\Textarea::make('writing_prompt')
                            ->label('Writing Task Prompt (Instruction for Student)')
                            ->rows(4)->columnSpanFull()
                            ->placeholder('e.g., Write a short email (40–60 words) to the finance department...'),
                        Forms\Components\Textarea::make('ai_evaluation_parameters')
                            ->label('AI Evaluation Parameters')
                            ->rows(4)->columnSpanFull()
                            ->placeholder('e.g., Salutation check, inquiry phrases, Grammar & Spelling'),
                    ]),
            ])->columnSpanFull()
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable()->limit(50),
                Tables\Columns\TextColumn::make('path_key')->label('Path')->badge(),
                Tables\Columns\TextColumn::make('level')->badge(),
                Tables\Columns\TextColumn::make('tag'),
                Tables\Columns\TextColumn::make('order_index')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d M Y')->sortable(),
            ])
            ->defaultSort('order_index')
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->options(['basic'=>'Basic','intermediate'=>'Intermediate','advanced'=>'Advanced']),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->recordActions([Actions\ViewAction::make(), Actions\EditAction::make(), Actions\DeleteAction::make()])
            ->toolbarActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LessonsRelationManager::class,
            RelationManagers\QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListModules::route('/'),
            'create' => Pages\CreateModule::route('/create'),
            'view'   => Pages\ViewModule::route('/{record}'),
            'edit'   => Pages\EditModule::route('/{record}/edit'),
        ];
    }
}
