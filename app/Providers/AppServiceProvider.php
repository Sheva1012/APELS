<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                // Cache settings for 1 hour to prevent DB query on every request
                $settings = \Illuminate\Support\Facades\Cache::remember('app_settings', 3600, function () {
                    return \App\Models\Setting::all()->pluck('value', 'key')->toArray();
                });

                if (isset($settings['GROQ_API_KEY'])) {
                    config(['groq.api_key' => $settings['GROQ_API_KEY']]);
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
        } catch (\Throwable $e) {
            // Ignore DB errors during initial migrations
        }
    }
}
