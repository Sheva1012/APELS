<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'title',
        'type',
        'content',
        'estimated_minutes',
        'order_index',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_index' => 'integer',
        'estimated_minutes' => 'integer',
    ];

    /**
     * Module that owns this lesson.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
