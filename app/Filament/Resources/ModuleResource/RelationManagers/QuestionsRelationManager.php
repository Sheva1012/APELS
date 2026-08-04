<?php

namespace App\Filament\Resources\ModuleResource\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Textarea::make('question')->required()->rows(3)->columnSpanFull(),
                \Filament\Forms\Components\TextInput::make('option_a')->required(),
                \Filament\Forms\Components\TextInput::make('option_b')->required(),
                \Filament\Forms\Components\TextInput::make('option_c')->required(),
                \Filament\Forms\Components\TextInput::make('option_d')->required(),
                \Filament\Forms\Components\Select::make('correct_answer')
                    ->options(['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'])
                    ->required(),
                \Filament\Forms\Components\Hidden::make('type')->default('grammar'),
                \Filament\Forms\Components\Hidden::make('tag')->default('basic'),
                \Filament\Forms\Components\Hidden::make('set')->default('A'),
                \Filament\Forms\Components\Toggle::make('is_active')->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question')
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('question')->limit(50)->searchable(),
                \Filament\Tables\Columns\TextColumn::make('correct_answer')->label('Correct'),
                \Filament\Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()->label('Create Question'),
                \Filament\Actions\AssociateAction::make()->label('Pick from Bank'),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make()->modalHeading('View Question'),
                \Filament\Actions\EditAction::make()->modalHeading('Edit Question'),
                \Filament\Actions\DissociateAction::make()->label('Remove from Module'),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DissociateBulkAction::make(),
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
