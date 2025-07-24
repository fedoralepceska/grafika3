<?php

// Simple HTTP server to handle broadcast messages
require_once __DIR__ . '/vendor/autoload.php';

use App\WebSocket\WebSocketHandler;

// Create WebSocket handler instance
$webSocketHandler = new WebSocketHandler();

// Handle HTTP requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] === '/broadcast') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if ($data && isset($data['channel']) && isset($data['data'])) {
        // Broadcast the message
        $webSocketHandler->broadcastToChannel($data['channel'], $data['data']);
        
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid broadcast data']);
    }
} else {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not Found']);
} 