<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LearningPath — hasil adaptive engine per user (APELS-EXECUTION-SPEC §3).
 *
 * Tabel `learning_paths` menyimpan satu record per user dengan:
 *   - path_name: key learning path (e.g., 'grammar_foundation')
 *   - modules_unlocked: JSON list of module IDs yang unlock
 *   - modules_locked: JSON list of module IDs yang locked
 */
class LearningPath extends Model
{
    use HasFactory;

    protected $table = 'learning_paths';

    protected $fillable = [
        'user_id',
        'path_name',
        'modules_unlocked',
        'modules_locked',
    ];

    protected $casts = [
        'modules_unlocked' => 'array',
        'modules_locked'   => 'array',
    ];

    /**
     * User pemilik learning path ini.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
