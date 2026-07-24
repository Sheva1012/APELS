<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Students', \App\Models\User::role('mahasiswa')->count())
                ->description('Registered students')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Total Modules', \App\Models\Module::count())
                ->description('Available learning modules')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('success'),
            Stat::make('Total Questions', \App\Models\Question::count())
                ->description('Questions in database')
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->color('warning'),
            Stat::make('Diagnostic Tests', \App\Models\DiagnosticResult::count())
                ->description('Tests completed')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}
