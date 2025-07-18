#!/usr/bin/env node

import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

console.log('🚀 Building DSMS Philex APK...\n');

try {
  // Build the project first
  console.log('🔨 Building project...');
  execSync('bun run build', { stdio: 'inherit' });

  // Check if Capacitor is configured
  console.log('\n📱 Checking Capacitor configuration...');
  const capacitorConfigPath = path.join(process.cwd(), 'capacitor.config.ts');

  if (!fs.existsSync(capacitorConfigPath)) {
    throw new Error('Capacitor not configured. Run: npx cap init');
  }

  // Sync web assets to Android
  console.log('\n🔄 Syncing web assets to Android...');
  execSync('npx cap sync android', { stdio: 'inherit' });

  // Check if Android SDK is available
  console.log('\n🔍 Checking Android development environment...');

  try {
    // Try to build with Gradle
    console.log('📦 Building APK with Gradle...');
    execSync('cd android && ./gradlew assembleDebug', { stdio: 'inherit' });

    // Check for generated APK
    const apkPath = path.join(process.cwd(), 'android', 'app', 'build', 'outputs', 'apk', 'debug');
    if (fs.existsSync(apkPath)) {
      const files = fs.readdirSync(apkPath);
      const apkFiles = files.filter(file => file.endsWith('.apk'));

      if (apkFiles.length > 0) {
        console.log('\n✅ APK generated successfully!');
        console.log(`📁 Location: ${apkPath}`);

        apkFiles.forEach(file => {
          const filePath = path.join(apkPath, file);
          const stats = fs.statSync(filePath);
          const sizeInMB = (stats.size / (1024 * 1024)).toFixed(2);
          console.log(`   📱 ${file} (${sizeInMB} MB)`);
        });

        // Copy APK to public directory for download
        const publicApkPath = path.join(process.cwd(), 'public', 'downloads');
        if (!fs.existsSync(publicApkPath)) {
          fs.mkdirSync(publicApkPath, { recursive: true });
        }

        apkFiles.forEach(file => {
          const sourcePath = path.join(apkPath, file);
          const destPath = path.join(publicApkPath, 'dsms-philex.apk');
          fs.copyFileSync(sourcePath, destPath);
          console.log(`📋 Copied to: ${destPath}`);
        });
      }
    }

  } catch (gradleError) {
    console.log('\n⚠️  Gradle build failed. Trying alternative method...');

    // Fallback to PWABuilder
    console.log('\n📦 Using PWABuilder as fallback...');

    try {
      execSync('npx @pwabuilder/cli package --platform android', {
        stdio: 'inherit',
        cwd: process.cwd()
      });
      console.log('\n✅ APK generated with PWABuilder!');
    } catch (pwaError) {
      console.log('\n❌ PWABuilder also failed. Providing manual instructions...');

      console.log('\n📋 Manual APK Generation Instructions:');
      console.log('1. Install Android Studio: https://developer.android.com/studio');
      console.log('2. Set up Android SDK and build tools');
      console.log('3. Run: npx cap open android');
      console.log('4. Build APK from Android Studio');
      console.log('\nOr use online APK generators:');
      console.log('- PWABuilder: https://www.pwabuilder.com/');
      console.log('- Bubblewrap: https://github.com/GoogleChromeLabs/bubblewrap');
    }
  }

} catch (error) {
  console.error('\n❌ Error:', error.message);
  console.log('\n💡 Troubleshooting:');
  console.log('   - Ensure Capacitor is properly configured');
  console.log('   - Install Android Studio and SDK');
  console.log('   - Run: npx cap doctor');
  process.exit(1);
}
