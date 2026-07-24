<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Eloquent model untuk tabel `diagnostic_results` (Req 4.2, 30.2).
 *
 * Menyimpan hasil Diagnostic Test per attempt: tiga skor skill (speaking,
 * grammar, vocabulary), overall weighted score, attempt number, dan path
 * audio sementara untuk pemrosesan asinkron oleh ProcessSpeakingAudio_Job.
 */
class DiagnosticResult extends Model
{
    /** @use HasFactory<\Database\Factories\DiagnosticResultFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'set_used',
        'attempt_number',
        'speaking',
        'grammar',
        'vocabulary',
        'overall',
        'attempt',
        'audio_path',
        'speaking_transcript',
        'speaking_reference',
        'is_speaking_processed',
        'answers',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'set_used'             => 'string',
            'attempt_number'       => 'integer',
            'speaking'             => 'integer',
            'grammar'              => 'integer',
            'vocabulary'           => 'integer',
            'overall'              => 'float',
            'attempt'              => 'integer',
            'speaking_transcript'  => 'string',
            'speaking_reference'   => 'string',
            'is_speaking_processed'=> 'boolean',
            'answers'              => 'array',
        ];
    }

    /**
     * Mahasiswa pemilik DiagnosticResult ini.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Feedback otomatis yang dihasilkan dari DiagnosticResult ini (Req 12.6).
     *
     * @return HasOne<Feedback, $this>
     */
    public function feedback(): HasOne
    {
        return $this->hasOne(Feedback::class);
    }
}
