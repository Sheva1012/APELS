<?php

namespace App\Filament\Resources\QuestionResource\Pages;

use App\Filament\Resources\QuestionResource;
use App\Services\Csv\CsvQuestionImporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

/**
 * ListQuestions — includes Import header action (Req 20.1) and Export PDF (Req 20.5).
 */
class ListQuestions extends ListRecords
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            // Import CSV/XLSX (Req 20.1-20.4)
            Actions\Action::make('import')
                ->label('Import')
                ->icon('heroicon-o-arrow-up-tray')
                ->modalDescription(
                    view('filament.partials.import-csv-hint')
                )
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('CSV or XLSX File')
                        ->acceptedFileTypes([
                            'text/csv',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $filePath = $data['file'];

                    // Get the uploaded file from Livewire temp storage
                    $fullPath = Storage::disk('local')->path($filePath);

                    if (!file_exists($fullPath)) {
                        Notification::make()
                            ->title('File tidak ditemukan')
                            ->danger()
                            ->send();
                        return;
                    }

                    $content = file_get_contents($fullPath);
                    $importer = app(CsvQuestionImporter::class);
                    $result = $importer->parse($content);

                    // Create valid records
                    $created = 0;
                    foreach ($result['valid'] as $row) {
                        \App\Models\Question::create($row);
                        $created++;
                    }

                    $errorCount = count($result['errors']);

                    if ($errorCount > 0) {
                        $errorSummary = collect($result['errors'])
                            ->take(5)
                            ->map(fn ($e) => "Baris {$e['row']}: " . implode(', ', $e['reasons']))
                            ->implode("\n");

                        Notification::make()
                            ->title("Import selesai: {$created} berhasil, {$errorCount} gagal")
                            ->body($errorSummary)
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title("Import berhasil: {$created} soal ditambahkan")
                            ->success()
                            ->send();
                    }

                    // Cleanup temp file
                    Storage::disk('local')->delete($filePath);
                }),

            // Export PDF (Req 20.5)
            Actions\Action::make('export')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    $questions = \App\Models\Question::orderBy('set')->orderBy('type')->orderBy('tag')->get();

                    $pdf = Pdf::loadView('pdf.questions-export', compact('questions'))
                        ->setPaper('a4', 'landscape');

                    $filename = 'questions_export_' . now()->format('Ymd_His') . '.pdf';

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, $filename, [
                        'Content-Type' => 'application/pdf',
                    ]);
                }),
        ];
    }
}
