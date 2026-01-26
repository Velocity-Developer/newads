import fs from 'fs';
import path from 'path';
import JSZip from 'jszip';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const rootDir = path.resolve(__dirname, '..');
const sourceDir = path.join(rootDir, 'public', 'build');
const destDir = path.join(rootDir, 'dist');
const destFile = path.join(destDir, 'public_build.zip');

async function zipBuild() {
    console.log(`Zipping contents of ${sourceDir} to ${destFile}...`);
    
    if (!fs.existsSync(sourceDir)) {
        console.error(`Source directory not found: ${sourceDir}`);
        console.error('Make sure to run "npm run build" first.');
        process.exit(1);
    }

    if (!fs.existsSync(destDir)) {
        fs.mkdirSync(destDir, { recursive: true });
    }

    const zip = new JSZip();

    function addFilesRecursively(currentDir) {
        const items = fs.readdirSync(currentDir);
        
        for (const item of items) {
            const fullPath = path.join(currentDir, item);
            const stat = fs.statSync(fullPath);
            
            if (stat.isDirectory()) {
                addFilesRecursively(fullPath);
            } else {
                const relativePath = path.relative(sourceDir, fullPath);
                // Ensure forward slashes for zip internal paths, regardless of OS
                const zipPath = relativePath.split(path.sep).join('/');
                const content = fs.readFileSync(fullPath);
                zip.file(zipPath, content);
            }
        }
    }

    try {
        addFilesRecursively(sourceDir);
        
        // compression: "DEFLATE" is standard zip compression
        const content = await zip.generateAsync({ 
            type: "nodebuffer", 
            compression: "DEFLATE",
            compressionOptions: {
                level: 9 // Max compression
            }
        });
        
        fs.writeFileSync(destFile, content);
        console.log(`Successfully created ${destFile}`);
    } catch (err) {
        console.error('Error creating zip:', err);
        process.exit(1);
    }
}

zipBuild();
