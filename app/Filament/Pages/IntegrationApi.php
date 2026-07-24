<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class IntegrationApi extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'Integration API';
    protected static ?string $title = 'Integration API Settings';
    protected static ?string $slug = 'integration-api';


    protected string $view = 'filament.pages.integration-api';

    public ?array $data = [];

    // Hanya admin yang bisa melihat dan mengakses halaman ini
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public function mount(): void
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        $this->form->fill([
            'GROQ_API_KEY' => $settings['GROQ_API_KEY'] ?? env('GROQ_API_KEY', ''),
            'APELS_AUDIO_MAX_MB' => $settings['APELS_AUDIO_MAX_MB'] ?? env('APELS_AUDIO_MAX_MB', 10),
            'APELS_STT_TIMEOUT' => $settings['APELS_STT_TIMEOUT'] ?? env('APELS_STT_TIMEOUT', 60),
            'APELS_NLP_TIMEOUT' => $settings['APELS_NLP_TIMEOUT'] ?? env('APELS_NLP_TIMEOUT', 30),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('API Configuration')
                    ->description('Masukkan API Key untuk integrasi layanan AI.')
                    ->schema([
                        TextInput::make('GROQ_API_KEY')
                            ->label('Groq API Key')
                            ->password()
                            ->revealable()
                            ->helperText('Digunakan untuk layanan STT (Whisper) dan NLP (Llama 3).')
                            ->required(),
                    ]),
                Section::make('System Settings')
                    ->description('Konfigurasi batasan sistem untuk upload dan durasi (Timeout).')
                    ->schema([
                        TextInput::make('APELS_AUDIO_MAX_MB')
                            ->label('Max Audio Size (MB)')
                            ->numeric()
                            ->helperText('Batas maksimal ukuran file audio yang dapat diunggah mahasiswa.')
                            ->required(),
                        TextInput::make('APELS_STT_TIMEOUT')
                            ->label('STT Timeout (Detik)')
                            ->numeric()
                            ->helperText('Batas waktu menunggu respons dari layanan Speech-to-Text (Whisper). Default: 60')
                            ->required(),
                        TextInput::make('APELS_NLP_TIMEOUT')
                            ->label('NLP Timeout (Detik)')
                            ->numeric()
                            ->helperText('Batas waktu menunggu respons dari layanan NLP (Llama 3). Default: 30')
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        Cache::forget('app_settings');

        Notification::make()
            ->title('Settings Saved Successfully')
            ->body('Konfigurasi telah disimpan ke dalam database secara terenkripsi.')
            ->success()
            ->send();
    }
}
