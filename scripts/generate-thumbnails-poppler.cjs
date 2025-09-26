#!/usr/bin/env node

const pdfPoppler = require('pdf-poppler');
const fs = require('fs');
const path = require('path');

async function generateThumbnails(inputPath, outputDir, dpi = 36) {
    try {
        console.log(`Starting thumbnail generation: ${inputPath} -> ${outputDir}`);
        console.log(`DPI: ${dpi}`);
        
        // Ensure output directory exists
        if (!fs.existsSync(outputDir)) {
            fs.mkdirSync(outputDir, { recursive: true });
        }
        
        // Configure pdf-poppler options
        const options = {
            format: 'png',
            out_dir: outputDir,
            out_prefix: 'thumb',
            page: null, // Convert all pages
            density: dpi
        };
        
        // Convert PDF to images
        const results = await pdfPoppler.convert(inputPath, options);
        
        console.log(`Thumbnail generation completed successfully`);
        console.log(`Pages processed: ${results.length}`);
        
        // Check created files
        const files = fs.readdirSync(outputDir).filter(f => f.startsWith('thumb') && f.endsWith('.png'));
        files.forEach((file, index) => {
            const filePath = path.join(outputDir, file);
            const stats = fs.statSync(filePath);
            console.log(`Page ${index + 1}: ${file} (${stats.size} bytes)`);
        });
        
        return files.length;
    } catch (error) {
        console.error('Error generating thumbnails:', error.message);
        return 0;
    }
}

// Command line interface
if (process.argv.length < 4) {
    console.error('Usage: node generate-thumbnails-poppler.cjs <input-pdf> <output-dir> [dpi]');
    process.exit(1);
}

const inputPath = process.argv[2];
const outputDir = process.argv[3];
const dpi = process.argv[4] ? parseInt(process.argv[4]) : 36;

if (!fs.existsSync(inputPath)) {
    console.error(`Input file does not exist: ${inputPath}`);
    process.exit(1);
}

generateThumbnails(inputPath, outputDir, dpi)
    .then(pageCount => {
        console.log(`Successfully generated ${pageCount} thumbnails`);
        process.exit(pageCount > 0 ? 0 : 1);
    })
    .catch(error => {
        console.error('Unexpected error:', error);
        process.exit(1);
    });