# Production Setup Guide: PDF Thumbnail Generation on Ubuntu VPS

## Overview

Based on our debugging, the **pdf-poppler** (Node.js) approach is the most reliable solution. However, for production redundancy, we'll set up both pdf-poppler and Poppler tools as fallbacks.

## 1. Install Node.js and npm

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install Node.js (using NodeSource repository for latest LTS)
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
sudo apt-get install -y nodejs

# Verify installation
node --version
npm --version
```

## 2. Install Poppler Tools (Fallback Option)

```bash
# Install Poppler utilities
sudo apt install -y poppler-utils

# Verify installation
pdftocairo -v
pdftoppm -v
pdfinfo -v

# Check installation paths
which pdftocairo
which pdftoppm
# Usually installed to: /usr/bin/
```

## 3. Install ImageMagick (Additional Fallback)

```bash
# Install ImageMagick with PDF support
sudo apt install -y imagemagick

# Configure ImageMagick policy for PDF processing
sudo nano /etc/ImageMagick-6/policy.xml

# Find and modify these lines (or add if missing):
# Change from:
#   <policy domain="coder" rights="none" pattern="PDF" />
# To:
#   <policy domain="coder" rights="read|write" pattern="PDF" />

# Verify ImageMagick can process PDFs
convert --version
```

## 4. Install Node.js Dependencies

In your Laravel project directory:

```bash
# Install pdf-poppler and other Node.js dependencies
npm install

# Verify pdf-poppler is installed
npm list pdf-poppler

# Test Node.js script manually
node scripts/generate-thumbnails-poppler.cjs --help
```

## 5. Configure Environment Variables

Edit your production `.env` file:

```bash
# Edit environment file
nano .env
```

Add/update these variables:

```env
# Node.js path (usually auto-detected, but specify if needed)
NODE_PATH=/usr/bin/node

# Poppler tools path (fallback option)
POPPLER_BIN=/usr/bin

# ImageMagick path (additional fallback)
IMAGEMAGICK_BIN=/usr/bin

# PDF processing configuration
PDF_CONVERTER_DRIVER=pdf-poppler
PDF_CONVERTER_STRICT=false

# Queue configuration (ensure it's sync for immediate processing)
QUEUE_CONNECTION=sync
```

## 6. Update Laravel Configuration

Create or update `config/services.php`:

```php
<?php

return [
    // ... existing config

    'poppler_bin' => env('POPPLER_BIN', '/usr/bin'),
    'imagemagick_bin' => env('IMAGEMAGICK_BIN', '/usr/bin'),
    'node_path' => env('NODE_PATH', '/usr/bin/node'),
    
    'pdf_processing' => [
        'primary_method' => 'pdf-poppler',
        'fallback_methods' => ['imagemagick', 'poppler'],
        'timeout' => 300, // 5 minutes
    ],
];
```

## 7. Test the Setup

Create a test script to verify everything works:

```bash
# Create test script
nano test-production-setup.php
```

```php
<?php
// test-production-setup.php

echo "=== Production PDF Processing Test ===\n";

// Test 1: Check Node.js
echo "\n1. Testing Node.js...\n";
$nodeVersion = shell_exec('node --version 2>&1');
echo "Node.js version: " . trim($nodeVersion) . "\n";

// Test 2: Check npm packages
echo "\n2. Testing npm packages...\n";
$pdfPopplerCheck = shell_exec('npm list pdf-poppler 2>&1');
echo "pdf-poppler status: " . (strpos($pdfPopplerCheck, 'pdf-poppler@') !== false ? 'INSTALLED' : 'MISSING') . "\n";

// Test 3: Check Poppler tools
echo "\n3. Testing Poppler tools...\n";
$popplerTools = ['pdftocairo', 'pdftoppm', 'pdfinfo'];
foreach ($popplerTools as $tool) {
    $path = shell_exec("which $tool 2>&1");
    echo "$tool: " . (trim($path) ? trim($path) : 'NOT FOUND') . "\n";
}

// Test 4: Check ImageMagick
echo "\n4. Testing ImageMagick...\n";
$imagickPath = shell_exec('which convert 2>&1');
echo "ImageMagick convert: " . (trim($imagickPath) ? trim($imagickPath) : 'NOT FOUND') . "\n";

// Test 5: Check PHP extensions
echo "\n5. Testing PHP extensions...\n";
echo "Imagick extension: " . (extension_loaded('imagick') ? 'LOADED' : 'NOT LOADED') . "\n";

// Test 6: Check file permissions
echo "\n6. Testing file permissions...\n";
$tempDir = 'storage/app/temp';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}
echo "Temp directory writable: " . (is_writable($tempDir) ? 'YES' : 'NO') . "\n";

echo "\n=== Test Complete ===\n";
```

Run the test:

```bash
php test-production-setup.php
```

## 8. Production Optimization

### A. Set up proper file permissions:

```bash
# Set correct ownership
sudo chown -R www-data:www-data storage/
sudo chown -R www-data:www-data bootstrap/cache/

# Set correct permissions
sudo chmod -R 755 storage/
sudo chmod -R 755 bootstrap/cache/

# Make scripts executable
chmod +x scripts/*.cjs
chmod +x scripts/*.js
```

### B. Configure log rotation for PDF processing:

```bash
# Create logrotate config
sudo nano /etc/logrotate.d/laravel-pdf

# Add content:
/path/to/your/laravel/storage/logs/laravel.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 0644 www-data www-data
    postrotate
        /usr/bin/supervisorctl restart laravel-worker: > /dev/null 2>&1 || true
    endscript
}
```

### C. Set up monitoring:

```bash
# Create monitoring script
nano monitor-pdf-processing.sh
```

```bash
#!/bin/bash
# monitor-pdf-processing.sh

LOG_FILE="/var/log/pdf-processing-monitor.log"
TEMP_DIR="/path/to/your/laravel/storage/app/temp"

# Check temp directory size
TEMP_SIZE=$(du -sh $TEMP_DIR 2>/dev/null | cut -f1)
echo "$(date): Temp directory size: $TEMP_SIZE" >> $LOG_FILE

# Clean up old temp files (older than 1 hour)
find $TEMP_DIR -name "*.pdf" -mmin +60 -delete 2>/dev/null
find $TEMP_DIR -name "*.png" -mmin +60 -delete 2>/dev/null
find $TEMP_DIR -type d -empty -delete 2>/dev/null

# Check if Node.js is responsive
if ! node --version > /dev/null 2>&1; then
    echo "$(date): ERROR - Node.js not responding" >> $LOG_FILE
fi

# Check if pdf-poppler is working
if ! npm list pdf-poppler > /dev/null 2>&1; then
    echo "$(date): ERROR - pdf-poppler not available" >> $LOG_FILE
fi
```

```bash
# Make executable and add to cron
chmod +x monitor-pdf-processing.sh

# Add to crontab (run every 30 minutes)
crontab -e
# Add line:
# */30 * * * * /path/to/monitor-pdf-processing.sh
```

## 9. Deployment Checklist

Before deploying to production:

- [ ] Node.js and npm installed
- [ ] pdf-poppler package installed via npm
- [ ] Poppler tools installed and accessible
- [ ] ImageMagick installed and configured
- [ ] Environment variables set correctly
- [ ] File permissions configured
- [ ] Test script passes all checks
- [ ] Monitoring and cleanup scripts in place
- [ ] Log rotation configured

## 10. Troubleshooting Common Issues

### Issue: "pdf-poppler not found"
```bash
# Reinstall Node.js dependencies
npm install
npm audit fix
```

### Issue: "Permission denied" errors
```bash
# Fix ownership and permissions
sudo chown -R www-data:www-data storage/
sudo chmod -R 755 storage/
```

### Issue: "ImageMagick policy" errors
```bash
# Edit ImageMagick policy
sudo nano /etc/ImageMagick-6/policy.xml
# Ensure PDF coder has read|write rights
```

### Issue: Node.js path not found
```bash
# Find Node.js path
which node
# Update .env with correct NODE_PATH
```

## 11. Performance Monitoring

Add this to your Laravel application for monitoring:

```php
// In a service provider or middleware
Log::info('PDF processing stats', [
    'method' => 'pdf-poppler',
    'file_size' => $fileSize,
    'processing_time' => $processingTime,
    'memory_usage' => memory_get_peak_usage(true),
    'temp_files_created' => $tempFileCount,
]);
```

## 12. Architecture Overview

### Processing Flow:
1. **Primary Method**: pdf-poppler (Node.js library)
   - Pure JavaScript implementation
   - No external process spawning issues
   - Reliable cross-platform compatibility

2. **Fallback Method 1**: ImageMagick
   - Handles corrupted or complex PDFs
   - Good for edge cases

3. **Fallback Method 2**: Poppler Tools
   - Last resort for maximum compatibility
   - Direct CLI tools (pdftocairo, pdftoppm)

### File Structure:
```
project/
├── scripts/
│   ├── generate-thumbnails-poppler.cjs  # Primary method
│   ├── generate-thumbnails-cli.cjs      # CLI fallback
│   └── scale-pdf.js                     # PDF scaling (if needed)
├── storage/app/temp/                    # Temporary files
├── app/Jobs/GeneratePdfThumbnails.php   # Main job class
└── PDF_THUMBNAIL_SETUP.md              # This guide
```

## 13. Security Considerations

### File Upload Security:
```php
// Validate PDF files before processing
$allowedMimeTypes = ['application/pdf'];
$maxFileSize = 100 * 1024 * 1024; // 100MB

// Sanitize file names
$sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName);
```

### Temp File Security:
```bash
# Set secure temp directory permissions
chmod 700 storage/app/temp/
chown www-data:www-data storage/app/temp/

# Add to .gitignore
echo "storage/app/temp/*" >> .gitignore
```

## 14. Performance Tuning

### PHP Configuration:
```ini
; php.ini optimizations for PDF processing
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 100M
post_max_size = 100M
```

### Node.js Optimization:
```bash
# Increase Node.js memory limit for large PDFs
export NODE_OPTIONS="--max-old-space-size=4096"
```

This setup provides a robust, production-ready PDF thumbnail generation system with multiple fallback options and proper monitoring.