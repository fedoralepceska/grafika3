<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\WebSocketHandler;

class WebSocketServer extends Command
{
    protected $signature = 'websocket:serve {--port=6001} {--host=0.0.0.0}';
    protected $description = 'Start the WebSocket server';

    public function handle()
    {
        $port = $this->option('port');
        $host = $this->option('host');
        
        $this->info("Starting WebSocket server on {$host}:{$port}");
        
        // Create WebSocket handler instance
        $webSocketHandler = new WebSocketHandler();
        
        $server = IoServer::factory(
            new HttpServer(
                new WsServer($webSocketHandler)
            ),
            $port,
            $host
        );

        $this->info("WebSocket server is running on ws://{$host}:{$port}");
        $this->info("Broadcast checking enabled (every ping)");
        $this->info("Press Ctrl+C to stop the server");
        
        $server->run();
    }
} 