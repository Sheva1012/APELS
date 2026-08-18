<?php

namespace App\Filament\Resources\ModuleResource\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Textarea::make('title')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Hidden::make('type')
                    ->default('text'),
                Forms\Components\RichEditor::make('content')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('estimated_minutes')
                    ->numeric()
                    ->suffix('minutes'),
                Forms\Components\TextInput::make('order_index')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->rules(['integer', 'min:0'])
                    ->extraInputAttributes(['min' => 0])
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('order_index')->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('order_index')
            ->reorderable('order_index')
            ->filters([
                //
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
