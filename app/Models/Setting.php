<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Accessor for value: Decrypt value safely, fallback to raw or null if key changed / MAC invalid.
     */
    public function getValueAttribute($value): ?string
    {
        if (is_null($value) || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            // Return raw value or fallback if APP_KEY changed or invalid MAC
            return $value;
        } catch (\Throwable $e) {
            return $value;
        }
    }

    /**
     * Mutator for value: Encrypt value with current APP_KEY before storing.
     */
    public function setValueAttribute($value): void
    {
        if (is_null($value) || $value === '') {
            $this->attributes['value'] = $value;
        } else {
            $this->attributes['value'] = Crypt::encryptString((string) $value);
        }
    }
}
