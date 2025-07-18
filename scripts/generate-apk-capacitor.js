#!/usr/bin/env node

import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

console.log('📱 Generating Real APK using Capacitor\n');

async function checkAndroidSetup() {
  console.log('🔍 Checking Android development setup...\n');
  
  try {
    // Check if Android SDK is available
    execSync('which android', { stdio: 'pipe' });
    console.log('✅ Android SDK found');
  } catch (error) {
    console.log('⚠️  Android SDK not found in PATH');
  }
  
  try {
    // Check if Java is available
    const javaVersion = execSync('java -version 2>&1', { encoding: 'utf8' });
    console.log('✅ Java found:', javaVersion.split('\n')[0]);
  } catch (error) {
    console.log('❌ Java not found');
    return false;
  }
  
  // Check if Gradle wrapper exists
  const gradleWrapper = path.join(__dirname, '..', 'android', 'gradlew');
  if (fs.existsSync(gradleWrapper)) {
    console.log('✅ Gradle wrapper found');
  } else {
    console.log('❌ Gradle wrapper not found');
    return false;
  }
  
  return true;
}

async function buildAPKWithCapacitor() {
  console.log('\n🚀 Building APK with Capacitor...\n');
  
  try {
    // First, sync the latest changes
    console.log('🔄 Syncing Capacitor...');
    execSync('npx cap sync android', { stdio: 'inherit' });
    
    console.log('\n🔨 Building APK...');
    
    // Try to build using Gradle
    const androidDir = path.join(__dirname, '..', 'android');
    
    // Make gradlew executable
    const gradlewPath = path.join(androidDir, 'gradlew');
    if (fs.existsSync(gradlewPath)) {
      execSync(`chmod +x ${gradlewPath}`, { stdio: 'pipe' });
    }
    
    // Build debug APK
    execSync('./gradlew assembleDebug', { 
      stdio: 'inherit',
      cwd: androidDir 
    });
    
    console.log('\n✅ APK build completed!\n');
    
    // Look for generated APK
    const apkPath = path.join(androidDir, 'app', 'build', 'outputs', 'apk', 'debug', 'app-debug.apk');
    
    if (fs.existsSync(apkPath)) {
      // Copy APK to downloads directory
      const destApk = path.join(__dirname, '..', 'public', 'downloads', 'dsms-philex.apk');
      fs.copyFileSync(apkPath, destApk);
      
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
        generatedWith: "Capacitor + Gradle",
        buildType: "debug"
      };
      
      fs.writeFileSync(
        path.join(__dirname, '..', 'public', 'downloads', 'apk-info.json'),
        JSON.stringify(apkInfo, null, 2)
      );
      
      console.log('✅ APK info updated\n');
      
      console.log('🎉 Real APK is now ready for download!\n');
      console.log('🔗 Download URL: https://philexscholar.koamishin.org/downloads/dsms-philex.apk');
      console.log('📊 APK Size:', apkInfo.size);
      console.log('🔧 Build Type: Debug (unsigned)');
      
      return true;
    } else {
      console.log('❌ Generated APK file not found at expected location');
      console.log(`Expected: ${apkPath}`);
      
      // Try to find APK files in the build directory
      const buildDir = path.join(androidDir, 'app', 'build', 'outputs', 'apk');
      if (fs.existsSync(buildDir)) {
        console.log('\n🔍 Searching for APK files...');
        const findAPKs = (dir) => {
          const files = fs.readdirSync(dir, { withFileTypes: true });
          let apks = [];
          
          for (const file of files) {
            const fullPath = path.join(dir, file.name);
            if (file.isDirectory()) {
              apks = apks.concat(findAPKs(fullPath));
            } else if (file.name.endsWith('.apk')) {
              apks.push(fullPath);
            }
          }
          return apks;
        };
        
        const foundAPKs = findAPKs(buildDir);
        if (foundAPKs.length > 0) {
          console.log('📱 Found APK files:');
          foundAPKs.forEach(apk => console.log(`   • ${apk}`));
          
          // Use the first found APK
          const sourceApk = foundAPKs[0];
          const destApk = path.join(__dirname, '..', 'public', 'downloads', 'dsms-philex.apk');
          fs.copyFileSync(sourceApk, destApk);
          console.log(`✅ APK copied from: ${sourceApk}`);
          return true;
        }
      }
      
      return false;
    }
    
  } catch (error) {
    console.log('❌ APK build failed:', error.message);
    return false;
  }
}

async function main() {
  const androidReady = await checkAndroidSetup();
  
  if (!androidReady) {
    console.log('\n❌ Android development environment not properly set up\n');
    console.log('🔧 To set up Android development:');
    console.log('1. Install Android Studio: https://developer.android.com/studio');
    console.log('2. Install Android SDK and build tools');
    console.log('3. Set ANDROID_HOME environment variable');
    console.log('4. Add Android SDK tools to PATH');
    console.log('\n🌐 Easiest Alternative: Use PWABuilder Website');
    console.log('1. Go to: https://www.pwabuilder.com/');
    console.log('2. Enter your URL: https://philexscholar.koamishin.org');
    console.log('3. Click "Start" and follow the steps');
    console.log('4. Download the generated APK');
    console.log('5. Replace the placeholder APK in public/downloads/');
    return;
  }
  
  const success = await buildAPKWithCapacitor();
  
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
    console.log('• This is a debug APK (unsigned)');
    console.log('• For production, build a release APK with signing');
    console.log('• Consider publishing to Google Play Store');
  } else {
    console.log('\n🌐 Recommended: Use PWABuilder Website');
    console.log('1. Go to: https://www.pwabuilder.com/');
    console.log('2. Enter your URL: https://philexscholar.koamishin.org');
    console.log('3. Click "Start" and follow the steps');
    console.log('4. Download the generated APK');
    console.log('5. Replace the placeholder APK in public/downloads/');
  }
}

main().catch(console.error);
