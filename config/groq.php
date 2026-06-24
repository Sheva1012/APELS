<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Groq API Key
    |--------------------------------------------------------------------------
    |
    | Your Groq API key for accessing Groq Cloud API services.
    | Get your free API key at: https://console.groq.com/keys
    |
    | Free tier limits:
    |   - 30 requests per minute
    |   - 7,000 requests per day
    |   - Rate limit applies across all models
    */

    'api_key' => env('GROQ_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Groq API Base URL
    |--------------------------------------------------------------------------
    |
    | Base URL for Groq API endpoints. Groq is OpenAI-compatible,
    | so the endpoint structure follows OpenAI's format.
    |
    | Default: https://api.groq.com/openai/v1
    */

    'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum number of seconds to wait for a Groq API response.
    | Groq is typically very fast (much faster than OpenAI), but we set
    | a conservative timeout for large audio files.
    |
    | Default: 60 seconds
    */

    'timeout' => env('GROQ_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Whisper Model
    |--------------------------------------------------------------------------
    |
    | Groq Whisper model for speech-to-text transcription.
    |
    | Available models:
    |   - whisper-large-v3 (recommended, free tier)
    |   - whisper-large-v3-turbo (faster, free tier)
    |
    | Default: whisper-large-v3
    */

    'whisper_model' => env('GROQ_WHISPER_MODEL', 'whisper-large-v3'),

    /*
    |--------------------------------------------------------------------------
    | LLM Model for NLP Analysis
    |--------------------------------------------------------------------------
    |
    | Groq LLM model for grammar and vocabulary analysis.
    |
    | Available models (free tier):
    |   - llama-3.3-70b-versatile (recommended, best for JSON mode)
    |   - llama-3.1-70b-versatile
    |   - mixtral-8x7b-32768
    |
    | Default: llama-3.3-70b-versatile
    */

    'llm_model' => env('GROQ_LLM_MODEL', 'llama-3.3-70b-versatile'),

    /*
    |--------------------------------------------------------------------------
    | Enable Response Format JSON
    |--------------------------------------------------------------------------
    |
    | Whether to use JSON mode for LLM responses. Groq supports
    | response_format parameter for compatible models.
    |
    | Default: true
    */

    'json_mode' => env('GROQ_JSON_MODE', true),

];
