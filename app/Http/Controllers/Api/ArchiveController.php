<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\Nvr;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    public function cameras()
    {
        $cameras = Camera::where('is_active', true)
            ->with('nvr')
            ->orderBy('nvr_id')
            ->orderBy('channel_number')
            ->get()
            ->map(function($camera) {
                return [
                    'id' => $camera->id,
                    'name' => $camera->name,
                    'channel_number' => $camera->channel_number,
                    'nvr_id' => $camera->nvr_id,
                    'nvr_name' => $camera->nvr ? $camera->nvr->name : 'Без NVR',
                    'stream_path' => "nvr{$camera->nvr_id}ch{$camera->channel_number}",
                ];
            });

        return response()->json(['success' => true, 'data' => $cameras]);
    }

    public function startPlayback(Request $request)
    {
        $request->validate([
            'camera_id' => 'required|exists:cameras,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date',
        ]);

        $camera = Camera::with('nvr')->findOrFail($request->camera_id);
        $nvr = $camera->nvr;

        if (!$nvr) {
            return response()->json(['success' => false, 'message' => 'NVR не найден'], 404);
        }

        $username = $nvr->credentials['username'] ?? 'admin';
        $password = $nvr->credentials['password'] ?? '';

        $playbackUrl = $this->buildPlaybackUrl(
            $nvr->vendor,
            $nvr->ip_address,
            $nvr->rtsp_port ?? 554,
            $username,
            $password,
            $camera->channel_number,
            $request->start_time,
            $request->end_time
        );

        $streamPath = "playback_nvr{$nvr->id}ch{$camera->channel_number}_" . time();
        $this->addPlaybackToMediaMtx($streamPath, $playbackUrl, $nvr->vendor);

        return response()->json([
            'success' => true,
            'stream_path' => $streamPath,
            'hls_url' => "/hls/{$streamPath}/index.m3u8",
        ]);
    }

    private function buildPlaybackUrl(
        string $vendor,
        string $ip,
        int $port,
        string $username,
        string $password,
        int $channel,
        string $startTime,
        string $endTime
    ): string {
        return match($vendor) {
            'dahua' => sprintf(
                'rtsp://%s:%s@%s:%d/cam/playback?channel=%d&starttime=%s&endtime=%s',
                $username, $password, $ip, $port, $channel,
                date('Y_m_d_H_i_s', strtotime($startTime)),
                date('Y_m_d_H_i_s', strtotime($endTime))
            ),
            'hikvision' => sprintf(
                'rtsp://%s:%s@%s:%d/Streaming/tracks/%d01?starttime=%s&endtime=%s',
                $username, $password, $ip, $port, $channel,
                date('Ymd', strtotime($startTime)) . 'T' . date('His', strtotime($startTime)) . 'Z',
                date('Ymd', strtotime($endTime)) . 'T' . date('His', strtotime($endTime)) . 'Z'
            ),
            'polyvision' => sprintf(
                'rtsp://%s:%d/user=%s&password=%s&channel=%d&stream=0.sdp&starttime=%s&endtime=%s',
                $ip, $port, $username, $password, $channel,
                date('Ymd', strtotime($startTime)) . 'T' . date('His', strtotime($startTime)) . 'Z',
                date('Ymd', strtotime($endTime)) . 'T' . date('His', strtotime($endTime)) . 'Z'
            ),
            default => sprintf(
                'rtsp://%s:%s@%s:%d/cam/playback?channel=%d&starttime=%s&endtime=%s',
                $username, $password, $ip, $port, $channel,
                date('Y_m_d_H_i_s', strtotime($startTime)),
                date('Y_m_d_H_i_s', strtotime($endTime))
            ),
        };
    }

    private function addPlaybackToMediaMtx(string $streamPath, string $rtspUrl, string $vendor): void
    {
        $configPath = '/var/www/vms/mediamtx/mediamtx.yml';
        $content = file_get_contents($configPath);

        // Для Dahua playback - HEVC, нужен транскодинг
        // Для Polyvision - H264, просто копируем
        if ($vendor === 'polyvision') {
            $ffmpegCmd = "ffmpeg -rtsp_transport tcp -i {$rtspUrl} -c:v copy -an -f rtsp rtsp://localhost:\$RTSP_PORT/\$MTX_PATH";
        } else {
            $ffmpegCmd = "ffmpeg -rtsp_transport tcp -i {$rtspUrl} -c:v libx264 -preset ultrafast -tune zerolatency -b:v 1500k -maxrate 2000k -bufsize 3000k -pix_fmt yuv420p -g 25 -an -f rtsp rtsp://localhost:\$RTSP_PORT/\$MTX_PATH";
        }

        $newPath = "  {$streamPath}:\n    runOnDemand: '{$ffmpegCmd}'\n    runOnDemandRestart: no\n";
        $content = rtrim($content) . "\n" . $newPath;

        file_put_contents($configPath, $content);
        exec('sudo systemctl restart mediamtx');
        sleep(2);
    }
}