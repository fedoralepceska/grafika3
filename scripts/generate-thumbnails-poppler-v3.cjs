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

async function centerThumbnailsIn30cmSquare(outputDir, files) {
    try {
        // Check if ImageMagick is available
        const isWindows = process.platform === 'win32';
        const convertCmd = isWindows ? 'magick' : 'convert';
        
        try {
            await execAsync(`${convertCmd} -version`);
        } catch (error) {
            console.log('   ⚠️ ImageMagick not available, skipping centering');
            return 0;
        }

        // 30cm at 72 DPI = 850 pixels (approximately)
        const squareSize = 850;
        let processedCount = 0;

        for (const file of files) {
            const filePath = path.join(outputDir, file);
            const tempPath = path.join(outputDir, `temp_${file}`);

            try {
                // Check file size - skip if too large (safety check)
                const stats = fs.statSync(filePath);
                if (stats.size > 10 * 1024 * 1024) { // 10MB
                    console.log(`   ⚠️ Skipping ${file} - too large (${(stats.size / 1024 / 1024).toFixed(2)}MB)`);
                    continue;
                }

                // Use ImageMagick to:
                // 1. Resize to fit within 850×850 (maintaining aspect ratio) - safety measure
                // 2. Add transparent background
                // 3. Center on 850×850 canvas
                const command = `${convertCmd} "${filePath}" -resize ${squareSize}x${squareSize} -background transparent -gravity center -extent ${squareSize}x${squareSize} "${tempPath}"`;
                
                await execAsync(command);

                // Replace original with centered version
                fs.unlinkSync(filePath);
                fs.renameSync(tempPath, filePath);

                const finalStats = fs.statSync(filePath);
                console.log(`   Page ${processedCount + 1}: ${file} → 850×850px (${finalStats.size} bytes)`);
                
                processedCount++;
            } catch (error) {
                console.log(`   ⚠️ Failed to center ${file}: ${error.message}`);
                // Clean up temp file if it exists
                if (fs.existsSync(tempPath)) {
                    fs.unlinkSync(tempPath);
                }
            }
        }

        return processedCount;
    } catch (error) {
        console.log(`   ⚠️ Error in centering process: ${error.message}`);
        return 0;
    }
}

async function getPdfInfo(pdftocairoCmd, inputPath) {
    try {
        // Use pdfinfo to get PDF dimensions (comes with poppler)
        const isWindows = process.platform === 'win32';
        const pdfinfoCmd = pdftocairoCmd.replace(isWindows ? 'pdftocairo.exe' : 'pdftocairo', 
                                                  isWindows ? 'pdfinfo.exe' : 'pdfinfo');
        
        const quotedCmd = pdfinfoCmd.includes(' ') ? `"${pdfinfoCmd}"` : pdfinfoCmd;
        const { stdout } = await execAsync(`${quotedCmd} "${inputPath}"`);
        
        // Parse page size from pdfinfo output
        // Example: "Page size:      1224 x 1584 pts (A4)"
        const pageSizeMatch = stdout.match(/Page size:\s+([\d.]+)\s+x\s+([\d.]+)\s+pts/);
        if (pageSizeMatch) {
            const widthPts = parseFloat(pageSizeMatch[1]);
            const heightPts = parseFloat(pageSizeMatch[2]);
            
            // Convert points to mm (1 point = 0.352778 mm)
            const widthMm = widthPts * 0.352778;
            const heightMm = heightPts * 0.352778;
            
            return { widthMm, heightMm, widthPts, heightPts };
        }
    } catch (error) {
        console.log(`   Could not get PDF info: ${error.message}`);
    }
    return null;
}

async function generateWithPdftocairo(inputPath, outputDir, dpi) {
    try {
        console.log('🔧 Using pdftocairo with proper color space handling');
        console.log(`   Target: 30cm × 30cm max (maintaining aspect ratio)`);

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

        // Get PDF dimensions to calculate scaling for 30cm × 30cm constraint
        const pdfInfo = await getPdfInfo(pdftocairoCmd, inputPath);
        if (pdfInfo) {
            console.log(`   PDF dimensions: ${pdfInfo.widthMm.toFixed(2)}mm × ${pdfInfo.heightMm.toFixed(2)}mm`);
            
            // Calculate scale to fit within 30cm × 30cm
            const maxDimensionCm = 30;
            const maxDimensionMm = maxDimensionCm * 10; // 300mm
            
            const scaleX = maxDimensionMm / pdfInfo.widthMm;
            const scaleY = maxDimensionMm / pdfInfo.heightMm;
            const scale = Math.min(scaleX, scaleY, 1.0); // Don't upscale, only downscale
            
            const targetWidthMm = pdfInfo.widthMm * scale;
            const targetHeightMm = pdfInfo.heightMm * scale;
            
            // At 72 DPI: 1 inch = 72 pixels, 1 mm = 2.834645669 pixels
            const targetWidthPx = Math.round(targetWidthMm * 2.834645669);
            const targetHeightPx = Math.round(targetHeightMm * 2.834645669);
            
            console.log(`   Scale factor: ${scale.toFixed(3)}`);
            console.log(`   Target thumbnail: ${targetWidthMm.toFixed(2)}mm × ${targetHeightMm.toFixed(2)}mm`);
            console.log(`   Target pixels (72 DPI): ${targetWidthPx}px × ${targetHeightPx}px`);
        }
        
        // Always use 72 DPI for consistent thumbnail sizes
        console.log(`   Using fixed DPI: ${dpi}`);

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

        // Calculate target pixel dimensions for 30cm × 30cm at 72 DPI
        let scaleToFlag = '';
        if (pdfInfo) {
            const maxDimensionMm = 300; // 30cm
            const scaleX = maxDimensionMm / pdfInfo.widthMm;
            const scaleY = maxDimensionMm / pdfInfo.heightMm;
            const scale = Math.min(scaleX, scaleY, 1.0);
            
            const targetWidthMm = pdfInfo.widthMm * scale;
            const targetHeightMm = pdfInfo.heightMm * scale;
            
            // Convert to pixels at 72 DPI
            const targetWidthPx = Math.round(targetWidthMm * 2.834645669);
            const targetHeightPx = Math.round(targetHeightMm * 2.834645669);
            
            // Use -scale-to-x and -scale-to-y to constrain dimensions
            scaleToFlag = `-scale-to-x ${targetWidthPx} -scale-to-y ${targetHeightPx}`;
        }

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

                // Build command with scaling
                let command;
                if (scaleToFlag) {
                    command = method.flag
                        ? `${quotedCmd} -png ${method.flag} ${scaleToFlag} "${inputPath}" "${outputPrefix}"`
                        : `${quotedCmd} -png ${scaleToFlag} "${inputPath}" "${outputPrefix}"`;
                } else {
                    // Fallback: use very low DPI for safety (in case of huge PDFs)
                    const safeDpi = 36; // Lower DPI to prevent huge thumbnails
                    console.log(`   ⚠️ Using fallback DPI: ${safeDpi} (PDF info not available)`);
                    command = method.flag
                        ? `${quotedCmd} -png ${method.flag} -r ${safeDpi} "${inputPath}" "${outputPrefix}"`
                        : `${quotedCmd} -png -r ${safeDpi} "${inputPath}" "${outputPrefix}"`;
                }

                console.log(`   Executing: ${command}`);

                const { stdout, stderr } = await execAsync(command);

                if (stderr && !stderr.includes('Warning')) {
                    console.log('   pdftocairo stderr:', stderr);
                }

                // Check if files were created
                const files = fs.readdirSync(outputDir).filter(f => f.startsWith('thumb') && f.endsWith('.png'));

                if (files.length > 0) {
                    console.log(`✅ Success with ${method.name}: ${files.length} pages generated`);

                    // Post-process to center in 30cm × 30cm square
                    const processedCount = await centerThumbnailsIn30cmSquare(outputDir, files);
                    
                    if (processedCount > 0) {
                        console.log(`✅ Centered ${processedCount} thumbnails in 30cm × 30cm square`);
                    }

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
