# WebSocket Usage Examples

## Backend Usage

### 1. Broadcasting Events

```php
// In your controller or service
use App\Events\JobStatusUpdated;

// Broadcast to all users
event(new JobStatusUpdated($jobId, $status));

// Broadcast to specific user
event(new JobStatusUpdated($jobId, $status, $userId));

// Using the broadcast service directly
use App\Services\WebSocketBroadcastService;

$broadcastService = app(WebSocketBroadcastService::class);
$broadcastService->broadcastToChannel('jobs', 'job.updated', [
    'job_id' => $jobId,
    'status' => $status
]);
```

### 2. Creating Custom Events

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return new Channel('custom-channel');
    }

    public function broadcastAs()
    {
        return 'custom.event';
    }
}
```

## Frontend Usage

### 1. Initialize WebSocket Client

```javascript
// In your main app.js or component
import { WebSocketClient } from './websocket-client.js';

// Initialize WebSocket client
const wsClient = new WebSocketClient('your-domain.com', 6001);
wsClient.connect();

// Subscribe to channels
wsClient.subscribe('jobs', (data) => {
    console.log('Job update received:', data);
    // Handle the update in your Vue component
});

// Subscribe to user-specific channel
wsClient.subscribeToUser(userId, (data) => {
    console.log('User-specific update:', data);
});
```

### 2. Vue Component Integration

```vue
<template>
    <div>
        <div v-for="job in jobs" :key="job.id">
            {{ job.name }} - {{ job.status }}
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            jobs: [],
            wsClient: null
        }
    },
    mounted() {
        this.initializeWebSocket();
    },
    methods: {
        initializeWebSocket() {
            this.wsClient = new WebSocketClient('your-domain.com', 6001);
            this.wsClient.connect();
            
            this.wsClient.subscribe('jobs', (data) => {
                if (data.event === 'job.status.updated') {
                    this.updateJobStatus(data.data);
                }
            });
        },
        updateJobStatus(data) {
            const jobIndex = this.jobs.findIndex(job => job.id === data.job_id);
            if (jobIndex !== -1) {
                this.jobs[jobIndex].status = data.status;
            }
        }
    },
    beforeUnmount() {
        if (this.wsClient) {
            this.wsClient.disconnect();
        }
    }
}
</script>
```

### 3. Real-time Job Status Updates

```javascript
// Subscribe to job status updates
wsClient.subscribe('jobs', (data) => {
    if (data.event === 'job.status.updated') {
        // Update the UI with new job status
        this.$emit('job-status-updated', data.data);
    }
});

// Subscribe to user-specific notifications
wsClient.subscribeToUser(currentUserId, (data) => {
    if (data.event === 'notification.new') {
        // Show notification to user
        this.showNotification(data.data);
    }
});
```

## Production Deployment

### 1. Environment Variables

Add these to your `.env` file:

```env
BROADCAST_DRIVER=websocket
WEBSOCKET_HOST=your-domain.com
WEBSOCKET_PORT=6001
WEBSOCKET_SCHEME=wss
```

### 2. Start WebSocket Server

```bash
# Development
php artisan websocket:serve

# Production (using systemd)
sudo systemctl enable websocket-server
sudo systemctl start websocket-server
sudo systemctl status websocket-server
```

### 3. Nginx Configuration

Add the WebSocket proxy configuration to your Nginx server block:

```nginx
# WebSocket proxy
location /ws {
    proxy_pass http://127.0.0.1:6001;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_cache_bypass $http_upgrade;
}
```

### 4. Firewall Configuration

Make sure port 6001 is open on your VPS:

```bash
sudo ufw allow 6001
```

## Testing

### 1. Test WebSocket Connection

```bash
# Test the WebSocket server
php artisan websocket:serve --port=6001

# In another terminal, test broadcasting
php artisan tinker
>>> event(new App\Events\JobStatusUpdated(1, 'completed'));
```

### 2. Browser Console Testing

```javascript
// Test WebSocket connection in browser console
const ws = new WebSocket('ws://your-domain.com:6001');
ws.onopen = () => console.log('Connected');
ws.onmessage = (event) => console.log('Message:', event.data);
``` 