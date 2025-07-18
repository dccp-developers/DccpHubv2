#!/usr/bin/env node

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

console.log('🔍 Testing PWA Configuration...\n');

// Check required files
const requiredFiles = [
  'public/site.webmanifest',
  'public/android-chrome-192x192.png',
  'public/android-chrome-512x512.png',
  'public/build/sw.js',
  'public/mobile/index.html'
];

let allFilesExist = true;

console.log('📁 Checking required files:');
requiredFiles.forEach(file => {
  const filePath = path.join(__dirname, '..', file);
  const exists = fs.existsSync(filePath);
  
  if (exists) {
    const stats = fs.statSync(filePath);
    const sizeInKB = (stats.size / 1024).toFixed(2);
    console.log(`   ✅ ${file} (${sizeInKB} KB)`);
  } else {
    console.log(`   ❌ ${file} - Missing!`);
    allFilesExist = false;
  }
});

// Check manifest content
console.log('\n📋 Checking PWA Manifest:');
const manifestPath = path.join(__dirname, '..', 'public', 'site.webmanifest');
if (fs.existsSync(manifestPath)) {
  try {
    const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
    
    console.log(`   ✅ Name: ${manifest.name}`);
    console.log(`   ✅ Short Name: ${manifest.short_name}`);
    console.log(`   ✅ Theme Color: ${manifest.theme_color}`);
    console.log(`   ✅ Display: ${manifest.display}`);
    console.log(`   ✅ Icons: ${manifest.icons?.length || 0} configured`);
    
    if (manifest.icons && manifest.icons.length > 0) {
      manifest.icons.forEach(icon => {
        console.log(`      - ${icon.sizes} (${icon.type})`);
      });
    }
  } catch (error) {
    console.log('   ❌ Invalid JSON in manifest');
    allFilesExist = false;
  }
} else {
  console.log('   ❌ Manifest file not found');
  allFilesExist = false;
}

// Check Capacitor configuration
console.log('\n📱 Checking Capacitor Configuration:');
const capacitorConfigPath = path.join(__dirname, '..', 'capacitor.config.ts');
if (fs.existsSync(capacitorConfigPath)) {
  console.log('   ✅ Capacitor config exists');
  
  // Check if Android platform exists
  const androidPath = path.join(__dirname, '..', 'android');
  if (fs.existsSync(androidPath)) {
    console.log('   ✅ Android platform configured');
  } else {
    console.log('   ⚠️  Android platform not added (run: npx cap add android)');
  }
} else {
  console.log('   ❌ Capacitor not configured');
}

// Generate summary
console.log('\n📊 PWA Readiness Summary:');
if (allFilesExist) {
  console.log('   ✅ All required files present');
  console.log('   ✅ PWA is ready for installation');
  console.log('   ✅ Ready for APK generation');
  
  console.log('\n🚀 Next Steps:');
  console.log('   1. Test PWA installation in browser');
  console.log('   2. Generate APK: bun run build:apk');
  console.log('   3. Test mobile app functionality');
  
  console.log('\n📱 APK Generation Options:');
  console.log('   • Capacitor (local): Requires Android Studio');
  console.log('   • PWABuilder (online): https://www.pwabuilder.com/');
  console.log('   • Bubblewrap (CLI): Google\'s PWA packaging tool');
  
} else {
  console.log('   ❌ Some required files are missing');
  console.log('   ⚠️  Please fix the issues above before proceeding');
}

console.log('\n🔗 Useful Links:');
console.log('   • PWA Testing: https://web.dev/pwa-checklist/');
console.log('   • Capacitor Docs: https://capacitorjs.com/docs');
console.log('   • PWABuilder: https://www.pwabuilder.com/');
