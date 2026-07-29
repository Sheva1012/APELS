<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GroqService — HTTP client wrapper for Groq Cloud API.
 *
 * Provides methods for:
 *   - Audio transcription (Whisper Large V3)
 *   - Chat completions (Llama-3.3-70B)
 *
 * Uses Laravel HTTP client with configurable timeout and error handling.
 * Groq API is OpenAI-compatible, so request/response format follows OpenAI spec.
 *
 * Free tier limits (as of 2026):
 *   - 30 requests per minute
 *   - 7,000 requests per day
 *
 * Requirements: Groq integration (CLAUDE.md section 2, 3.4, 9, 12)
 */
class GroqService
{
    private string $apiKey;
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->apiKey = config('groq.api_key') ?? '';
        $this->baseUrl = config('groq.base_url') ?? 'https://api.groq.com/openai/v1';
        $this->timeout = (int) (config('groq.timeout') ?? 60);
    }

    /**
     * Transcribe audio file using Groq Whisper Large V3.
     *
     * @param  string  $audioPath  Absolute path to audio file on disk.
     * @param  string  $language   Language code (default: 'en').
     * @return array{text: string}  Transcription result.
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function transcribeAudio(string $audioPath, string $language = 'en'): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('GROQ_API_KEY is not set in environment variables.');
        }

        $model = config('groq.whisper_model', 'whisper-large-v3');

        $response = Http::timeout($this->timeout)
            ->retry(3, 1000, function (\Throwable $exception, \Illuminate\Http\Client\PendingRequest $request) {
                if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                    return in_array($exception->response->status(), [429, 500, 502, 503, 504]);
                }
                return $exception instanceof \Illuminate\Http\Client\ConnectionException;
            }, throw: false)
            ->withoutVerifying()
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])
            ->attach('file', file_get_contents($audioPath), basename($audioPath))
            ->post($this->baseUrl . '/audio/transcriptions', [
                'model'    => $model,
                'language' => $language,
            ]);

        if ($response->failed()) {
            $status = $response->status();
            $errorMsg = match ($status) {
                429 => 'Layanan Groq API melebihi batas (rate limit 30 req/menit). Silakan coba lagi beberapa saat.',
                500, 502, 503, 504 => 'Layanan Groq API sedang tidak dapat diakses. Silakan coba lagi nanti.',
                401, 403 => 'Otentikasi Groq API gagal. Periksa GROQ_API_KEY.',
                default => "Groq API Error (HTTP {$status}): " . $response->body(),
            };

            Log::error("GroqService::transcribeAudio — {$errorMsg}", [
                'status'       => $status,
                'body'         => $response->body(),
                'audio_path'   => $audioPath,
            ]);

            throw new \RuntimeException($errorMsg, $status);
        }

        return $response->json();
    }

    /**
     * Create chat completion using Groq LLM (Llama-3.3-70B).
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array{
     *     model?: string,
     *     max_tokens?: int,
     *     temperature?: float,
     *     response_format?: array{type: string}
     * }  $options
     * @return array{
     *     id: string,
     *     choices: array<int, array{message: array{content: string}}>,
     *     usage?: array
     * }
     * @throws \RuntimeException
     */
    public function chatCompletion(array $messages, array $options = []): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('GROQ_API_KEY is not set in environment variables.');
        }

        $model = $options['model'] ?? config('groq.llm_model', 'llama-3.3-70b-versatile');

        $payload = [
            'model'    => $model,
            'messages' => $messages,
        ];

        // Optional parameters
        if (isset($options['max_tokens'])) {
            $payload['max_tokens'] = $options['max_tokens'];
        }

        if (isset($options['temperature'])) {
            $payload['temperature'] = $options['temperature'];
        }

        // JSON mode support (Groq llama-3.3-70b supports this)
        if (isset($options['response_format']) && config('groq.json_mode')) {
            $payload['response_format'] = $options['response_format'];
        }

        $response = Http::timeout($this->timeout)
            ->retry(3, 1000, function (\Throwable $exception, \Illuminate\Http\Client\PendingRequest $request) {
                if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                    return in_array($exception->response->status(), [429, 500, 502, 503, 504]);
                }
                return $exception instanceof \Illuminate\Http\Client\ConnectionException;
            }, throw: false)
            ->withoutVerifying()
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->post($this->baseUrl . '/chat/completions', $payload);

        if ($response->failed()) {
            $status = $response->status();
            $errorMsg = match ($status) {
                429 => 'Layanan Groq API melebihi batas (rate limit 30 req/menit). Silakan coba lagi beberapa saat.',
                500, 502, 503, 504 => 'Layanan Groq API sedang tidak dapat diakses. Silakan coba lagi nanti.',
                401, 403 => 'Otentikasi Groq API gagal. Periksa GROQ_API_KEY.',
                default => "Groq API Error (HTTP {$status}): " . $response->body(),
            };

            Log::error("GroqService::chatCompletion — {$errorMsg}", [
                'status'   => $status,
                'body'     => $response->body(),
                'model'    => $model,
                'messages' => $messages,
            ]);

            throw new \RuntimeException($errorMsg, $status);
        }

        return $response->json();
    }

    /**
     * Check if Groq API is configured properly.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->baseUrl);
    }

    /**
     * Get current API configuration (for debugging).
     *
     * @return array{
     *     api_key_set: bool,
     *     base_url: string,
     *     timeout: int,
     *     whisper_model: string,
     *     llm_model: string
     * }
     */
    public function getConfig(): array
    {
        return [
            'api_key_set'   => !empty($this->apiKey),
            'base_url'      => $this->baseUrl,
            'timeout'       => $this->timeout,
            'whisper_model' => config('groq.whisper_model'),
            'llm_model'     => config('groq.llm_model'),
        ];
    }
}
