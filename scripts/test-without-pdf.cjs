#!/usr/bin/env node

const { exec } = require('child_process');
const { promisify } = require('util');
const fs = require('fs');

const execAsync = promisify(exec);

async function testWithoutPDF() {
    console.log('🧪 Testing pdftocairo capabilities without PDF...\n');

    // Method 1: Create a simple test PDF using Ghostscript (if available)
    console.log('📄 Attempting to create test PDF...');

    try {
        // Create a simple PostScript file that we can convert to PDF
        const psContent = `%!PS-Adobe-3.0
%%BoundingBox: 0 0 595 842
%%Pages: 1
%%Page: 1 1
/Helvetica findfont 24 scalefont setfont
100 700 moveto
(Test CMYK Colors) show
0.8 0.2 0.9 0.1 setcmykcolor
100 600 200 100 rectfill
0.2 0.8 0.1 0.9 setcmykcolor  
350 600 200 100 rectfill
showpage
%%EOF`;

        fs.writeFileSync('/tmp/test.ps', psContent);
        console.log('✅ Created test PostScript file');

        // Convert PS to PDF using Ghostscript
        await execAsync('gs -sDEVICE=pdfwrite -o /tmp/test.pdf /tmp/test.ps');
        console.log('✅ Created test PDF with CMYK colors');

        // Now test pdftocairo with this PDF
        return await testPdftocairoMethods('/tmp/test.pdf');

    } catch (error) {
        console.log('❌ Could not create test PDF with Ghostscript');
    }

    // Method 2: Test pdftocairo version and flags without PDF
    console.log('\n🔧 Testing pdftocairo version and capabilities...');

    try {
        // Get detailed version info
        const { stdout: version } = await execAsync('pdftocairo -v');
        console.log(`Version: ${version.trim()}`);

        // Test flag support
        const { stdout: help } = await execAsync('pdftocairo -h');

        const flagTests = [
            { flag: '-icc', description: 'ICC color profile support' },
            { flag: '-transp', description: 'Transparency support' },
            { flag: '-gray', description: 'Grayscale conversion' },
            { flag: '-W', description: 'Width constraint' },
            { flag: '-H', description: 'Height constraint' }
        ];

        console.log('\n🚩 Flag Support:');
        flagTests.forEach(test => {
            // More thorough flag detection
            const supported = help.includes(test.flag + ' ') || help.includes(test.flag + '\n') || help.includes(test.flag + '<');
            console.log(`${supported ? '✅' : '❌'} ${test.flag} - ${test.description}`);
        });

        // Debug: Show actual help content for flags we care about
        console.log('\n🔍 Debug - Looking for key flags in help:');
        const helpLines = help.split('\n');
        helpLines.forEach(line => {
            if (line.includes('-icc') || line.includes('-transp') || line.includes('-gray') || line.includes('-W ') || line.includes('-H ')) {
                console.log(`   Found: ${line.trim()}`);
            }
        });

        // Check for color-related options in help
        console.log('\n🌈 Color-related options:');
        const lines = help.split('\n');
        lines.forEach(line => {
            if (line.includes('color') || line.includes('icc') || line.includes('antialias')) {
                console.log(`   ${line.trim()}`);
            }
        });

    } catch (error) {
        console.log('❌ Could not get pdftocairo info');
    }

    // Method 3: Compare with your local version behavior
    console.log('\n📊 Version Comparison:');
    console.log('Local (Windows): pdftocairo version 25.07.0');
    console.log('Production (Linux): pdftocairo version 22.02.0');
    console.log('');
    console.log('💡 Known differences between versions:');
    console.log('- Version 22.x: Different default CMYK→RGB conversion');
    console.log('- Version 25.x: Improved color space handling');
    console.log('- ICC profile behavior changed between versions');

    // Method 4: Suggest specific commands to try
    console.log('\n🎯 Recommended test approach:');
    console.log('Since we can\'t test without a PDF, here are the most likely solutions:');
    console.log('');
    console.log('1. Force simple conversion (no extra flags):');
    console.log('   pdftocairo -png -r 72 input.pdf output');
    console.log('');
    console.log('2. Use different resolution:');
    console.log('   pdftocairo -png -r 150 input.pdf output');
    console.log('');
    console.log('3. Remove size constraints:');
    console.log('   pdftocairo -png -r 72 input.pdf output  # (no -W -H flags)');

    return { needsPDF: true };
}

async function testPdftocairoMethods(pdfPath) {
    console.log('\n🧪 Testing pdftocairo methods with synthetic PDF...');

    const outputDir = '/tmp/pdftocairo-test';
    if (!fs.existsSync(outputDir)) {
        fs.mkdirSync(outputDir, { recursive: true });
    }

    const methods = [
        {
            name: 'Basic',
            command: `pdftocairo -png -r 72 "${pdfPath}" "${outputDir}/basic"`
        },
        {
            name: 'No size constraints',
            command: `pdftocairo -png -r 72 "${pdfPath}" "${outputDir}/no-size"`
        },
        {
            name: 'With transparency',
            command: `pdftocairo -png -r 72 -transp "${pdfPath}" "${outputDir}/transp"`
        },
        {
            name: 'Higher resolution',
            command: `pdftocairo -png -r 150 "${pdfPath}" "${outputDir}/hires"`
        }
    ];

    const results = [];

    for (const method of methods) {
        try {
            console.log(`Testing: ${method.name}`);
            await execAsync(method.command);

            const files = fs.readdirSync(outputDir).filter(f =>
                f.includes(method.command.split('/').pop().split(' ')[0]) && f.endsWith('.png')
            );

            if (files.length > 0) {
                console.log(`✅ ${method.name}: Success (${files.length} files)`);
                results.push({ method: method.name, success: true, command: method.command });
            } else {
                console.log(`❌ ${method.name}: No files generated`);
                results.push({ method: method.name, success: false });
            }
        } catch (error) {
            console.log(`❌ ${method.name}: Failed - ${error.message.substring(0, 50)}`);
            results.push({ method: method.name, success: false, error: error.message });
        }
    }

    console.log('\n📊 Results:');
    const successful = results.filter(r => r.success);
    if (successful.length > 0) {
        console.log('✅ Working methods:');
        successful.forEach(r => console.log(`   - ${r.method}`));
        console.log(`\n📁 Test files created in: ${outputDir}`);
        console.log('💡 These methods should work with your actual PDFs too!');
    }

    return results;
}

testWithoutPDF()
    .then(results => {
        // Cleanup temporary files
        console.log('\n🧹 Cleaning up temporary files...');

        const tempFiles = [
            '/tmp/test.ps',
            '/tmp/test.pdf'
        ];

        tempFiles.forEach(file => {
            try {
                if (fs.existsSync(file)) {
                    fs.unlinkSync(file);
                    console.log(`✅ Deleted: ${file}`);
                }
            } catch (error) {
                console.log(`⚠️  Could not delete ${file}: ${error.message}`);
            }
        });

        // Also clean up test output directory
        try {
            const testDir = '/tmp/pdftocairo-test';
            if (fs.existsSync(testDir)) {
                const files = fs.readdirSync(testDir);
                files.forEach(f => fs.unlinkSync(`${testDir}/${f}`));
                fs.rmdirSync(testDir);
                console.log(`✅ Deleted test directory: ${testDir}`);
            }
        } catch (error) {
            console.log(`⚠️  Could not clean test directory: ${error.message}`);
        }

        console.log('🎯 Cleanup complete!');
    })
    .catch(error => {
        console.error('Unexpected error:', error);

        // Still try to cleanup on error
        try {
            ['/tmp/test.ps', '/tmp/test.pdf'].forEach(file => {
                if (fs.existsSync(file)) fs.unlinkSync(file);
            });
        } catch (cleanupError) {
            // Silent cleanup failure
        }
    });