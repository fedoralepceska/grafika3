<?php

namespace App\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Http\Request;
use App\Services\WebSocketBroadcastService;

class WebSocketBroadcaster extends Broadcaster
{
    protected $websocketService;

    public function __construct(WebSocketBroadcastService $websocketService)
    {
        $this->websocketService = $websocketService;
    }

    public function auth($request)
    {
        // Implement authentication logic here
        // For now, we'll allow all authenticated users
        if (!auth()->check()) {
            abort(403);
        }

        $channelName = $request->input('channel_name');
        $socketId = $request->input('socket_id');

        return response()->json([
            'auth' => 'your-auth-key-here'
        ]);
    }

    public function validAuthenticationResponse($request, $result)
    {
        return response()->json($result);
    }

    public function broadcast(array $channels, $event, array $payload = [])
    {
        foreach ($channels as $channel) {
            WebSocketBroadcastService::broadcast($channel, $event, $payload);
        }
    }
} 