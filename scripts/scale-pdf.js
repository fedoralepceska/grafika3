#!/usr/bin/env node

import { PDFDocument } from 'pdf-lib';
import fs from 'fs';
import path from 'path';

async function scalePdf(inputPath, outputPath, scaleFactor = 0.5) {
    try {
        console.log(`Starting PDF scaling: ${inputPath} -> ${outputPath}`);
        
        // Read the input PDF
        const inputPdfBytes = fs.readFileSync(inputPath);
        const inputPdf = await PDFDocument.load(inputPdfBytes);
        
        // Create a new PDF document
        const outputPdf = await PDFDocument.create();
        
        // Get all pages from input PDF
        const pages = await outputPdf.copyPages(inputPdf, inputPdf.getPageIndices());
        
        // Process each page
        for (let i = 0; i < pages.length; i++) {
            const page = pages[i];
            const { width, height } = page.getSize();
            
            console.log(`Processing page ${i + 1}: ${width}x${height} -> ${width * scaleFactor}x${height * scaleFactor}`);
            
            // Scale down the page content and size
            page.scaleContent(scaleFactor, scaleFactor);
            page.setSize(width * scaleFactor, height * scaleFactor);
            
            // Add the scaled page to output PDF
            outputPdf.addPage(page);
        }
        
        // Set PDF metadata to indicate it's a scaled version
        outputPdf.setTitle('Scaled PDF for Thumbnails');
        outputPdf.setCreator('pdf-lib scaling script');
        
        // Save the scaled PDF
        const outputPdfBytes = await outputPdf.save();
        fs.writeFileSync(outputPath, outputPdfBytes);
        
        const inputSize = fs.statSync(inputPath).size;
        const outputSize = fs.statSync(outputPath).size;
        
        console.log(`PDF scaling completed successfully`);
        console.log(`Input size: ${inputSize} bytes`);
        console.log(`Output size: ${outputSize} bytes`);
        console.log(`Size reduction: ${((1 - outputSize / inputSize) * 100).toFixed(1)}%`);
        
        return true;
    } catch (error) {
        console.error('Error scaling PDF:', error.message);
        return false;
    }
}

// Command line interface
if (process.argv.length < 4) {
    console.error('Usage: node scale-pdf.js <input-pdf> <output-pdf> [scale-factor]');
    process.exit(1);
}

const inputPath = process.argv[2];
const outputPath = process.argv[3];
const scaleFactor = process.argv[4] ? parseFloat(process.argv[4]) : 0.5;

if (!fs.existsSync(inputPath)) {
    console.error(`Input file does not exist: ${inputPath}`);
    process.exit(1);
}

// Ensure output directory exists
const outputDir = path.dirname(outputPath);
if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
}

scalePdf(inputPath, outputPath, scaleFactor)
    .then(success => {
        process.exit(success ? 0 : 1);
    })
    .catch(error => {
        console.error('Unexpected error:', error);
        process.exit(1);
    });