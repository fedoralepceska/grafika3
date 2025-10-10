#!/usr/bin/env node

/**
 * PDF Thumbnail Generator V3 - Pure pdftocairo Edition
 * 
 * This version:
 * - Uses ONLY pdftocairo (no pdf-poppler dependency)
 * - Fixes inverted colors in CMYK PDFs
 * - No A4 standardization
 * - No Canvas dependency
 */

const fs = require('fs');
const path = require('path');
const { exec } = require('child_process');
const { promisify } = require('util');
const execAsync = promisify(exec);

console.log('🎨 PDF Thumbnail Generator V3 - Pure pdftocairo Edition');

async function generateThumbnails(inputPath, outputDir, dpi = 72) {
    try {
        console.log(`Starting thumbnail generation V3: ${inputPath} -> ${outputDir}`);
        console.log(`DPI: ${dpi}`);
        console.log(`Mode: Original dimensions (no A4 standardization)`);

        // Ensure output directory exists
        if (!fs.existsSync(outputDir)) {
            fs.mkdirSync(outputDir, { recursive: true });
        }

        // Use pdftocairo directly
        return await generateWithPdftocairo(inputPath, outputDir, dpi);

    } catch (error) {
        console.error('Error generating thumbnails:', error.message);
        return 0;
    }
}

async function generateWithPdftocairo(inputPath, outputDir, dpi) {
    try {
        console.log('🔧 Using pdftocairo with proper color space handling');

        // Try to find pdftocairo - respect POPPLER_BIN environment variable
        const isWindows = process.platform === 'win32';
        const popplerBin = process.env.POPPLER_BIN;
        
        let pdftocairoCmd;
        if (popplerBin) {
            // Use the specified poppler bin directory
            pdftocairoCmd = path.join(popplerBin, isWindows ? 'pdftocairo.exe' : 'pdftocairo');
            console.log(`   Using POPPLER_BIN: ${popplerBin}`);
        } else {
            // Use system PATH
            pdftocairoCmd = isWindows ? 'pdftocairo.exe' : 'pdftocairo';
            console.log(`   Using system PATH for pdftocairo`);
        }

        // Check if pdftocairo exists
        let popplerVersion = null;
        try {
            // Quote the command path for Windows paths with spaces
            const quotedCmd = pdftocairoCmd.includes(' ') ? `"${pdftocairoCmd}"` : pdftocairoCmd;
            const { stdout } = await execAsync(`${quotedCmd} -v`);
            const versionMatch = stdout.match(/pdftocairo version (\d+\.\d+)/);
            if (versionMatch) {
                popplerVersion = parseFloat(versionMatch[1]);
                console.log(`   Detected pdftocairo version: ${popplerVersion}`);
            }
        } catch (error) {
            console.error(`❌ pdftocairo not found at: ${pdftocairoCmd}`);
            console.error(`   Error: ${error.message}`);
            console.error(`   Cannot generate thumbnails without pdftocairo`);
            return 0;
        }

        const outputPrefix = path.join(outputDir, 'thumb');

        // Try multiple methods in order of preference
        const methods = [
            // Method 1: Transparent background (helps with color space conversion)
            { flag: '-transp', name: 'transparent background' },
            // Method 2: Grayscale (fallback for problematic color spaces)
            { flag: '-gray', name: 'grayscale' },
            // Method 3: Simple mode (most compatible)
            { flag: '', name: 'simple mode' }
        ];

        // Quote the command path for Windows paths with spaces
        const quotedCmd = pdftocairoCmd.includes(' ') ? `"${pdftocairoCmd}"` : pdftocairoCmd;

        for (const method of methods) {
            try {
                console.log(`   Trying ${method.name}...`);

                const command = method.flag
                    ? `${quotedCmd} -png ${method.flag} -r ${dpi} "${inputPath}" "${outputPrefix}"`
                    : `${quotedCmd} -png -r ${dpi} "${inputPath}" "${outputPrefix}"`;

                console.log(`   Executing: ${command}`);

                const { stdout, stderr } = await execAsync(command);

                if (stderr && !stderr.includes('Warning')) {
                    console.log('   pdftocairo stderr:', stderr);
                }

                // Check if files were created
                const files = fs.readdirSync(outputDir).filter(f => f.startsWith('thumb') && f.endsWith('.png'));

                if (files.length > 0) {
                    console.log(`✅ Success with ${method.name}: ${files.length} pages generated`);

                    files.forEach((file, index) => {
                        const filePath = path.join(outputDir, file);
                        const stats = fs.statSync(filePath);
                        console.log(`   Page ${index + 1}: ${file} (${stats.size} bytes)`);
                    });

                    return files.length;
                }
            } catch (error) {
                console.log(`   ❌ ${method.name} failed:`, error.message);
                // Clean up any partial files
                const partialFiles = fs.readdirSync(outputDir).filter(f => f.startsWith('thumb'));
                partialFiles.forEach(f => fs.unlinkSync(path.join(outputDir, f)));
            }
        }

        console.error('❌ All pdftocairo methods failed');
        return 0;

    } catch (error) {
        console.error('❌ pdftocairo generation failed:', error.message);
        return 0;
    }
}

// Command line interface
if (process.argv.length < 4) {
    console.error('Usage: node generate-thumbnails-poppler-v3.cjs <input-pdf> <output-dir> [dpi]');
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
        console.log(`\n✅ Successfully generated ${pageCount} thumbnails`);
        process.exit(pageCount > 0 ? 0 : 1);
    })
    .catch(error => {
        console.error('\n❌ Unexpected error:', error);
        process.exit(1);
    });
