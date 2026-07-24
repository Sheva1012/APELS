<?php

namespace App\Filament\Imports;

use App\Models\Question;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

/**
 * QuestionImporter — import soal dari CSV ke tabel `questions`.
 *
 * Kolom CSV yang diharapkan (sesuai APELS-EXECUTION-SPEC §2):
 *   set, question, option_a, option_b, option_c, option_d,
 *   correct_answer, type, tag, difficulty, is_active
 */
class QuestionImporter extends Importer
{
    protected static ?string $model = Question::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('set')
                ->requiredMapping()
                ->castStateUsing(fn (?string $state) => strtoupper(trim($state ?? '')))
                ->rules(['required', 'in:A,B,C']),
            ImportColumn::make('question')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:5000']),
            ImportColumn::make('option_a')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:1000']),
            ImportColumn::make('option_b')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:1000']),
            ImportColumn::make('option_c')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:1000']),
            ImportColumn::make('option_d')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:1000']),
            ImportColumn::make('correct_answer')
                ->requiredMapping()
                ->castStateUsing(fn (?string $state) => strtolower(trim($state ?? '')))
                ->rules(['required', 'in:a,b,c,d']),
            ImportColumn::make('type')
                ->requiredMapping()
                ->castStateUsing(fn (?string $state) => strtolower(trim($state ?? '')))
                ->rules(['required', 'in:grammar,vocabulary']),
            ImportColumn::make('tag')
                ->requiredMapping()
                ->castStateUsing(fn (?string $state) => strtolower(trim($state ?? '')))
                ->rules(['required', 'in:basic,intermediate,advanced']),
            ImportColumn::make('difficulty')
                ->numeric()
                ->requiredMapping()
                ->rules(['required', 'integer', 'min:1', 'max:5']),
            ImportColumn::make('is_active')
                ->boolean()
                ->rules(['nullable', 'boolean']),
        ];
    }

    public function resolveRecord(): Question
    {
        // updateOrCreate berdasarkan kombinasi set + question (unik per soal)
        return Question::firstOrNew([
            'set'      => $this->data['set'],
            'question' => $this->data['question'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import soal selesai. ' . Number::format($import->successful_rows) . ' ' . str('baris')->plural($import->successful_rows) . ' berhasil diimport.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' baris gagal.';
        }

        return $body;
    }
}
