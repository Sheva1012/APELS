<?php

namespace App\Filament\Widgets;

use Filament\Actions\BulkActionGroup;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LatestDiagnosticResults extends TableWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\DiagnosticResult::query()->latest()->limit(5)
            )
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('overall')
                    ->label('Overall Score')
                    ->badge()
                    ->color(fn ($state) => $state >= 70 ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state) => number_format($state, 1)),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y H:i'),
            ])
            ->paginated(false);
    }
}
