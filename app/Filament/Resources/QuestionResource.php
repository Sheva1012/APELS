<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionResource\Pages;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Actions;
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
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->hasAnyRole(['admin', 'dosen']) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('set')
                ->label('Set Soal')
                ->options(function (?string $state = null) {
                    $sets = Question::whereNotNull('set')->distinct()->pluck('set', 'set')->toArray();
                    $defaults = ['A' => 'Set A', 'B' => 'Set B', 'C' => 'Set C'];
                    foreach ($sets as $key => $val) {
                        $keyStr = strtoupper(trim((string) $key));
                        if ($keyStr !== '') {
                            $code = str_starts_with($keyStr, 'SET ') ? strtoupper(trim(substr($keyStr, 4))) : $keyStr;
                            $defaults[$code] = 'Set ' . $code;
                        }
                    }
                    if ($state !== null && $state !== '') {
                        $stateStr = strtoupper(trim((string) $state));
                        $stateCode = str_starts_with($stateStr, 'SET ') ? strtoupper(trim(substr($stateStr, 4))) : $stateStr;
                        if (!isset($defaults[$stateCode])) {
                            $defaults[$stateCode] = 'Set ' . $stateCode;
                        }
                    }
                    ksort($defaults);
                    return $defaults;
                })
                ->createOptionForm([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama/Kode Set Baru (Contoh: Set D, Set E)')
                        ->required()
                        ->maxLength(15),
                ])
                ->createOptionUsing(function (array $data) {
                    $name = trim($data['name']);
                    if (str_starts_with(strtoupper($name), 'SET ')) {
                        return strtoupper(trim(substr($name, 4)));
                    }
                    return strtoupper($name);
                })
                ->searchable()
                ->required(),
            Forms\Components\RichEditor::make('question')->required()->maxLength(1000)->columnSpanFull(),
            Forms\Components\TextInput::make('option_a')->label('Option A')->required()->maxLength(1000),
            Forms\Components\TextInput::make('option_b')->label('Option B')->required()->maxLength(1000),
            Forms\Components\TextInput::make('option_c')->label('Option C')->required()->maxLength(1000),
            Forms\Components\TextInput::make('option_d')->label('Option D')->required()->maxLength(1000),
            Forms\Components\Select::make('correct_answer')
                ->options(['a'=>'A','b'=>'B','c'=>'C','d'=>'D'])->required(),
            Forms\Components\Hidden::make('question_type')->default('multiple_choice'),
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
                    ->formatStateUsing(function (?string $state): string {
                        if (!$state) return '';
                        $upper = strtoupper(trim($state));
                        return str_starts_with($upper, 'SET ') ? strtoupper(trim(substr($upper, 4))) : $upper;
                    })
                    ->color(fn (?string $state): string => match (strtoupper(trim((string) $state))) {
                        'A', 'SET A' => 'primary',
                        'B', 'SET B' => 'warning',
                        'C', 'SET C' => 'success',
                        'D', 'SET D' => 'info',
                        default => 'gray',
                    })->sortable(),
                Tables\Columns\TextColumn::make('question')->formatStateUsing(fn ($state) => html_entity_decode(strip_tags($state), ENT_QUOTES | ENT_HTML5, 'UTF-8'))->limit(60)->searchable()->sortable(),
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
                    ->options(function () {
                        $sets = Question::whereNotNull('set')->distinct()->pluck('set', 'set')->toArray();
                        $defaults = ['A' => 'Set A', 'B' => 'Set B', 'C' => 'Set C'];
                        foreach ($sets as $key => $val) {
                            $keyStr = strtoupper(trim((string) $key));
                            if ($keyStr !== '') {
                                $code = str_starts_with($keyStr, 'SET ') ? strtoupper(trim(substr($keyStr, 4))) : $keyStr;
                                $defaults[$code] = 'Set ' . $code;
                            }
                        }
                        ksort($defaults);
                        return $defaults;
                    }),
                Tables\Filters\SelectFilter::make('type')
                    ->options(['grammar'=>'Grammar','vocabulary'=>'Vocabulary']),
                Tables\Filters\SelectFilter::make('tag')
                    ->options(['basic'=>'Basic','intermediate'=>'Intermediate','advanced'=>'Advanced']),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->recordActions([Actions\ViewAction::make(), Actions\EditAction::make(), Actions\DeleteAction::make()])
            ->toolbarActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
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
