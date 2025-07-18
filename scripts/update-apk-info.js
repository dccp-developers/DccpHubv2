#!/usr/bin/env node

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

function updateAPKInfo(apkPath, buildType = 'release') {
  try {
    const fullApkPath = path.resolve(apkPath);
    
    if (!fs.existsSync(fullApkPath)) {
      console.log(`❌ APK file not found: ${fullApkPath}`);
      return false;
    }
    
    const apkStats = fs.statSync(fullApkPath);
    const apkInfo = {
      name: "DSMS Philex",
      version: "1.0.0",
      packageId: "com.philex.dsms",
      size: `${(apkStats.size / 1024 / 1024).toFixed(2)} MB`,
      lastUpdated: new Date().toISOString(),
      downloadUrl: "/downloads/dsms-philex.apk",
      installInstructions: "Enable 'Install from unknown sources' in Android settings, then install the APK.",
      generatedWith: "Android Studio + Capacitor",
      buildType: buildType,
      buildDate: new Date().toLocaleDateString(),
      buildTime: new Date().toLocaleTimeString(),
      fileSize: apkStats.size,
      fileSizeFormatted: formatBytes(apkStats.size),
      signed: buildType === 'release' ? 'Yes' : 'Debug signed',
      architecture: 'Universal (ARM + x86)',
      minSdkVersion: '22',
      targetSdkVersion: '34',
      buildTools: 'Android Studio',
      framework: 'Capacitor + React + Laravel'
    };
    
    const infoPath = path.join(__dirname, '..', 'public', 'downloads', 'apk-info.json');
    fs.writeFileSync(infoPath, JSON.stringify(apkInfo, null, 2));
    
    console.log('✅ APK info updated successfully');
    console.log(`📊 APK Size: ${apkInfo.size}`);
    console.log(`🏗️ Build Type: ${buildType}`);
    console.log(`📅 Build Date: ${apkInfo.buildDate} ${apkInfo.buildTime}`);
    console.log(`🔐 Signed: ${apkInfo.signed}`);
    
    return true;
  } catch (error) {
    console.log('❌ Failed to update APK info:', error.message);
    return false;
  }
}

function formatBytes(bytes, decimals = 2) {
  if (bytes === 0) return '0 Bytes';
  
  const k = 1024;
  const dm = decimals < 0 ? 0 : decimals;
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
  
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// Main execution
const args = process.argv.slice(2);

if (args.length === 0) {
  console.log('📋 Usage: node scripts/update-apk-info.js <apk-path> [build-type]');
  console.log('');
  console.log('Examples:');
  console.log('  node scripts/update-apk-info.js android/app/build/outputs/apk/debug/app-debug.apk debug');
  console.log('  node scripts/update-apk-info.js android/app/build/outputs/apk/release/app-release.apk release');
  process.exit(1);
}

const apkPath = args[0];
const buildType = args[1] || 'release';

console.log(`📱 Updating APK info for: ${apkPath}`);
console.log(`🏗️ Build type: ${buildType}`);
console.log('');

const success = updateAPKInfo(apkPath, buildType);

if (success) {
  console.log('');
  console.log('🎉 APK info updated successfully!');
  console.log('🔗 Your APK is now available at:');
  console.log('   https://philexscholar.koamishin.org/downloads/dsms-philex.apk');
} else {
  console.log('');
  console.log('❌ Failed to update APK info');
  process.exit(1);
}
