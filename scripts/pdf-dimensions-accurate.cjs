#!/usr/bin/env node

const { PDFDocument } = require('pdf-lib');
const fs = require('fs');
const path = require('path');

/**
 * Get PDF dimensions using pdf-lib (more accurate page count and dimensions)
 * Usage: node pdf-dimensions-accurate.cjs <pdf-path>
 */

async function getPdfDimensions(pdfPath) {
    try {
        if (!fs.existsSync(pdfPath)) {
            throw new Error(`PDF file not found: ${pdfPath}`);
        }
        
        // Read PDF file
        const pdfBuffer = fs.readFileSync(pdfPath);
        
        // Parse PDF with pdf-lib
        const pdfDoc = await PDFDocument.load(pdfBuffer);
        const pageCount = pdfDoc.getPageCount();
        
        if (!pageCount || pageCount <= 0) {
            throw new Error('Could not extract PDF page count');
        }

        const pages = [];
        
        // Get dimensions for each page
        for (let i = 0; i < pageCount; i++) {
            const page = pdfDoc.getPage(i);
            const { width, height } = page.getSize();
            
            // Convert from points to mm (1 point = 0.352778 mm)
            const widthMm = width * 0.352778;
            const heightMm = height * 0.352778;
            const areaM2 = (widthMm * heightMm) / 1000000;
            
            pages.push({
                page: i + 1,
                width_mm: Math.round(widthMm * 1000000) / 1000000, // Round to 6 decimal places
                height_mm: Math.round(heightMm * 1000000) / 1000000,
                area_m2: Math.round(areaM2 * 1000000) / 1000000
            });
        }

        const result = {
            pageCount: pageCount,
            pages: pages
        };

        return result;

    } catch (error) {
        console.error('Error:', error.message);
        process.exit(1);
    }
}

// Main execution
if (require.main === module) {
    const pdfPath = process.argv[2];
    
    if (!pdfPath) {
        console.error('Usage: node pdf-dimensions-accurate.cjs <pdf-path>');
        process.exit(1);
    }

    getPdfDimensions(pdfPath)
        .then(result => {
            console.log(JSON.stringify(result, null, 2));
        })
        .catch(error => {
            console.error('Failed to process PDF:', error.message);
            process.exit(1);
        });
}

module.exports = { getPdfDimensions };