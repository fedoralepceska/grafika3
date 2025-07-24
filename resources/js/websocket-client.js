class WebSocketClient {
    constructor(host = 'localhost', port = 6001) {
        this.host = host;
        this.port = port;
        this.connection = null;
        this.channels = new Map();
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000;
        this.listeners = new Map();
        this.pingInterval = null;
        this.onConnect = null;
        this.onDisconnect = null;
    }

    connect() {
        try {
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const wsUrl = `${protocol}//${this.host}:${this.port}`;
            
            console.log('Attempting to connect to:', wsUrl);
            
            this.connection = new WebSocket(wsUrl);
            
            this.connection.onopen = () => {
                console.log('WebSocket connected successfully');
                this.reconnectAttempts = 0;
                this.resubscribeToChannels();
                this.startPingInterval();
                
                // Call onConnect callback if set
                if (this.onConnect) {
                    this.onConnect();
                }
            };

            this.connection.onmessage = (event) => {
                console.log('WebSocket message received:', event.data);
                this.handleMessage(event.data);
            };

            this.connection.onclose = (event) => {
                console.log('WebSocket disconnected', {
                    code: event.code,
                    reason: event.reason,
                    wasClean: event.wasClean
                });
                
                // Call onDisconnect callback if set
                if (this.onDisconnect) {
                    this.onDisconnect();
                }
                
                this.attemptReconnect();
            };

            this.connection.onerror = (error) => {
                console.error('WebSocket error:', error);
            };
        } catch (error) {
            console.error('Failed to connect to WebSocket:', error);
        }
    }

    disconnect() {
        if (this.connection) {
            this.connection.close();
            this.connection = null;
        }
        this.stopPingInterval();
    }

    subscribe(channel, callback) {
        if (!this.connection || this.connection.readyState !== WebSocket.OPEN) {
            console.warn('WebSocket not connected, queuing subscription');
            this.channels.set(channel, callback);
            return;
        }

        this.channels.set(channel, callback);
        
        const message = {
            event: 'subscribe',
            channel: channel
        };

        this.connection.send(JSON.stringify(message));
        console.log(`Subscribed to channel: ${channel}`);
    }

    unsubscribe(channel) {
        if (!this.connection || this.connection.readyState !== WebSocket.OPEN) {
            return;
        }

        this.channels.delete(channel);
        
        const message = {
            event: 'unsubscribe',
            channel: channel
        };

        this.connection.send(JSON.stringify(message));
        console.log(`Unsubscribed from channel: ${channel}`);
    }

    resubscribeToChannels() {
        this.channels.forEach((callback, channel) => {
            this.subscribe(channel, callback);
        });
    }

    handleMessage(data) {
        try {
            const message = JSON.parse(data);
            console.log('WebSocket message received:', message);
            
            // Handle welcome message
            if (message.event === 'connected') {
                console.log('WebSocket connection confirmed:', message.message);
                return;
            }
            
            // Handle pong response
            if (message.event === 'pong') {
                console.log('Pong received from server');
                return;
            }
            
            // Handle subscription confirmation
            if (message.event === 'subscribed') {
                console.log('Successfully subscribed to channel:', message.channel);
                return;
            }
            
            // Handle broadcast messages
            if (message.channel && this.channels.has(message.channel)) {
                console.log(`Processing broadcast message for channel: ${message.channel}`, message.data);
                const callback = this.channels.get(message.channel);
                callback(message.data);
            } else if (message.channel) {
                console.log(`Received message for channel ${message.channel} but no callback registered`);
            }
        } catch (error) {
            console.error('Error parsing WebSocket message:', error);
        }
    }

    attemptReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
            
            setTimeout(() => {
                this.connect();
            }, this.reconnectDelay * this.reconnectAttempts);
        } else {
            console.error('Max reconnection attempts reached');
        }
    }

    startPingInterval() {
        this.stopPingInterval(); // Clear any existing interval
        this.pingInterval = setInterval(() => {
            if (this.connection && this.connection.readyState === WebSocket.OPEN) {
                this.connection.send(JSON.stringify({ event: 'ping' }));
            }
        }, 30000); // Send ping every 30 seconds
    }

    stopPingInterval() {
        if (this.pingInterval) {
            clearInterval(this.pingInterval);
            this.pingInterval = null;
        }
    }

    // Helper method to subscribe to user-specific channels
    subscribeToUser(userId, callback) {
        this.subscribe(`private-user.${userId}`, callback);
    }

    // Helper method to subscribe to public channels
    subscribeToPublic(channel, callback) {
        this.subscribe(`public-${channel}`, callback);
    }
}

// Export for ES6 modules
export { WebSocketClient };

// Also create a global instance for backward compatibility
window.WebSocketClient = WebSocketClient; 