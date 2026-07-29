<?php

namespace Tests\Unit\Service;

use App\Services\GroqService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GroqServiceResilienceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('groq.api_key', 'test_groq_key');
        Config::set('groq.base_url', 'https://api.groq.com/openai/v1');
        Config::set('groq.timeout', 5);
    }

    public function test_chat_completion_success()
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl-123',
                'choices' => [
                    ['message' => ['content' => 'Hello student!']]
                ]
            ], 200)
        ]);

        $service = new GroqService();
        $response = $service->chatCompletion([
            ['role' => 'user', 'content' => 'Hello']
        ]);

        $this->assertEquals('chatcmpl-123', $response['id']);
        $this->assertEquals('Hello student!', $response['choices'][0]['message']['content']);
    }

    public function test_chat_completion_retries_on_rate_limit_429()
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::sequence()
                ->push('Rate limit exceeded', 429)
                ->push([
                    'id' => 'chatcmpl-456',
                    'choices' => [
                        ['message' => ['content' => 'Retried successfully!']]
                    ]
                ], 200)
        ]);

        $service = new GroqService();
        $response = $service->chatCompletion([
            ['role' => 'user', 'content' => 'Test retry']
        ]);

        $this->assertEquals('chatcmpl-456', $response['id']);
        $this->assertEquals('Retried successfully!', $response['choices'][0]['message']['content']);
    }

    public function test_chat_completion_throws_friendly_exception_on_persistent_429()
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response('Rate limit hit', 429)
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Layanan Groq API melebihi batas');

        $service = new GroqService();
        $service->chatCompletion([
            ['role' => 'user', 'content' => 'Test fail']
        ]);
    }
}
