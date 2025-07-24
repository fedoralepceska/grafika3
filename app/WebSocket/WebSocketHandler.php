<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Illuminate\Support\Facades\Log;

class WebSocketHandler implements MessageComponentInterface
{
    protected $clients;
    protected $channels;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->channels = [];
        $this->lastBroadcastCheck = 0;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        Log::info("New WebSocket connection! ({$conn->resourceId})");
        echo "New WebSocket connection! ({$conn->resourceId})\n";
        
        // Send a welcome message to confirm connection
        $welcome = json_encode([
            'event' => 'connected',
            'message' => 'WebSocket connection established',
            'connection_id' => $conn->resourceId
        ]);
        $conn->send($welcome);
        echo "Sent welcome message to {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo "Received message from {$from->resourceId}: {$msg}\n";
        
        $data = json_decode($msg, true);
        
        if (!$data) {
            echo "Failed to decode JSON: {$msg}\n";
            return;
        }

        echo "Processing event: " . ($data['event'] ?? 'unknown') . "\n";

        switch ($data['event'] ?? '') {
            case 'subscribe':
                $this->subscribeToChannel($from, $data['channel'] ?? '');
                break;
            case 'unsubscribe':
                $this->unsubscribeFromChannel($from, $data['channel'] ?? '');
                break;
            case 'broadcast':
                $this->broadcastToChannel($data['channel'] ?? '', $data['data'] ?? []);
                break;
            case 'ping':
                // Respond to ping with pong
                $response = json_encode(['event' => 'pong', 'timestamp' => time()]);
                $from->send($response);
                echo "Sent pong response to {$from->resourceId}\n";
                break;
            default:
                echo "Unknown event: " . ($data['event'] ?? 'none') . "\n";
                break;
        }
        
        // Check for broadcast messages from Laravel on every message
        $this->checkBroadcastMessages();
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        $this->removeFromAllChannels($conn);
        Log::info("Connection {$conn->resourceId} has disconnected");
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        Log::error("An error has occurred: {$e->getMessage()}");
        echo "WebSocket Error: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function subscribeToChannel(ConnectionInterface $conn, $channel)
    {
        if (!isset($this->channels[$channel])) {
            $this->channels[$channel] = new \SplObjectStorage;
        }
        
        $this->channels[$channel]->attach($conn);
        Log::info("Client {$conn->resourceId} subscribed to channel: {$channel}");
        echo "Client {$conn->resourceId} subscribed to channel: {$channel}\n";
        
        // Send confirmation back to client
        $conn->send(json_encode([
            'event' => 'subscribed',
            'channel' => $channel,
            'message' => "Successfully subscribed to {$channel}"
        ]));
    }

    protected function unsubscribeFromChannel(ConnectionInterface $conn, $channel)
    {
        if (isset($this->channels[$channel])) {
            $this->channels[$channel]->detach($conn);
            Log::info("Client {$conn->resourceId} unsubscribed from channel: {$channel}");
            echo "Client {$conn->resourceId} unsubscribed from channel: {$channel}\n";
        }
    }

    protected function removeFromAllChannels(ConnectionInterface $conn)
    {
        foreach ($this->channels as $channel => $clients) {
            $this->channels[$channel]->detach($conn);
        }
    }

    public function broadcastToChannel($channel, $data)
    {
        if (!isset($this->channels[$channel])) {
            return;
        }

        $message = json_encode([
            'channel' => $channel,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ]);

        foreach ($this->channels[$channel] as $client) {
            $client->send($message);
        }

        Log::info("Broadcasted to channel {$channel}: " . json_encode($data));
    }

    public function broadcastToAll($data)
    {
        $message = json_encode([
            'data' => $data,
            'timestamp' => now()->toISOString()
        ]);

        foreach ($this->clients as $client) {
            $client->send($message);
        }
    }
    
    protected function checkBroadcastMessages()
    {
        $broadcastFile = storage_path('broadcasts.jsonl');
        
        if (!file_exists($broadcastFile)) {
            return;
        }
        
        $lines = file($broadcastFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            return;
        }
        
        echo "Found " . count($lines) . " broadcast messages to process\n";
        
        foreach ($lines as $line) {
            $data = json_decode($line, true);
            if ($data && isset($data['channel']) && isset($data['data'])) {
                // Handle both string and object channel formats
                $channelName = is_string($data['channel']) ? $data['channel'] : $data['channel']['name'];
                echo "Broadcasting message from file to channel {$channelName}: " . json_encode($data['data']) . "\n";
                
                // Send the message in the format expected by the frontend
                $message = json_encode([
                    'channel' => $channelName,
                    'data' => $data['data'],
                    'timestamp' => now()->toISOString()
                ]);
                
                if (isset($this->channels[$channelName])) {
                    foreach ($this->channels[$channelName] as $client) {
                        $client->send($message);
                    }
                    echo "Sent broadcast message to " . $this->channels[$channelName]->count() . " clients on channel {$channelName}\n";
                } else {
                    echo "No clients subscribed to channel {$channelName}\n";
                }
            }
        }
        
        // Clear the file after processing
        file_put_contents($broadcastFile, '');
        echo "Broadcast file cleared\n";
    }
} 