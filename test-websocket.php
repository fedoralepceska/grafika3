<?php

// Simple test script to send broadcast messages to WebSocket server
$websocketUrl = 'http://localhost:6001';

// Test data for job action started
$testData = [
    'event' => 'broadcast',
    'channel' => 'job-actions',
    'data' => [
        'event' => 'job.action.started',
        'data' => [
            'job_id' => 123,
            'action_id' => 456,
            'action_name' => 'Test Action',
            'user_id' => 1,
            'started_at' => now()->toISOString()
        ]
    ]
];

echo "Sending test broadcast message...\n";
echo "Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n";

// Send HTTP request to WebSocket server
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $websocketUrl . '/broadcast');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen(json_encode($testData))
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Response Code: $httpCode\n";
echo "Response: $response\n";

curl_close($ch); 