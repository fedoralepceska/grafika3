<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebSocketBroadcastService
{
    protected $websocketUrl;

    public function __construct()
    {
        $this->websocketUrl = config('broadcasting.connections.websocket.host', 'http://localhost:6002');
    }

    public static function broadcast($channel, $event, $data = [])
    {
        try {
            $payload = [
                'event' => 'broadcast',
                'channel' => $channel,
                'data' => [
                    'event' => $event,
                    'data' => $data,
                    'channel' => $channel
                ],
                'timestamp' => now()->toISOString()
            ];

            // Write broadcast message to a file that the WebSocket server can read
            $broadcastFile = storage_path('broadcasts.jsonl');
            $result = file_put_contents($broadcastFile, json_encode($payload) . "\n", FILE_APPEND | LOCK_EX);
            
            Log::info("Broadcasted to channel {$channel}: {$event}", [
                'data' => $data,
                'file' => $broadcastFile,
                'bytes_written' => $result,
                'payload' => $payload
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Error broadcasting to WebSocket: " . $e->getMessage());
            echo "Broadcast error: " . $e->getMessage() . "\n";
            return false;
        }
    }

    public static function broadcastToUser($userId, $event, $data = [])
    {
        return self::broadcast("private-user.{$userId}", $event, $data);
    }

    public static function broadcastToChannel($channel, $event, $data = [])
    {
        return self::broadcast($channel, $event, $data);
    }

    public static function broadcastToAll($event, $data = [])
    {
        return self::broadcast('public', $event, $data);
    }
} 