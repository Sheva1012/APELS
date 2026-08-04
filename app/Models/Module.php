<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent model untuk tabel `modules` (Req 10.7, 30.4).
 *
 * Field utama:
 *   - title, description, path_key, level, tag, order_index, is_active.
 *
 * Cast:
 *   - `is_active` => boolean.
 *   - `order_index` => integer untuk sort Learning Roadmap (Req 14.1).
 *
 * Scope:
 *   - `active()` — hanya modul `is_active = true` (Req 10.6).
 *   - `byPath($pathKey)` — filter berdasarkan path key aktif mahasiswa
 *     (dipakai AdaptiveEngineService::resolveModules, Req 10.1).
 */
class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'path_key',
        'level',
        'tag',
        'order_index',
        'is_active',
        'writing_prompt',
        'ai_evaluation_parameters'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_index' => 'integer',
    ];

    /**
     * Lessons within this module.
     *
     * @return HasMany<Lesson, $this>
     */
    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order_index');
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Progress per mahasiswa untuk modul ini.
     *
     * @return HasMany<UserModuleProgress, $this>
     */
    public function progress(): HasMany
    {
        return $this->hasMany(UserModuleProgress::class);
    }

    /**
     * Scope hanya modul aktif (`is_active` = true) — Req 10.6.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope filter berdasarkan `path_key` — dipakai AdaptiveEngineService
     * untuk resolve modul per learning path aktif (Req 10.1).
     */
    public function scopeByPath(Builder $query, string $pathKey): Builder
    {
        return $query->where('path_key', $pathKey);
    }
}
