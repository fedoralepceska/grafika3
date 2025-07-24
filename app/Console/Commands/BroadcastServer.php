<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer as RatchetHttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\WebSocketHandler;
use React\Http\Server as ReactHttpServer;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;

class BroadcastServer extends Command
{
    protected $signature = 'broadcast:serve {--port=6002} {--host=0.0.0.0}';
    protected $description = 'Start the HTTP broadcast server';

    public function handle()
    {
        $port = $this->option('port');
        $host = $this->option('host');
        
        $this->info("Starting HTTP broadcast server on {$host}:{$port}");
        
        // Create WebSocket handler instance to access broadcast methods
        $webSocketHandler = new WebSocketHandler();
        
        // Create HTTP server
        $httpServer = new ReactHttpServer(function (ServerRequestInterface $request) use ($webSocketHandler) {
            $path = $request->getUri()->getPath();
            
            if ($path === '/broadcast' && $request->getMethod() === 'POST') {
                $body = $request->getBody()->getContents();
                $data = json_decode($body, true);
                
                if ($data && isset($data['channel']) && isset($data['data'])) {
                    $webSocketHandler->broadcastToChannel($data['channel'], $data['data']);
                    
                    return Response::json(['success' => true]);
                } else {
                    return Response::json(['error' => 'Invalid broadcast data'], 400);
                }
            }
            
            return Response::json(['error' => 'Not Found'], 404);
        });
        
        $socket = new \React\Socket\Server("{$host}:{$port}");
        $httpServer->listen($socket);
        
        $this->info("HTTP broadcast server is running on http://{$host}:{$port}");
        $this->info("Broadcast endpoint: http://{$host}:{$port}/broadcast");
        $this->info("Press Ctrl+C to stop the server");
        
        $socket->run();
    }
} 