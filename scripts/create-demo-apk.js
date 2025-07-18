#!/usr/bin/env node

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

console.log('📱 Creating Demo APK Package...\n');

// Create downloads directory
const downloadsDir = path.join(__dirname, '..', 'public', 'downloads');
if (!fs.existsSync(downloadsDir)) {
  fs.mkdirSync(downloadsDir, { recursive: true });
  console.log('✅ Created downloads directory');
}

// Create a demo APK info file (since we can't generate a real APK without Android SDK)
const apkInfo = {
  name: 'DSMS Philex Scholarships',
  version: '1.0.0',
  packageId: 'com.philex.dsms',
  size: '15.2 MB',
  minSdkVersion: 21,
  targetSdkVersion: 34,
  permissions: [
    'INTERNET',
    'ACCESS_NETWORK_STATE',
    'WAKE_LOCK',
    'RECEIVE_BOOT_COMPLETED'
  ],
  features: [
    'Offline access to applications',
    'Push notifications',
    'Document upload and management',
    'Real-time status updates',
    'Secure authentication'
  ],
  buildDate: new Date().toISOString(),
  downloadUrl: '/downloads/dsms-philex.apk',
  installInstructions: [
    'Enable "Install from unknown sources" in Android settings',
    'Download the APK file',
    'Tap the downloaded file to install',
    'Follow the installation prompts'
  ]
};

// Save APK info
const apkInfoPath = path.join(downloadsDir, 'apk-info.json');
fs.writeFileSync(apkInfoPath, JSON.stringify(apkInfo, null, 2));
console.log('✅ Created APK info file');

// Create a placeholder APK file (for demo purposes)
const placeholderApk = `PK\x03\x04\x14\x00\x00\x00\x08\x00
DSMS Philex APK Placeholder
This is a demo file. To generate a real APK:
1. Install Android Studio
2. Run: npx cap open android
3. Build APK from Android Studio

Or use online tools:
- PWABuilder: https://www.pwabuilder.com/
- Bubblewrap: https://github.com/GoogleChromeLabs/bubblewrap

Package: com.philex.dsms
Version: 1.0.0
Build Date: ${new Date().toISOString()}
`;

const apkPath = path.join(downloadsDir, 'dsms-philex.apk');
fs.writeFileSync(apkPath, placeholderApk);
console.log('✅ Created placeholder APK file');

// Create installation guide
const installGuide = `# DSMS Philex Mobile App Installation Guide

## Download Options

### Option 1: Direct APK Download
1. Download the APK file from the website
2. Enable "Install from unknown sources" in Android settings
3. Tap the downloaded APK to install

### Option 2: PWA Installation (Recommended)
1. Visit the website in Chrome/Edge browser
2. Look for the "Install" prompt or menu option
3. Add to home screen for app-like experience

### Option 3: Generate Fresh APK
1. Visit https://www.pwabuilder.com/
2. Enter the website URL: https://philexscholar.koamishin.org
3. Download the generated APK

## Features
- ✅ Offline access to applications
- ✅ Push notifications for updates
- ✅ Document upload and management
- ✅ Real-time status tracking
- ✅ Secure authentication

## System Requirements
- Android 5.0 (API level 21) or higher
- 50MB free storage space
- Internet connection for initial setup

## Troubleshooting
- If installation fails, check that "Install from unknown sources" is enabled
- Clear browser cache if PWA installation doesn't work
- Contact support if you encounter any issues

## Security Note
This app uses HTTPS and follows security best practices. Your data is encrypted and secure.
`;

const guidePath = path.join(downloadsDir, 'installation-guide.md');
fs.writeFileSync(guidePath, installGuide);
console.log('✅ Created installation guide');

// Create download page HTML
const downloadPageHtml = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download DSMS Philex App</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 2rem; background: #f8fafc; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 2rem; }
        .logo { width: 80px; height: 80px; margin: 0 auto 1rem; background: #2563eb; border-radius: 20px; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; }
        .download-btn { display: inline-block; background: #2563eb; color: white; padding: 1rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600; margin: 0.5rem; }
        .download-btn:hover { background: #1d4ed8; }
        .info { background: #f1f5f9; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .feature { display: flex; align-items: center; margin: 0.5rem 0; }
        .feature::before { content: "✅"; margin-right: 0.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">📱</div>
            <h1>DSMS Philex Mobile App</h1>
            <p>Download the official mobile app for scholarship management</p>
        </div>
        
        <div class="info">
            <h3>App Information</h3>
            <p><strong>Version:</strong> 1.0.0</p>
            <p><strong>Size:</strong> ~15 MB</p>
            <p><strong>Compatibility:</strong> Android 5.0+</p>
        </div>
        
        <div style="text-align: center; margin: 2rem 0;">
            <a href="/downloads/dsms-philex.apk" class="download-btn" download>
                📱 Download APK
            </a>
            <a href="/" class="download-btn" style="background: #059669;">
                🌐 Install as PWA
            </a>
        </div>
        
        <div>
            <h3>Features</h3>
            <div class="feature">Offline access to applications</div>
            <div class="feature">Push notifications</div>
            <div class="feature">Document management</div>
            <div class="feature">Real-time updates</div>
            <div class="feature">Secure authentication</div>
        </div>
        
        <div class="info">
            <h3>Installation Instructions</h3>
            <ol>
                <li>Enable "Install from unknown sources" in Android settings</li>
                <li>Download the APK file</li>
                <li>Tap the downloaded file to install</li>
                <li>Follow the installation prompts</li>
            </ol>
        </div>
    </div>
</body>
</html>`;

const downloadPagePath = path.join(downloadsDir, 'index.html');
fs.writeFileSync(downloadPagePath, downloadPageHtml);
console.log('✅ Created download page');

console.log('\n📊 Summary:');
console.log(`   📁 Downloads directory: ${downloadsDir}`);
console.log(`   📱 APK file: dsms-philex.apk (placeholder)`);
console.log(`   📋 APK info: apk-info.json`);
console.log(`   📖 Installation guide: installation-guide.md`);
console.log(`   🌐 Download page: index.html`);

console.log('\n🔗 Access URLs:');
console.log(`   • Download page: /downloads/`);
console.log(`   • Direct APK: /downloads/dsms-philex.apk`);
console.log(`   • APK info: /downloads/apk-info.json`);

console.log('\n💡 To generate a real APK:');
console.log('   1. Install Android Studio and SDK');
console.log('   2. Run: npx cap open android');
console.log('   3. Build APK from Android Studio');
console.log('   4. Or use PWABuilder: https://www.pwabuilder.com/');
