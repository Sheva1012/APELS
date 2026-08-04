<?php

namespace App\Filament\Resources\ModuleResource\Pages;

use App\Filament\Resources\ModuleResource;
use App\Filament\Imports\ModuleImporter;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListModules extends ListRecords
{
    protected static string $resource = ModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('import')
                ->label('Import module')
                ->icon('heroicon-o-arrow-up-tray')
                ->modalDescription(
                    str('Download template Excel: <a href="'.asset('templates/module_import_template.xlsx').'" style="color: #3b82f6; text-decoration: underline; font-weight: bold;" download>module_import_template.xlsx</a>')
                        ->toHtmlString()
                )
                ->form([
                    \Filament\Forms\Components\FileUpload::make('file')
                        ->label('Excel File (.xlsx)')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $filePath = $data['file'];
                    $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($filePath);

                    if (!file_exists($fullPath)) {
                        \Filament\Notifications\Notification::make()
                            ->title('File tidak ditemukan')
                            ->danger()
                            ->send();
                        return;
                    }

                    $importer = app(\App\Services\Excel\ExcelModuleImporter::class);
                    $result = $importer->parse($fullPath);

                    $created = 0;
                    $errorCount = count($result['errors']);

                    foreach ($result['valid'] as $item) {
                        $moduleData = $item['module'];
                        $lessonsData = $item['lessons'];
                        $questionsData = $item['questions'] ?? [];

                        // Create module
                        $module = \App\Models\Module::create($moduleData);

                        // Create skeleton lessons
                        foreach ($lessonsData as $lesson) {
                            $module->lessons()->create($lesson);
                        }

                        // Create skeleton questions
                        foreach ($questionsData as $question) {
                            $module->questions()->create($question);
                        }
                        
                        $created++;
                    }

                    if ($errorCount > 0) {
                        $errorSummary = collect($result['errors'])
                            ->take(5)
                            ->map(fn ($e) => "Baris {$e['row']}: " . implode(', ', $e['reasons']))
                            ->implode("\n");

                        \Filament\Notifications\Notification::make()
                            ->title("Import selesai: {$created} berhasil, {$errorCount} gagal")
                            ->body($errorSummary)
                            ->warning()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title("Import berhasil: {$created} modul beserta kerangka lesson ditambahkan")
                            ->success()
                            ->send();
                    }

                    \Illuminate\Support\Facades\Storage::disk('local')->delete($filePath);
                }),
        ];
    }
}
