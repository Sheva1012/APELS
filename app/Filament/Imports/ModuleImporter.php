<?php

namespace App\Filament\Imports;

use App\Models\Module;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class ModuleImporter extends Importer
{
    protected static ?string $model = Module::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('title')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('description')
                ->rules(['nullable', 'string']),
            ImportColumn::make('path_key')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('level')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer', 'min:1']),
            ImportColumn::make('tag')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('order_index')
                ->numeric()
                ->rules(['nullable', 'integer']),
            ImportColumn::make('is_active')
                ->boolean()
                ->rules(['nullable', 'boolean']),
        ];
    }

    public function resolveRecord(): Module
    {
        // You can update existing records by searching matching columns
        // return Module::firstOrNew(['path_key' => $this->data['path_key'], 'level' => $this->data['level']]);
        
        return new Module();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your module import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
