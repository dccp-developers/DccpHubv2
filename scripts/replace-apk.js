#!/usr/bin/env node

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

console.log('📱 APK Replacement Helper\n');

function updateAPKInfo(apkPath) {
  try {
    const apkStats = fs.statSync(apkPath);
    const apkInfo = {
      name: "DSMS Philex",
      version: "1.0.0",
      packageId: "com.philex.dsms",
      size: `${(apkStats.size / 1024 / 1024).toFixed(2)} MB`,
      lastUpdated: new Date().toISOString(),
      downloadUrl: "/downloads/dsms-philex.apk",
      installInstructions: "Enable 'Install from unknown sources' in Android settings, then install the APK.",
      generatedWith: "PWABuilder",
      buildType: "release"
    };
    
    const infoPath = path.join(__dirname, '..', 'public', 'downloads', 'apk-info.json');
    fs.writeFileSync(infoPath, JSON.stringify(apkInfo, null, 2));
    
    console.log('✅ APK info updated');
    console.log(`📊 APK Size: ${apkInfo.size}`);
    console.log(`📅 Last Updated: ${apkInfo.lastUpdated}`);
    
    return true;
  } catch (error) {
    console.log('❌ Failed to update APK info:', error.message);
    return false;
  }
}

function replaceAPK(sourcePath) {
  const destPath = path.join(__dirname, '..', 'public', 'downloads', 'dsms-philex.apk');
  
  try {
    // Check if source file exists
    if (!fs.existsSync(sourcePath)) {
      console.log(`❌ Source APK not found: ${sourcePath}`);
      return false;
    }
    
    // Check if it's actually an APK file
    const fileBuffer = fs.readFileSync(sourcePath, { start: 0, end: 4 });
    const isAPK = fileBuffer.toString('hex').startsWith('504b0304') || // ZIP signature
                  fileBuffer.toString().includes('PK'); // Alternative ZIP check
    
    if (!isAPK) {
      console.log('⚠️  Warning: File may not be a valid APK (doesn\'t have ZIP signature)');
      console.log('   Continuing anyway...');
    }
    
    // Copy the file
    fs.copyFileSync(sourcePath, destPath);
    console.log(`✅ APK copied to: ${destPath}`);
    
    // Update APK info
    updateAPKInfo(destPath);
    
    return true;
  } catch (error) {
    console.log('❌ Failed to replace APK:', error.message);
    return false;
  }
}

function main() {
  const args = process.argv.slice(2);
  
  if (args.length === 0) {
    console.log('📋 Usage: node scripts/replace-apk.js <path-to-new-apk>\n');
    console.log('🌐 To generate a real APK:');
    console.log('1. Go to: https://www.pwabuilder.com/');
    console.log('2. Enter your URL: https://philexscholar.koamishin.org');
    console.log('3. Click "Start" and analyze your PWA');
    console.log('4. Click "Package For Stores"');
    console.log('5. Select "Android" and click "Generate Package"');
    console.log('6. Download the generated APK');
    console.log('7. Run: node scripts/replace-apk.js /path/to/downloaded.apk\n');
    
    console.log('📱 Current APK status:');
    const currentAPK = path.join(__dirname, '..', 'public', 'downloads', 'dsms-philex.apk');
    if (fs.existsSync(currentAPK)) {
      const stats = fs.statSync(currentAPK);
      console.log(`   File: ${currentAPK}`);
      console.log(`   Size: ${(stats.size / 1024).toFixed(2)} KB`);
      console.log(`   Modified: ${stats.mtime.toISOString()}`);
      
      // Check if it's the placeholder
      const content = fs.readFileSync(currentAPK, 'utf8', { start: 0, end: 100 });
      if (content.includes('Placeholder')) {
        console.log('   Status: ⚠️  Placeholder file (not a real APK)');
      } else {
        console.log('   Status: ✅ Real APK file');
      }
    } else {
      console.log('   Status: ❌ No APK file found');
    }
    
    return;
  }
  
  const sourcePath = args[0];
  console.log(`📥 Replacing APK with: ${sourcePath}\n`);
  
  const success = replaceAPK(sourcePath);
  
  if (success) {
    console.log('\n🎉 APK replacement completed successfully!\n');
    console.log('🔗 Your APK is now available at:');
    console.log('   https://philexscholar.koamishin.org/downloads/dsms-philex.apk\n');
    
    console.log('📱 Test the installation:');
    console.log('1. Download the APK on an Android device');
    console.log('2. Enable "Install from unknown sources" in Settings');
    console.log('3. Install the APK');
    console.log('4. Verify the app opens and works correctly');
  } else {
    console.log('\n❌ APK replacement failed');
  }
}

main();
