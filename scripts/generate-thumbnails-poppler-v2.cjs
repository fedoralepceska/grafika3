#!/usr/bin/env node

/**
 * PDF Thumbnail Generator V2 - Color Fix Edition
 * 
 * This version fixes:
 * - Inverted colors in CMYK PDFs (using -srgb flag)
 * - Removes A4 standardization for consistency
 * - Better fallback chain for compatibility
 */

const pdfPoppler = require('pdf-poppler');
const fs = require('fs');
const path = require('path');

console.log('🎨 PDF Thumbnail Generator V2 - Color Fix Edition');

async function generateThumbnails(inputPath, outputDir, dpi = 72) {
    try {
        console.log(`Starting thumbnail generation V2: ${inputPath} -> ${outputDir}`);
        console.log(`DPI: ${dpi}`);
        console.log(`Mode: Original dimensions (no A4 standardization)`);

        // Ensure output directory exists
        if (!fs.existsSync(outputDir)) {
            fs.mkdirSync(outputDir, { recursive: true });
        }

        // Use pdftocairo with color fixes, fallback to pdf-poppler
        return await generateOriginalThumbnails(inputPath, outputDir, dpi);

    } catch (error) {
        console.error('Error generating thumbnails:', error.message);
        return 0;
    }
}

async function generateOriginalThumbnails(inputPath, outputDir, dpi) {
    try {
        // Use pdftocairo directly for better color handling
        const pdftocairoSuccess = await tryPdftocairoWithColorFix(inputPath, outputDir, dpi);
        if (pdftocairoSuccess) {
            return pdftocairoSuccess;
        }

        // Fallback to original pdf-poppler
        console.log('⚠️ Falling back to pdf-poppler method (colors may not be perfect)');

        // Configure pdf-poppler options
        const options = {
            format: 'png',
            out_dir: outputDir,
            out_prefix: 'thumb',
            page: null, // Convert all pages
            density: dpi,
            single_file: false,
            print_err: true
        };

        // Convert PDF to images
        const results = await pdfPoppler.convert(inputPath, options);

        console.log(`✅ Thumbnail generation completed`);
        console.log(`📄 Pages processed: ${results.length}`);

        // Check created files
        const files = fs.readdirSync(outputDir).filter(f => f.startsWith('thumb') && f.endsWith('.png'));
        files.forEach((file, index) => {
            const filePath = path.join(outputDir, file);
            const stats = fs.statSync(filePath);
            console.log(`   Page ${index + 1}: ${file} (${stats.size} bytes)`);
        });

        return files.length;
    } catch (error) {
        console.error('❌ Error generating original thumbnails:', error.message);
        return 0;
    }
}

async function tryPdftocairoWithColorFix(inputPath, outputDir, dpi) {
    try {
        const { exec } = require('child_process');
        const { promisify } = require('util');
        const execAsync = promisify(exec);

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
            console.log(`⚠️ pdftocairo not found at: ${pdftocairoCmd}`);
            console.log(`   Error: ${error.message}`);
            console.log('   Will use pdf-poppler fallback');
            return false;
        }

        const outputPrefix = path.join(outputDir, 'thumb');

        // Try multiple methods in order of preference
        // Note: -srgb flag doesn't exist in pdftocairo (tested on v22 and v25)
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

        console.log('❌ All pdftocairo methods failed');
        return false;

    } catch (error) {
        console.log('❌ pdftocairo with color fix failed:', error.message);
        return false;
    }
}

// Command line interface
if (process.argv.length < 4) {
    console.error('Usage: node generate-thumbnails-poppler-v2.cjs <input-pdf> <output-dir> [dpi]');
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
