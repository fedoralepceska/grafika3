<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class SimpleWebSocketServer extends Command
{
    protected $signature = 'websocket:simple {--port=6001} {--host=0.0.0.0}';
    protected $description = 'Start a simple WebSocket server for testing';

    public function handle()
    {
        $port = $this->option('port');
        $host = $this->option('host');
        
        $this->info("Starting simple WebSocket server on {$host}:{$port}");
        
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new class implements MessageComponentInterface {
                        protected $clients;

                        public function __construct() {
                            $this->clients = new \SplObjectStorage;
                        }

                        public function onOpen(ConnectionInterface $conn) {
                            $this->clients->attach($conn);
                            echo "New connection! ({$conn->resourceId})\n";
                            
                            // Send welcome message
                            $conn->send(json_encode([
                                'event' => 'connected',
                                'message' => 'Connected to simple WebSocket server',
                                'id' => $conn->resourceId
                            ]));
                        }

                        public function onMessage(ConnectionInterface $from, $msg) {
                            echo "Received: {$msg}\n";
                            
                            // Echo back the message
                            $from->send(json_encode([
                                'event' => 'echo',
                                'data' => $msg
                            ]));
                        }

                        public function onClose(ConnectionInterface $conn) {
                            $this->clients->detach($conn);
                            echo "Connection {$conn->resourceId} has disconnected\n";
                        }

                        public function onError(ConnectionInterface $conn, \Exception $e) {
                            echo "An error has occurred: {$e->getMessage()}\n";
                            $conn->close();
                        }
                    }
                )
            ),
            $port,
            $host
        );

        $this->info("Simple WebSocket server is running on ws://{$host}:{$port}");
        $this->info("Press Ctrl+C to stop the server");
        
        $server->run();
    }
} 