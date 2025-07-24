#!/bin/bash

# WebSocket Server Deployment Script for Hostinger VPS
# Run this script on your VPS to set up the WebSocket server

echo "Starting WebSocket server deployment..."

# Update system packages
echo "Updating system packages..."
sudo apt update && sudo apt upgrade -y

# Install required packages
echo "Installing required packages..."
sudo apt install -y php8.1-cli php8.1-common php8.1-mysql php8.1-zip php8.1-gd php8.1-mbstring php8.1-curl php8.1-xml php8.1-bcmath

# Set up firewall
echo "Configuring firewall..."
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443
sudo ufw allow 6001
sudo ufw --force enable

# Create systemd service file
echo "Creating systemd service..."
sudo tee /etc/systemd/system/websocket-server.service > /dev/null <<EOF
[Unit]
Description=Laravel WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/home/username/public_html
ExecStart=/usr/bin/php artisan websocket:serve --port=6001 --host=0.0.0.0
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal
SyslogIdentifier=laravel-websocket

[Install]
WantedBy=multi-user.target
EOF

# Reload systemd and enable service
echo "Enabling WebSocket service..."
sudo systemctl daemon-reload
sudo systemctl enable websocket-server
sudo systemctl start websocket-server

# Check service status
echo "Checking service status..."
sudo systemctl status websocket-server

# Create log directory
echo "Setting up logging..."
sudo mkdir -p /var/log/websocket
sudo chown www-data:www-data /var/log/websocket

# Update Nginx configuration (if using Nginx)
echo "Updating Nginx configuration..."
sudo tee /etc/nginx/sites-available/websocket-proxy > /dev/null <<EOF
# WebSocket proxy configuration
location /ws {
    proxy_pass http://127.0.0.1:6001;
    proxy_http_version 1.1;
    proxy_set_header Upgrade \$http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host \$host;
    proxy_set_header X-Real-IP \$remote_addr;
    proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto \$scheme;
    proxy_cache_bypass \$http_upgrade;
}

location /broadcast {
    proxy_pass http://127.0.0.1:6001;
    proxy_http_version 1.1;
    proxy_set_header Host \$host;
    proxy_set_header X-Real-IP \$remote_addr;
    proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto \$scheme;
}
EOF

# Test WebSocket connection
echo "Testing WebSocket connection..."
curl -I http://localhost:6001

echo "Deployment completed!"
echo "WebSocket server should be running on port 6001"
echo "Check status with: sudo systemctl status websocket-server"
echo "View logs with: sudo journalctl -u websocket-server -f" 