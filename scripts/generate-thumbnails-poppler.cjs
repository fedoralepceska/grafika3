#!/usr/bin/env node

const pdfPoppler = require('pdf-poppler');
const fs = require('fs');
const path = require('path');

// Try to load canvas for A4 standardization, fallback gracefully if not available
let createCanvas, loadImage;
try {
    const canvas = require('canvas');
    createCanvas = canvas.createCanvas;
    loadImage = canvas.loadImage;
} catch (error) {
    console.log('Canvas module not available, using original thumbnail generation');
}

// A4 dimensions at 72 DPI
const A4_WIDTH_PX = 595;  // 210mm at 72 DPI
const A4_HEIGHT_PX = 842; // 297mm at 72 DPI

// Function to try different color space conversion methods
async function tryAlternativeColorConversion(inputPath, outputDir, dpi) {
    const { exec } = require('child_process');
    const { promisify } = require('util');
    const execAsync = promisify(exec);

    console.log('Trying alternative color conversion methods...');

    // Method 1: Try version-specific approach
    try {
        const outputPrefix = path.join(outputDir, 'thumb');
        
        // Get pdftocairo version
        let useSimpleMode = false;
        try {
            const { stdout } = await execAsync('pdftocairo -v');
            const versionMatch = stdout.match(/pdftocairo version (\d+\.\d+)/);
            if (versionMatch && parseFloat(versionMatch[1]) < 23.0) {
                useSimpleMode = true;
                console.log('Using simple mode for older pdftocairo version');
            }
        } catch (e) {
            useSimpleMode = true; // Default to simple mode if version check fails
        }
        
        const command = useSimpleMode 
            ? `pdftocairo -png -r ${dpi} "${inputPath}" "${outputPrefix}"`
            : `pdftocairo -png -r ${dpi} -icc "${inputPath}" "${outputPrefix}"`;
            
        await execAsync(command);

        const files = fs.readdirSync(outputDir).filter(f => f.startsWith('thumb') && f.endsWith('.png'));
        if (files.length > 0) {
            console.log(`Success with ${useSimpleMode ? 'simple' : 'ICC'} color method`);
            return files.length;
        }
    } catch (error) {
        console.log('Version-specific method failed, trying next...');
    }

    // Method 2: Try with different color space flags
    try {
        const outputPrefix = path.join(outputDir, 'thumb');
        const command = `pdftocairo -png -r ${dpi} -gray "${inputPath}" "${outputPrefix}"`;
        await execAsync(command);

        const files = fs.readdirSync(outputDir).filter(f => f.startsWith('thumb') && f.endsWith('.png'));
        if (files.length > 0) {
            console.log('Success with grayscale conversion (fallback)');
            return files.length;
        }
    } catch (error) {
        console.log('Grayscale method failed');
    }

    return false;
}

async function generateThumbnails(inputPath, outputDir, dpi = 72) {
    try {
        console.log(`Starting thumbnail generation: ${inputPath} -> ${outputDir}`);
        console.log(`DPI: ${dpi}`);
        console.log(`Target format: ${createCanvas ? 'A4 standardized with canvas (595x842px)' : 'A4 standardized with pdftocairo or original dimensions'}`);

        // Ensure output directory exists
        if (!fs.existsSync(outputDir)) {
            fs.mkdirSync(outputDir, { recursive: true });
        }

        // TESTING: Temporarily disable Canvas to isolate issues
        console.log('🧪 Testing without Canvas - using direct pdf-poppler');
        return await generateOriginalThumbnails(inputPath, outputDir, dpi);
        
        // If canvas is available, use canvas-based A4 standardization (best quality)
        // if (createCanvas && loadImage) {
        //     return await generateA4StandardizedThumbnails(inputPath, outputDir, dpi);
        // } else {
        //     // Try A4 standardization without canvas (using pdftocairo or fallback to original)
        //     return await generateOriginalThumbnails(inputPath, outputDir, dpi);
        // }

    } catch (error) {
        console.error('Error generating thumbnails:', error.message);
        return 0;
    }
}

async function generateA4StandardizedThumbnails(inputPath, outputDir, baseDpi) {
    try {
        // First, get PDF info to determine optimal DPI
        let optimalDpi = baseDpi;

        // Try to get first page dimensions to calculate optimal DPI
        try {
            // Generate a single page at base DPI to check dimensions
            const tempOptions = {
                format: 'png',
                out_dir: outputDir,
                out_prefix: 'temp_check',
                page: 1,
                density: baseDpi
            };

            await pdfPoppler.convert(inputPath, tempOptions);

            // Check the generated temp file to get actual dimensions
            const tempFiles = fs.readdirSync(outputDir).filter(f => f.startsWith('temp_check'));
            if (tempFiles.length > 0) {
                const tempPath = path.join(outputDir, tempFiles[0]);
                const tempImage = await loadImage(tempPath);

                // Calculate if we need to adjust DPI for very large pages
                const pageArea = tempImage.width * tempImage.height;

                // If page is extremely large (> 4M pixels), reduce DPI
                if (pageArea > 4000000) {
                    optimalDpi = Math.max(36, Math.floor(baseDpi * 0.5));
                    console.log(`Large page detected (${tempImage.width}x${tempImage.height}), reducing DPI to ${optimalDpi}`);
                } else if (pageArea > 2000000) {
                    optimalDpi = Math.max(50, Math.floor(baseDpi * 0.7));
                    console.log(`Medium-large page detected (${tempImage.width}x${tempImage.height}), reducing DPI to ${optimalDpi}`);
                }

                // Clean up temp file
                fs.unlinkSync(tempPath);
            }
        } catch (error) {
            console.log('Could not determine page size, using base DPI:', baseDpi);
        }

        // Configure pdf-poppler options with optimal DPI and color fixes
        const options = {
            format: 'png',
            out_dir: outputDir,
            out_prefix: 'thumb_raw',
            page: null, // Convert all pages
            density: optimalDpi,
            // Add color space handling
            single_file: false,
            print_err: true
        };

        // Convert PDF to images
        const results = await pdfPoppler.convert(inputPath, options);
        console.log(`Raw thumbnails generated: ${results.length} pages at ${optimalDpi} DPI`);

        // Process each generated thumbnail to A4 standard format
        const rawFiles = fs.readdirSync(outputDir)
            .filter(f => f.startsWith('thumb_raw') && f.endsWith('.png'))
            .sort((a, b) => {
                const aNum = parseInt(a.match(/\d+/)?.[0] || '0');
                const bNum = parseInt(b.match(/\d+/)?.[0] || '0');
                return aNum - bNum;
            });

        let processedCount = 0;

        for (let i = 0; i < rawFiles.length; i++) {
            const rawFile = rawFiles[i];
            const rawPath = path.join(outputDir, rawFile);
            const finalPath = path.join(outputDir, `thumb-${i + 1}.png`);

            try {
                // Load the raw thumbnail
                const rawImage = await loadImage(rawPath);

                // Create A4 canvas
                const canvas = createCanvas(A4_WIDTH_PX, A4_HEIGHT_PX);
                const ctx = canvas.getContext('2d');

                // Fill with white background
                ctx.fillStyle = 'white';
                ctx.fillRect(0, 0, A4_WIDTH_PX, A4_HEIGHT_PX);

                // Calculate scaling to fit within A4 while preserving aspect ratio
                const scaleX = A4_WIDTH_PX / rawImage.width;
                const scaleY = A4_HEIGHT_PX / rawImage.height;
                const scale = Math.min(scaleX, scaleY); // Use smaller scale to fit entirely

                const scaledWidth = rawImage.width * scale;
                const scaledHeight = rawImage.height * scale;

                // Center the image on the A4 canvas
                const offsetX = (A4_WIDTH_PX - scaledWidth) / 2;
                const offsetY = (A4_HEIGHT_PX - scaledHeight) / 2;

                // Draw the scaled image centered on the A4 canvas
                ctx.drawImage(rawImage, offsetX, offsetY, scaledWidth, scaledHeight);

                // Save the A4 standardized thumbnail
                const buffer = canvas.toBuffer('image/png');
                fs.writeFileSync(finalPath, buffer);

                console.log(`Page ${i + 1}: A4 standardized (${rawImage.width}x${rawImage.height} -> ${A4_WIDTH_PX}x${A4_HEIGHT_PX}, scale: ${scale.toFixed(3)})`);

                // Clean up raw file
                fs.unlinkSync(rawPath);
                processedCount++;

            } catch (error) {
                console.error(`Error processing page ${i + 1}:`, error.message);
                // Clean up raw file even on error
                if (fs.existsSync(rawPath)) {
                    fs.unlinkSync(rawPath);
                }
            }
        }

        console.log(`A4 standardization completed: ${processedCount} thumbnails processed`);
        return processedCount;

    } catch (error) {
        console.error('Error generating A4 standardized thumbnails:', error.message);
        return 0;
    }
}

async function generateOriginalThumbnails(inputPath, outputDir, dpi) {
    try {
        // Try A4 standardization using pdftocairo first (no canvas needed)
        const a4Success = await tryA4WithPdftocairo(inputPath, outputDir, dpi);
        if (a4Success) {
            return a4Success;
        }

        // Fallback to original pdf-poppler
        console.log('Falling back to original pdf-poppler method');

        // Configure pdf-poppler options with color fixes
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
        console.error('Error generating original thumbnails:', error.message);
        return 0;
    }
}

async function tryA4WithPdftocairo(inputPath, outputDir, dpi) {
    try {
        const { exec } = require('child_process');
        const { promisify } = require('util');
        const execAsync = promisify(exec);

        console.log('Attempting A4 standardization using pdftocairo with version-specific color fixes');

        // Try to find pdftocairo and get version
        const isWindows = process.platform === 'win32';
        const pdftocairoCmd = isWindows ? 'pdftocairo.exe' : 'pdftocairo';

        let popplerVersion = null;
        try {
            const { stdout } = await execAsync(`${pdftocairoCmd} -v`);
            const versionMatch = stdout.match(/pdftocairo version (\d+\.\d+)/);
            if (versionMatch) {
                popplerVersion = parseFloat(versionMatch[1]);
                console.log(`Detected pdftocairo version: ${popplerVersion}`);
            }
        } catch (error) {
            console.log('pdftocairo not found, skipping A4 standardization');
            return false;
        }

        // Calculate optimal DPI for very large files
        let optimalDpi = dpi;
        if (dpi > 150) {
            // For high DPI requests on potentially large files, be more conservative
            optimalDpi = Math.max(72, Math.min(dpi, 150));
            console.log(`Adjusted DPI from ${dpi} to ${optimalDpi} for A4 standardization`);
        }

        // Use version-specific color handling
        const outputPrefix = path.join(outputDir, 'thumb');
        let command;
        
        if (popplerVersion && popplerVersion < 23.0) {
            // Older versions (22.x and below) - use simpler approach
            console.log('Using older pdftocairo version compatibility mode');
            command = `${pdftocairoCmd} -png -r ${optimalDpi} -W 595 -H 842 "${inputPath}" "${outputPrefix}"`;
        } else {
            // Newer versions (23.0+) - use advanced color handling
            console.log('Using newer pdftocairo version with ICC support');
            command = `${pdftocairoCmd} -png -r ${optimalDpi} -W 595 -H 842 -singlefile -transp -icc "${inputPath}" "${outputPrefix}"`;
        }

        console.log(`Executing: ${command}`);

        const { stdout, stderr } = await execAsync(command);

        if (stderr && !stderr.includes('Warning')) {
            console.log('pdftocairo stderr:', stderr);
        }

        // Check if files were created
        const files = fs.readdirSync(outputDir).filter(f => f.startsWith('thumb') && f.endsWith('.png'));

        if (files.length > 0) {
            console.log(`A4 standardization successful using pdftocairo: ${files.length} pages`);
            console.log(`Target format: A4 constrained (approximately 595x842px at ${optimalDpi} DPI)`);

            files.forEach((file, index) => {
                const filePath = path.join(outputDir, file);
                const stats = fs.statSync(filePath);
                console.log(`Page ${index + 1}: ${file} (${stats.size} bytes, A4 format)`);
            });

            return files.length;
        } else {
            console.log('Standard pdftocairo failed, trying alternative color conversion...');
            return await tryAlternativeColorConversion(inputPath, outputDir, optimalDpi);
        }

    } catch (error) {
        console.log('A4 standardization with pdftocairo failed:', error.message);
        return false;
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