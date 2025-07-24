<!DOCTYPE html>
<html>
<head>
    <title>WebSocket Test</title>
</head>
<body>
    <h1>WebSocket Connection Test</h1>
    <div id="status">Connecting...</div>
    <div id="messages"></div>
    
    <script>
        const statusDiv = document.getElementById('status');
        const messagesDiv = document.getElementById('messages');
        
        function addMessage(message) {
            const div = document.createElement('div');
            div.textContent = new Date().toLocaleTimeString() + ': ' + message;
            messagesDiv.appendChild(div);
        }
        
        try {
            const ws = new WebSocket('ws://127.0.0.1:6001');
            
            ws.onopen = function() {
                statusDiv.textContent = 'Connected!';
                statusDiv.style.color = 'green';
                addMessage('WebSocket connected');
                
                // Subscribe to channels
                ws.send(JSON.stringify({event: 'subscribe', channel: 'test'}));
                addMessage('Subscribed to test channel');
            };
            
            ws.onmessage = function(event) {
                addMessage('Received: ' + event.data);
            };
            
            ws.onclose = function(event) {
                statusDiv.textContent = 'Disconnected (Code: ' + event.code + ')';
                statusDiv.style.color = 'red';
                addMessage('WebSocket disconnected: ' + event.code);
            };
            
            ws.onerror = function(error) {
                statusDiv.textContent = 'Error!';
                statusDiv.style.color = 'red';
                addMessage('WebSocket error: ' + error);
            };
            
        } catch (error) {
            statusDiv.textContent = 'Failed to connect';
            statusDiv.style.color = 'red';
            addMessage('Error: ' + error.message);
        }
    </script>
</body>
</html> 