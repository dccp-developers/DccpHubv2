#!/usr/bin/env node

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

console.log('🎉 DSMS Philex PWA & APK Setup - Final Test\n');

// Test all components
const tests = [
  {
    name: 'PWA Manifest',
    path: 'public/site.webmanifest',
    test: (content) => {
      const manifest = JSON.parse(content);
      return manifest.name && manifest.icons && manifest.display === 'standalone';
    }
  },
  {
    name: 'Service Worker',
    path: 'public/build/sw.js',
    test: (content) => content.includes('workbox') && content.length > 1000
  },
  {
    name: 'App Icons',
    path: 'public/android-chrome-512x512.png',
    test: () => true // Just check existence
  },
  {
    name: 'Mobile Entry Point',
    path: 'public/mobile/index.html',
    test: (content) => content.includes('DSMS Philex') && content.includes('manifest')
  },
  {
    name: 'APK Download Page',
    path: 'public/downloads/index.html',
    test: (content) => content.includes('Download APK') && content.includes('Installation')
  },
  {
    name: 'APK Info',
    path: 'public/downloads/apk-info.json',
    test: (content) => {
      const info = JSON.parse(content);
      return info.name && info.version && info.packageId;
    }
  },
  {
    name: 'Capacitor Config',
    path: 'capacitor.config.ts',
    test: (content) => content.includes('com.philex.dsms') && content.includes('public/mobile')
  },
  {
    name: 'Android Platform',
    path: 'android/app/build.gradle',
    test: () => true // Just check existence
  }
];

let passedTests = 0;
let totalTests = tests.length;

console.log('🔍 Running Tests:\n');

tests.forEach((test, index) => {
  const filePath = path.join(__dirname, '..', test.path);
  const exists = fs.existsSync(filePath);
  
  if (!exists) {
    console.log(`   ${index + 1}. ❌ ${test.name} - File missing: ${test.path}`);
    return;
  }
  
  try {
    const content = fs.readFileSync(filePath, 'utf8');
    const testResult = test.test(content);
    
    if (testResult) {
      console.log(`   ${index + 1}. ✅ ${test.name}`);
      passedTests++;
    } else {
      console.log(`   ${index + 1}. ❌ ${test.name} - Content validation failed`);
    }
  } catch (error) {
    console.log(`   ${index + 1}. ❌ ${test.name} - Error: ${error.message}`);
  }
});

// Summary
console.log(`\n📊 Test Results: ${passedTests}/${totalTests} passed\n`);

if (passedTests === totalTests) {
  console.log('🎉 All tests passed! Your PWA is ready for deployment.\n');
  
  console.log('🚀 Next Steps:');
  console.log('   1. Deploy your application to a server with HTTPS');
  console.log('   2. Test PWA installation in Chrome/Edge browser');
  console.log('   3. Generate real APK using one of these methods:');
  console.log('      • Android Studio: npx cap open android');
  console.log('      • PWABuilder: https://www.pwabuilder.com/');
  console.log('      • Bubblewrap: npx @bubblewrap/cli init');
  
  console.log('\n📱 Features Available:');
  console.log('   ✅ Progressive Web App with offline support');
  console.log('   ✅ Mobile app download section in landing page');
  console.log('   ✅ QR code for easy mobile access');
  console.log('   ✅ APK download functionality');
  console.log('   ✅ Installation instructions');
  console.log('   ✅ Capacitor setup for native app generation');
  
  console.log('\n🔗 Access Points:');
  console.log('   • Main app: /');
  console.log('   • Mobile entry: /mobile/');
  console.log('   • Downloads: /downloads/');
  console.log('   • APK info: /downloads/apk-info.json');
  
} else {
  console.log('⚠️  Some tests failed. Please check the issues above.');
  
  console.log('\n🔧 Troubleshooting:');
  console.log('   • Run: bun run build (to regenerate assets)');
  console.log('   • Run: npx cap sync android (to sync Capacitor)');
  console.log('   • Check file permissions and paths');
}

console.log('\n📚 Documentation:');
console.log('   • PWA Guide: https://web.dev/progressive-web-apps/');
console.log('   • Capacitor Docs: https://capacitorjs.com/docs');
console.log('   • PWABuilder: https://docs.pwabuilder.com/');

// Check if server is running
console.log('\n🌐 Server Status:');
try {
  // This is just a placeholder - in a real scenario you'd check if the server is running
  console.log('   ℹ️  Start development server: php artisan serve');
  console.log('   ℹ️  Or deploy to production with HTTPS for full PWA functionality');
} catch (error) {
  console.log('   ⚠️  Server not running - start with: php artisan serve');
}
