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
                \Filament\Forms\Components\Select::make('question_type')
                    ->label('Tipe Soal')
                    ->options([
                        'multiple_choice' => 'Pilihan Ganda',
                        'short_answer' => 'Isian Singkat',
                        'matching' => 'Menjodohkan',
                        'ordering' => 'Mengurutkan',
                    ])
                    ->default('multiple_choice')
                    ->reactive()
                    ->required(),

                \Filament\Forms\Components\TextInput::make('option_a')->label('Option A')->maxLength(1000)
                    ->visible(fn ($get) => $get('question_type') === 'multiple_choice' || !$get('question_type')),
                \Filament\Forms\Components\TextInput::make('option_b')->label('Option B')->maxLength(1000)
                    ->visible(fn ($get) => $get('question_type') === 'multiple_choice' || !$get('question_type')),
                \Filament\Forms\Components\TextInput::make('option_c')->label('Option C')->maxLength(1000)
                    ->visible(fn ($get) => $get('question_type') === 'multiple_choice' || !$get('question_type')),
                \Filament\Forms\Components\TextInput::make('option_d')->label('Option D')->maxLength(1000)
                    ->visible(fn ($get) => $get('question_type') === 'multiple_choice' || !$get('question_type')),
                \Filament\Forms\Components\Select::make('correct_answer')
                    ->options(['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'])
                    ->visible(fn ($get) => $get('question_type') === 'multiple_choice' || !$get('question_type')),

                \Filament\Forms\Components\Repeater::make('answer_json.acceptable_answers')
                    ->label('Jawaban yang Dibenarkan (Isian Singkat)')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('answer')->label('Jawaban')->required(),
                    ])
                    ->visible(fn ($get) => $get('question_type') === 'short_answer'),

                \Filament\Forms\Components\Repeater::make('options_json.pairs')
                    ->label('Pasangan (Kiri - Kanan)')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('left')->label('Item Kiri')->required(),
                        \Filament\Forms\Components\TextInput::make('right')->label('Item Kanan')->required(),
                    ])
                    ->visible(fn ($get) => $get('question_type') === 'matching'),
                    
                \Filament\Forms\Components\Repeater::make('options_json.items')
                    ->label('Item (Untuk Diurutkan)')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('item')->label('Potongan Kalimat/Item')->required(),
                    ])
                    ->visible(fn ($get) => $get('question_type') === 'ordering'),
                \Filament\Forms\Components\Repeater::make('answer_json.correct_sequence')
                    ->label('Urutan Benar')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('item')->label('Isi berurutan')->required(),
                    ])
                    ->visible(fn ($get) => $get('question_type') === 'ordering'),
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
