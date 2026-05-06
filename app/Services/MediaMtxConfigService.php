<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MediaMtxConfigService
{
    protected string $configPath;
    protected string $serviceName = 'mediamtx';

    public function __construct()
    {
        $this->configPath = '/var/www/vms/mediamtx/mediamtx.yml';
    }

    /**
     * Добавить камеру в конфиг MediaMTX
     */
    public function addCamera(int $channelNumber, string $rtspUrl): bool
    {
        try {
            $pathName = "camera{$channelNumber}";
            
            // Читаем текущий конфиг
            $content = file_get_contents($this->configPath);
            
            // Проверяем есть ли уже эта камера
            if (preg_match("/^\s*{$pathName}:/m", $content)) {
                Log::info("MediaMTX: Path {$pathName} already exists, skipping");
                return true;
            }

            // Делаем backup
            $backupPath = $this->configPath . '.backup.' . time();
            copy($this->configPath, $backupPath);

            // Формируем новую секцию
            $newPath = $this->formatPath($pathName, $rtspUrl);
            
            // Находим секцию paths и добавляем туда
            if (preg_match('/^paths:\s*$/m', $content)) {
                // Есть секция paths, добавляем в конец
                $content = rtrim($content) . "\n" . $newPath . "\n";
            } else {
                // Нет секции paths, создаём
                $content .= "\npaths:\n" . $newPath . "\n";
            }

            // Записываем
            file_put_contents($this->configPath, $content);
            chmod($this->configPath, 0644);

            // Перезапускаем MediaMTX
            $this->reloadService();

            Log::info("MediaMTX: Successfully added camera{$channelNumber}");
            return true;

        } catch (\Exception $e) {
            Log::error("MediaMTX: Failed to add camera{$channelNumber}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Удалить камеру из конфига
     */
    public function removeCamera(int $channelNumber): bool
    {
        try {
            $pathName = "camera{$channelNumber}";
            
            // Читаем конфиг
            $content = file_get_contents($this->configPath);
            
            // Проверяем есть ли камера
            if (!preg_match("/^\s*{$pathName}:/m", $content)) {
                Log::info("MediaMTX: Path {$pathName} not found, skipping");
                return true;
            }

            // Делаем backup
            $backupPath = $this->configPath . '.backup.' . time();
            copy($this->configPath, $backupPath);

            // Удаляем секцию камеры (путь + все его параметры до следующего пути)
            $pattern = "/^  {$pathName}:.*?(?=^  [a-z]|\z)/ms";
            $content = preg_replace($pattern, '', $content);

            // Записываем
            file_put_contents($this->configPath, $content);
            chmod($this->configPath, 0644);

            // Перезапускаем MediaMTX
            $this->reloadService();

            Log::info("MediaMTX: Successfully removed camera{$channelNumber}");
            return true;

        } catch (\Exception $e) {
            Log::error("MediaMTX: Failed to remove camera{$channelNumber}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Форматировать секцию path для YAML
     */
    protected function formatPath(string $pathName, string $rtspUrl): string
    {
        $command = $this->getTranscodeCommand($rtspUrl);
        
        return "  {$pathName}:\n" .
               "    runOnDemand: '{$command}'\n" .
               "    runOnDemandRestart: yes";
    }

    /**
     * Перезапустить MediaMTX
     */
    protected function reloadService(): void
    {
        exec('sudo systemctl restart mediamtx 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            Log::warning("MediaMTX: Failed to restart service", [
                'output' => $output,
                'return_code' => $returnCode
            ]);
        } else {
            Log::info("MediaMTX: Service restarted successfully");
        }
    }

    /**
     * Получить команду транскодинга
     */
    protected function getTranscodeCommand(string $rtspUrl): string
    {
        return sprintf(
            'ffmpeg -rtsp_transport tcp -i %s -c:v libx264 -preset ultrafast -tune zerolatency -b:v 1500k -maxrate 2000k -bufsize 3000k -pix_fmt yuv420p -g 50 -c:a aac -b:a 64k -f rtsp rtsp://localhost:$RTSP_PORT/$MTX_PATH',
            $rtspUrl
        );
    }
}