<?php

namespace Tests\Unit\Model;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class SettingEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_setting_encrypts_and_decrypts_value()
    {
        Setting::create([
            'key' => 'TEST_KEY',
            'value' => 'my-secret-key-123',
        ]);

        $setting = Setting::where('key', 'TEST_KEY')->first();

        $this->assertEquals('my-secret-key-123', $setting->value);
    }

    public function test_setting_handles_invalid_encrypted_data_gracefully()
    {
        // Insert invalid MAC or plain text string directly into DB
        \DB::table('settings')->insert([
            'key' => 'OLD_KEY',
            'value' => 'corrupted_encrypted_payload_string',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $setting = Setting::where('key', 'OLD_KEY')->first();

        // Should not throw DecryptException, should return raw payload string
        $this->assertEquals('corrupted_encrypted_payload_string', $setting->value);
    }
}
