<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'module_id',
        'set',
        'question',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_answer',
        'type',
        'tag',
        'difficulty',
        'is_active',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'difficulty' => 'integer',
    ];

    /**
     * Hanya soal yang aktif.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Filter soal berdasarkan tipe (`grammar` atau `vocabulary`).
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Filter soal berdasarkan tag (`basic`, `intermediate`, `advanced`).
     */
    public function scopeByTag(Builder $query, string $tag): Builder
    {
        return $query->where('tag', $tag);
    }

    /**
     * Filter soal berdasarkan set (`A`, `B`, atau `C`) — dipakai DiagnosticService.
     */
    public function scopeBySet(Builder $query, string $set): Builder
    {
        return $query->where('set', $set);
    }
}
