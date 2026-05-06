<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Services\MediaMtxConfigService;

class Camera extends Model
{
    use HasFactory;

    protected $fillable = [
        'nvr_id',
        'name',
        'type',
        'channel_number',
        'rtsp_live_url',
        'rtsp_playback_template',
        'onvif_url',
        'location',
        'is_active',
        'is_recording',
        'last_health_check',
        'health_status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_recording' => 'boolean',
        'last_health_check' => 'datetime',
    ];

    /**
     * Связь с NVR
     */
    public function nvr(): BelongsTo
    {
        return $this->belongsTo(Nvr::class);
    }

    /**
     * Связь с группами камер
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(CameraGroup::class, 'camera_group_cameras');
    }

    /**
     * Событие: После создания камеры
     * ВРЕМЕННО ОТКЛЮЧЕНО ДЛЯ ТЕСТИРОВАНИЯ
     */
    protected static function booted()
    {
        // Раскомментируй когда будем включать автоматизацию
        static::created(function (Camera $camera) {
            if ($camera->is_active && $camera->rtsp_live_url) {
                $service = app(MediaMtxConfigService::class);
                $service->addCamera($camera->channel_number, $camera->rtsp_live_url);
            }
        });

        static::updated(function (Camera $camera) {
            if ($camera->is_active && $camera->rtsp_live_url) {
                $service = app(MediaMtxConfigService::class);
                $service->addCamera($camera->channel_number, $camera->rtsp_live_url);
            } elseif (!$camera->is_active) {
                $service = app(MediaMtxConfigService::class);
                $service->removeCamera($camera->channel_number);
            }
        });

        static::deleted(function (Camera $camera) {
            $service = app(MediaMtxConfigService::class);
            $service->removeCamera($camera->channel_number);
        });
    }
}