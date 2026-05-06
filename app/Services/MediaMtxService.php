<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MediaMtxService
{
    protected string $apiUrl;
    protected string $configPath;

    public function __construct()
    {
        $this->apiUrl = config('services.mediamtx.api_url', 'http://localhost:9997');
        $this->configPath = config('services.mediamtx.config_path', '/var/www/vms/mediamtx/mediamtx.yml');
    }

    /**
     * Добавить камеру в MediaMTX
     */
    public function addCamera(int $channelNumber, string $rtspUrl): bool
    {
        try {
            $pathName = "camera{$channelNumber}";
            
            // Проверяем существует ли path
            $response = Http::get("{$this->apiUrl}/v3/paths/get/{$pathName}");
            
            if ($response->successful()) {
                Log::info("MediaMTX: Path {$pathName} already exists, updating...");
                return $this->updateCamera($channelNumber, $rtspUrl);
            }

            // Создаём новый path через API
            $response = Http::post("{$this->apiUrl}/v3/config/paths/add/{$pathName}", [
                'source' => $rtspUrl,
                'sourceProtocol' => 'tcp',
                'runOnDemand' => $this->getTranscodeCommand($channelNumber, $rtspUrl),
                'runOnDemandRestart' => true,
            ]);

            if ($response->successful()) {
                Log::info("MediaMTX: Successfully added camera{$channelNumber}");
                return true;
            }

            Log::error("MediaMTX: Failed to add camera{$channelNumber}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            return false;

        } catch (\Exception $e) {
            Log::error("MediaMTX: Exception adding camera{$channelNumber}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Обновить камеру в MediaMTX
     */
    public function updateCamera(int $channelNumber, string $rtspUrl): bool
    {
        try {
            $pathName = "camera{$channelNumber}";
            
            $response = Http::patch("{$this->apiUrl}/v3/config/paths/edit/{$pathName}", [
                'source' => $rtspUrl,
                'runOnDemand' => $this->getTranscodeCommand($channelNumber, $rtspUrl),
            ]);

            if ($response->successful()) {
                Log::info("MediaMTX: Successfully updated camera{$channelNumber}");
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error("MediaMTX: Exception updating camera{$channelNumber}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Удалить камеру из MediaMTX
     */
    public function removeCamera(int $channelNumber): bool
    {
        try {
            $pathName = "camera{$channelNumber}";
            
            $response = Http::delete("{$this->apiUrl}/v3/config/paths/remove/{$pathName}");

            if ($response->successful()) {
                Log::info("MediaMTX: Successfully removed camera{$channelNumber}");
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error("MediaMTX: Exception removing camera{$channelNumber}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получить команду транскодинга FFmpeg
     */
    protected function getTranscodeCommand(int $channelNumber, string $rtspUrl): string
    {
        return sprintf(
            'ffmpeg -rtsp_transport tcp -i %s -c:v libx264 -preset ultrafast -tune zerolatency -b:v 1500k -maxrate 2000k -bufsize 3000k -pix_fmt yuv420p -g 50 -c:a aac -b:a 64k -f rtsp rtsp://localhost:$RTSP_PORT/$MTX_PATH',
            escapeshellarg($rtspUrl)
        );
    }
}
