<template>
    <div class="p-8">
        <h1 class="text-2xl font-bold mb-4">WebSocket Test Page</h1>
        
        <!-- Connection Status -->
        <div class="mb-4">
            <div class="flex items-center gap-2">
                <div 
                    :class="[
                        'w-4 h-4 rounded-full',
                        wsConnected ? 'bg-green-500' : 'bg-red-500'
                    ]"
                ></div>
                <span class="font-medium">
                    {{ wsConnected ? 'Connected' : 'Disconnected' }}
                </span>
            </div>
        </div>

        <!-- Test Buttons -->
        <div class="mb-6 space-y-2">
            <button 
                @click="testBroadcast"
                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
            >
                Test Broadcast
            </button>
            <button 
                @click="testJobActionStarted"
                class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
            >
                Test Job Action Started
            </button>
        </div>

        <!-- Messages Log -->
        <div class="bg-gray-100 p-4 rounded">
            <h2 class="font-bold mb-2">Received Messages:</h2>
            <div class="space-y-1 max-h-96 overflow-y-auto">
                <div 
                    v-for="(message, index) in messages" 
                    :key="index"
                    class="text-sm bg-white p-2 rounded border"
                >
                    <div class="font-medium">{{ message.timestamp }}</div>
                    <div class="text-gray-600">{{ message.event }}</div>
                    <pre class="text-xs mt-1">{{ JSON.stringify(message.data, null, 2) }}</pre>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { WebSocketClient } from '../websocket-client.js';

export default {
    name: 'WebSocketTest',
    data() {
        return {
            wsClient: null,
            wsConnected: false,
            messages: []
        };
    },
    mounted() {
        this.initializeWebSocket();
    },
    beforeUnmount() {
        if (this.wsClient) {
            this.wsClient.disconnect();
        }
    },
    methods: {
        initializeWebSocket() {
            try {
                const host = window.location.hostname;
                const port = 6001;
                
                this.wsClient = new WebSocketClient(host, port);
                
                this.wsClient.onConnect = () => {
                    this.wsConnected = true;
                    this.addMessage('connected', { message: 'WebSocket connected' });
                };
                
                this.wsClient.onDisconnect = () => {
                    this.wsConnected = false;
                    this.addMessage('disconnected', { message: 'WebSocket disconnected' });
                };
                
                this.wsClient.connect();
                
                // Subscribe to job-actions channel
                this.wsClient.subscribe('job-actions', (data) => {
                    this.addMessage('job-actions', data);
                });
                
                // Subscribe to user-specific channel
                if (window.userId) {
                    this.wsClient.subscribeToUser(window.userId, (data) => {
                        this.addMessage(`private-user.${window.userId}`, data);
                    });
                }
                
                console.log('WebSocket test client initialized');
            } catch (error) {
                console.error('Failed to initialize WebSocket test client:', error);
            }
        },
        
        addMessage(event, data) {
            this.messages.unshift({
                timestamp: new Date().toLocaleTimeString(),
                event: event,
                data: data
            });
            
            // Keep only last 50 messages
            if (this.messages.length > 50) {
                this.messages = this.messages.slice(0, 50);
            }
        },
        
        async testBroadcast() {
            try {
                const response = await fetch('/test-broadcast', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const result = await response.json();
                console.log('Test broadcast result:', result);
                this.addMessage('test-broadcast-response', result);
            } catch (error) {
                console.error('Test broadcast error:', error);
                this.addMessage('test-broadcast-error', { error: error.message });
            }
        },
        
        async testJobActionStarted() {
            try {
                const response = await fetch('/test-job-action-started', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const result = await response.json();
                console.log('Test job action started result:', result);
                this.addMessage('test-job-action-started-response', result);
            } catch (error) {
                console.error('Test job action started error:', error);
                this.addMessage('test-job-action-started-error', { error: error.message });
            }
        }
    }
};
</script> 