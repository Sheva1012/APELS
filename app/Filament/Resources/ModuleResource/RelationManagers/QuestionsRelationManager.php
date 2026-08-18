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
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                RichEditor::make('question')->required()->columnSpanFull(),
                Select::make('question_type')
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

                TextInput::make('option_a')->label('Option A')->maxLength(1000)
                    ->visible(fn ($get) => $get('question_type') === 'multiple_choice' || !$get('question_type')),
                TextInput::make('option_b')->label('Option B')->maxLength(1000)
                    ->visible(fn ($get) => $get('question_type') === 'multiple_choice' || !$get('question_type')),
                TextInput::make('option_c')->label('Option C')->maxLength(1000)
                    ->visible(fn ($get) => $get('question_type') === 'multiple_choice' || !$get('question_type')),
                TextInput::make('option_d')->label('Option D')->maxLength(1000)
                    ->visible(fn ($get) => $get('question_type') === 'multiple_choice' || !$get('question_type')),
                Select::make('correct_answer')
                    ->options(['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'])
                    ->visible(fn ($get) => $get('question_type') === 'multiple_choice' || !$get('question_type')),

                Repeater::make('answer_json.acceptable_answers')
                    ->label('Jawaban yang Dibenarkan (Isian Singkat)')
                    ->schema([
                        TextInput::make('answer')->label('Jawaban')->required(),
                    ])
                    ->visible(fn ($get) => $get('question_type') === 'short_answer'),

                Repeater::make('options_json.pairs')
                    ->label('Pasangan (Kiri - Kanan)')
                    ->schema([
                        TextInput::make('left')->label('Item Kiri')->required(),
                        TextInput::make('right')->label('Item Kanan')->required(),
                    ])
                    ->visible(fn ($get) => $get('question_type') === 'matching'),
                    
                Repeater::make('options_json.items')
                    ->label('Item (Untuk Diurutkan)')
                    ->schema([
                        TextInput::make('item')->label('Potongan Kalimat/Item')->required(),
                    ])
                    ->visible(fn ($get) => $get('question_type') === 'ordering'),
                Repeater::make('answer_json.correct_sequence')
                    ->label('Urutan Benar')
                    ->schema([
                        TextInput::make('item')->label('Isi berurutan')->required(),
                    ])
                    ->visible(fn ($get) => $get('question_type') === 'ordering'),
                Hidden::make('type')->default('grammar'),
                Hidden::make('tag')->default('basic'),
                Hidden::make('set')->default(null),
                Toggle::make('is_active')->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question')
            ->columns([
                TextColumn::make('question')->formatStateUsing(fn ($state) => html_entity_decode(strip_tags($state), ENT_QUOTES | ENT_HTML5, 'UTF-8'))->limit(50)->searchable(),
                TextColumn::make('correct_answer')->label('Correct'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->label('Create Question'),
            ])
            ->recordActions([
                ViewAction::make()->modalHeading('View Question'),
                EditAction::make()->modalHeading('Edit Question'),
                DissociateAction::make()->label('Remove from Module'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
