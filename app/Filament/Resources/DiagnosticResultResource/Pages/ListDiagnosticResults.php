<?php

namespace App\Filament\Resources\DiagnosticResultResource\Pages;

use App\Filament\Resources\DiagnosticResultResource;
use App\Models\DiagnosticResult;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListDiagnosticResults extends ListRecords
{
    protected static string $resource = DiagnosticResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Export PDF
            Actions\Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function () {
                    $results = DiagnosticResult::with('user')->orderBy('created_at', 'desc')->get();

                    $pdf = Pdf::loadView('pdf.diagnostic-results-export', compact('results'))
                        ->setPaper('a4', 'portrait');

                    $filename = 'diagnostic_results_' . now()->format('Ymd_His') . '.pdf';

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, $filename, [
                        'Content-Type' => 'application/pdf',
                    ]);
                }),
        ];
    }
}
