<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Layout extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'user_id',
        'grid_type',
        'is_default',
        'is_public',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_public' => 'boolean',
    ];

    /**
     * Владелец раскладки
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Камеры в раскладке с позициями
     */
    public function cameras(): BelongsToMany
    {
        return $this->belongsToMany(Camera::class, 'layout_cameras')
            ->withPivot('position')
            ->orderBy('layout_cameras.position');
    }

/**
     * Получить максимальное количество позиций для текущей сетки
     */
    public function getMaxPositionsAttribute(): int
    {
        return match($this->grid_type) {
            '1x1' => 1,
            '2x2' => 4,
            '3x3' => 9,
            '4x4' => 16,
            '5x5' => 25,
            '6x6' => 36,
            '6x8' => 48,
            '8x6' => 48,
            default => 4,
        };
    }
}