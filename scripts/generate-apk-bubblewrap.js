#!/usr/bin/env node

import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const DOMAIN = 'philexscholar.koamishin.org';
const BASE_URL = `https://${DOMAIN}`;

console.log('📱 Generating Real APK using Bubblewrap\n');
console.log(`🌐 Domain: ${DOMAIN}`);
console.log(`🔗 PWA URL: ${BASE_URL}\n`);

async function installBubblewrap() {
  console.log('📦 Installing Bubblewrap...\n');
  
  try {
    // Check if Bubblewrap is already installed
    execSync('npx @bubblewrap/cli --version', { stdio: 'pipe' });
    console.log('✅ Bubblewrap is already available\n');
    return true;
  } catch (error) {
    console.log('📦 Installing Bubblewrap CLI...\n');
    try {
      execSync('npm install -g @bubblewrap/cli', { stdio: 'inherit' });
      console.log('✅ Bubblewrap CLI installed\n');
      return true;
    } catch (installError) {
      console.log('❌ Failed to install Bubblewrap CLI');
      console.log('💡 Please install manually: npm install -g @bubblewrap/cli\n');
      return false;
    }
  }
}

async function generateAPKWithBubblewrap() {
  console.log('🚀 Generating APK using Bubblewrap...\n');
  
  try {
    // Create bubblewrap directory
    const bubblewrapDir = path.join(__dirname, '..', 'bubblewrap-project');
    
    // Remove existing directory if it exists
    if (fs.existsSync(bubblewrapDir)) {
      fs.rmSync(bubblewrapDir, { recursive: true, force: true });
    }
    
    fs.mkdirSync(bubblewrapDir, { recursive: true });
    
    console.log('⏳ Initializing Bubblewrap project...\n');
    
    // Initialize Bubblewrap project
    const initCommand = `npx @bubblewrap/cli init --manifest ${BASE_URL}/site.webmanifest`;
    console.log(`Running: ${initCommand}\n`);
    
    execSync(initCommand, { 
      stdio: 'inherit',
      cwd: bubblewrapDir 
    });
    
    console.log('\n⏳ Building APK...\n');
    
    // Build the APK
    execSync('npx @bubblewrap/cli build', { 
      stdio: 'inherit',
      cwd: bubblewrapDir 
    });
    
    console.log('\n✅ APK generation completed!\n');
    
    // Look for generated APK
    const apkPath = path.join(bubblewrapDir, 'app-release-unsigned.apk');
    const signedApkPath = path.join(bubblewrapDir, 'app-release.apk');
    
    let sourceApk = null;
    if (fs.existsSync(signedApkPath)) {
      sourceApk = signedApkPath;
      console.log('📱 Found signed APK');
    } else if (fs.existsSync(apkPath)) {
      sourceApk = apkPath;
      console.log('📱 Found unsigned APK');
    }
    
    if (sourceApk) {
      // Copy APK to downloads directory
      const destApk = path.join(__dirname, '..', 'public', 'downloads', 'dsms-philex.apk');
      fs.copyFileSync(sourceApk, destApk);
      
      console.log(`✅ APK copied to: public/downloads/dsms-philex.apk`);
      
      // Update APK info
      const apkStats = fs.statSync(destApk);
      const apkInfo = {
        name: "DSMS Philex",
        version: "1.0.0",
        packageId: "com.philex.dsms",
        size: `${(apkStats.size / 1024 / 1024).toFixed(2)} MB`,
        lastUpdated: new Date().toISOString(),
        downloadUrl: "/downloads/dsms-philex.apk",
        installInstructions: "Enable 'Install from unknown sources' in Android settings, then install the APK.",
        generatedWith: "Bubblewrap"
      };
      
      fs.writeFileSync(
        path.join(__dirname, '..', 'public', 'downloads', 'apk-info.json'),
        JSON.stringify(apkInfo, null, 2)
      );
      
      console.log('✅ APK info updated\n');
      
      console.log('🎉 Real APK is now ready for download!\n');
      console.log('🔗 Download URL: https://philexscholar.koamishin.org/downloads/dsms-philex.apk');
      console.log('📊 APK Size:', apkInfo.size);
      
      return true;
    } else {
      console.log('❌ Generated APK file not found');
      return false;
    }
    
  } catch (error) {
    console.log('❌ APK generation failed:', error.message);
    return false;
  }
}

async function main() {
  const bubblewrapInstalled = await installBubblewrap();
  
  if (!bubblewrapInstalled) {
    console.log('❌ Bubblewrap installation failed');
    console.log('\n🔧 Alternative methods:');
    console.log('1. Use PWABuilder website: https://www.pwabuilder.com/');
    console.log(`   Enter URL: ${BASE_URL}`);
    console.log('2. Use Android Studio with Capacitor:');
    console.log('   npx cap open android');
    return;
  }
  
  const success = await generateAPKWithBubblewrap();
  
  if (success) {
    console.log('\n📱 Installation Instructions:');
    console.log('1. Download the APK from your website');
    console.log('2. On Android device, go to Settings > Security');
    console.log('3. Enable "Install from unknown sources" or "Allow from this source"');
    console.log('4. Open the downloaded APK file');
    console.log('5. Follow the installation prompts');
    
    console.log('\n🔧 Testing:');
    console.log('• Test the APK on a real Android device');
    console.log('• Verify all app features work correctly');
    console.log('• Check that the app opens your website properly');
    
    console.log('\n📝 Note:');
    console.log('• This APK is unsigned (for testing only)');
    console.log('• For production, you should sign the APK with your keystore');
    console.log('• Consider publishing to Google Play Store for wider distribution');
  } else {
    console.log('\n🌐 Easiest Alternative: Use PWABuilder Website');
    console.log('1. Go to: https://www.pwabuilder.com/');
    console.log(`2. Enter your URL: ${BASE_URL}`);
    console.log('3. Click "Start" and follow the steps');
    console.log('4. Download the generated APK');
    console.log('5. Replace the placeholder APK in public/downloads/');
  }
}

main().catch(console.error);
