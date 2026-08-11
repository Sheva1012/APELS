<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */ 
    public function boot(): void
    {
        // Fallback for Filament HTML Sanitizer on PHP < 8.4 / Shared hosting (InfinityFree)
        // Prevents: Class "Dom\HTMLDocument" not found
        Str::macro('sanitizeHtml', function (string $html): string {
            return strip_tags($html, '<b><i><strong><em><a><ul><ol><li><p><br><span><div><small><sub><sup>');
        });

        try {
            if (Schema::hasTable('settings')) {
                // Cache settings for 1 hour to prevent DB query on every request
                $settings = Cache::remember('app_settings', 3600, function () {
                    return Setting::all()->pluck('value', 'key')->toArray();
                });

                if (isset($settings['GROQ_API_KEY'])) {
                    $keyVal = $settings['GROQ_API_KEY'];
                    // If decryption failed, $keyVal remains an encrypted JSON string starting with "eyJ"
                    if (!str_starts_with($keyVal, 'eyJ')) {
                        config(['groq.api_key' => $keyVal]);
                    }
                }
                if (isset($settings['APELS_AUDIO_MAX_MB'])) {
                    config(['apels.audio_max_mb' => $settings['APELS_AUDIO_MAX_MB']]);
                }
                if (isset($settings['APELS_STT_TIMEOUT'])) {
                    config(['apels.stt_timeout' => $settings['APELS_STT_TIMEOUT']]);
                    config(['groq.timeout' => $settings['APELS_STT_TIMEOUT']]);
                }
                if (isset($settings['APELS_NLP_TIMEOUT'])) {
                    config(['apels.nlp_timeout' => $settings['APELS_NLP_TIMEOUT']]);
                }
            }
        } catch (Throwable $e) {
            // Ignore DB errors during initial migrations
        }
    }
}
