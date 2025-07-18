#!/usr/bin/env node

import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const DOMAIN = 'philexscholar.koamishin.org';
const BASE_URL = `https://${DOMAIN}`;

console.log('📱 Generating Real APK for DSMS Philex\n');
console.log(`🌐 Domain: ${DOMAIN}`);
console.log(`🔗 PWA URL: ${BASE_URL}\n`);

async function checkPWAReadiness() {
  console.log('🔍 Checking PWA readiness...\n');
  
  try {
    // Check if PWABuilder CLI is installed
    execSync('npx @pwabuilder/cli --version', { stdio: 'pipe' });
    console.log('✅ PWABuilder CLI is available\n');
  } catch (error) {
    console.log('📦 Installing PWABuilder CLI...\n');
    try {
      execSync('npm install -g @pwabuilder/cli', { stdio: 'inherit' });
      console.log('✅ PWABuilder CLI installed\n');
    } catch (installError) {
      console.log('❌ Failed to install PWABuilder CLI');
      console.log('💡 Please install manually: npm install -g @pwabuilder/cli\n');
      return false;
    }
  }
  
  return true;
}

async function generateAPK() {
  console.log('🚀 Generating APK using PWABuilder...\n');
  
  try {
    // Create output directory
    const outputDir = path.join(__dirname, '..', 'apk-output');
    if (!fs.existsSync(outputDir)) {
      fs.mkdirSync(outputDir, { recursive: true });
    }
    
    // Generate APK using PWABuilder
    console.log('⏳ This may take a few minutes...\n');
    
    const command = `npx @pwabuilder/cli package --platform android --url ${BASE_URL} --output ${outputDir}`;
    console.log(`Running: ${command}\n`);
    
    execSync(command, { 
      stdio: 'inherit',
      cwd: __dirname 
    });
    
    console.log('\n✅ APK generation completed!\n');
    
    // Look for generated APK
    const apkFiles = fs.readdirSync(outputDir, { recursive: true })
      .filter(file => file.toString().endsWith('.apk'));
    
    if (apkFiles.length > 0) {
      console.log('📱 Generated APK files:');
      apkFiles.forEach(file => {
        console.log(`   • ${file}`);
      });
      
      // Copy the first APK to downloads directory
      const sourceApk = path.join(outputDir, apkFiles[0]);
      const destApk = path.join(__dirname, '..', 'public', 'downloads', 'dsms-philex.apk');
      
      if (fs.existsSync(sourceApk)) {
        fs.copyFileSync(sourceApk, destApk);
        console.log(`\n✅ APK copied to: public/downloads/dsms-philex.apk`);
        
        // Update APK info
        const apkStats = fs.statSync(destApk);
        const apkInfo = {
          name: "DSMS Philex",
          version: "1.0.0",
          packageId: "com.philex.dsms",
          size: `${(apkStats.size / 1024 / 1024).toFixed(2)} MB`,
          lastUpdated: new Date().toISOString(),
          downloadUrl: "/downloads/dsms-philex.apk",
          installInstructions: "Enable 'Install from unknown sources' in Android settings, then install the APK."
        };
        
        fs.writeFileSync(
          path.join(__dirname, '..', 'public', 'downloads', 'apk-info.json'),
          JSON.stringify(apkInfo, null, 2)
        );
        
        console.log('✅ APK info updated\n');
        
        console.log('🎉 Real APK is now ready for download!\n');
        console.log('🔗 Download URL: https://philexscholar.koamishin.org/downloads/dsms-philex.apk');
        console.log('📊 APK Size:', apkInfo.size);
        
      } else {
        console.log('❌ Generated APK file not found');
      }
    } else {
      console.log('❌ No APK files were generated');
    }
    
  } catch (error) {
    console.log('❌ APK generation failed:', error.message);
    console.log('\n🔧 Alternative methods:');
    console.log('1. Use PWABuilder website: https://www.pwabuilder.com/');
    console.log(`   Enter URL: ${BASE_URL}`);
    console.log('2. Use Android Studio with Capacitor:');
    console.log('   npx cap open android');
    console.log('3. Use Bubblewrap:');
    console.log('   npx @bubblewrap/cli init');
    
    return false;
  }
  
  return true;
}

async function main() {
  const isReady = await checkPWAReadiness();
  
  if (!isReady) {
    console.log('❌ PWA setup check failed');
    return;
  }
  
  const success = await generateAPK();
  
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
  }
}

main().catch(console.error);
