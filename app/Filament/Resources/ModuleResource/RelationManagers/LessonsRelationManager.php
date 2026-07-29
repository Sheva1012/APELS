<?php

namespace App\Filament\Resources\ModuleResource\RelationManagers;

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
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'text' => 'Text',
                        'video' => 'Video',
                        'quiz' => 'Quiz',
                    ])
                    ->required()
                    ->default('text'),
                Forms\Components\RichEditor::make('content')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('estimated_minutes')
                    ->numeric()
                    ->suffix('minutes'),
                Forms\Components\TextInput::make('order_index')
                    ->numeric()
                    ->default(0)
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
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('order_index')
            ->reorderable('order_index')
            ->filters([
                //
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
