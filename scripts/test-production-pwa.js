#!/usr/bin/env node

import https from 'https';
import { URL } from 'url';

const DOMAIN = 'philexscholar.koamishin.org';
const BASE_URL = `https://${DOMAIN}`;

console.log('🌐 Testing DSMS Philex PWA on Production\n');
console.log(`🔗 Domain: ${DOMAIN}`);
console.log(`🔗 Base URL: ${BASE_URL}\n`);

// Test endpoints
const endpoints = [
  { path: '/', name: 'Main Application' },
  { path: '/mobile/', name: 'Mobile Entry Point' },
  { path: '/downloads/', name: 'Download Page' },
  { path: '/downloads/apk-info.json', name: 'APK Info API' },
  { path: '/site.webmanifest', name: 'PWA Manifest' },
  { path: '/android-chrome-192x192.png', name: 'App Icon 192x192' },
  { path: '/android-chrome-512x512.png', name: 'App Icon 512x512' }
];

async function testEndpoint(url, name) {
  return new Promise((resolve) => {
    const urlObj = new URL(url);
    
    const options = {
      hostname: urlObj.hostname,
      port: urlObj.port || 443,
      path: urlObj.pathname,
      method: 'HEAD',
      timeout: 10000,
      headers: {
        'User-Agent': 'PWA-Test-Bot/1.0'
      }
    };

    const req = https.request(options, (res) => {
      const success = res.statusCode >= 200 && res.statusCode < 400;
      resolve({
        success,
        statusCode: res.statusCode,
        contentType: res.headers['content-type'],
        name,
        url
      });
    });

    req.on('error', (err) => {
      resolve({
        success: false,
        error: err.message,
        name,
        url
      });
    });

    req.on('timeout', () => {
      req.destroy();
      resolve({
        success: false,
        error: 'Timeout',
        name,
        url
      });
    });

    req.end();
  });
}

async function testPWAManifest() {
  return new Promise((resolve) => {
    const url = `${BASE_URL}/site.webmanifest`;
    
    https.get(url, (res) => {
      let data = '';
      
      res.on('data', (chunk) => {
        data += chunk;
      });
      
      res.on('end', () => {
        try {
          const manifest = JSON.parse(data);
          const isValid = manifest.name && manifest.icons && manifest.display;
          
          resolve({
            success: isValid,
            manifest,
            issues: []
          });
        } catch (error) {
          resolve({
            success: false,
            error: 'Invalid JSON',
            manifest: null
          });
        }
      });
    }).on('error', (err) => {
      resolve({
        success: false,
        error: err.message,
        manifest: null
      });
    });
  });
}

async function runTests() {
  console.log('🔍 Testing Endpoints:\n');
  
  const results = [];
  
  for (const endpoint of endpoints) {
    const url = `${BASE_URL}${endpoint.path}`;
    const result = await testEndpoint(url, endpoint.name);
    results.push(result);
    
    if (result.success) {
      console.log(`   ✅ ${endpoint.name} (${result.statusCode})`);
    } else {
      console.log(`   ❌ ${endpoint.name} - ${result.error || result.statusCode}`);
    }
  }
  
  console.log('\n📋 Testing PWA Manifest:\n');
  
  const manifestTest = await testPWAManifest();
  
  if (manifestTest.success) {
    console.log('   ✅ PWA Manifest is valid');
    console.log(`   📱 App Name: ${manifestTest.manifest.name}`);
    console.log(`   📱 Short Name: ${manifestTest.manifest.short_name}`);
    console.log(`   📱 Display Mode: ${manifestTest.manifest.display}`);
    console.log(`   📱 Theme Color: ${manifestTest.manifest.theme_color}`);
    console.log(`   📱 Icons: ${manifestTest.manifest.icons?.length || 0} configured`);
  } else {
    console.log(`   ❌ PWA Manifest failed: ${manifestTest.error}`);
  }
  
  // Summary
  const successCount = results.filter(r => r.success).length;
  const totalCount = results.length;
  
  console.log(`\n📊 Test Results: ${successCount}/${totalCount} endpoints accessible\n`);
  
  if (successCount === totalCount && manifestTest.success) {
    console.log('🎉 All tests passed! Your PWA is ready for mobile installation.\n');
    
    console.log('📱 Installation Instructions:');
    console.log('   1. Open Chrome/Edge on mobile device');
    console.log(`   2. Visit: ${BASE_URL}`);
    console.log('   3. Look for "Install" or "Add to Home Screen" option');
    console.log('   4. Follow the installation prompts');
    
    console.log('\n📱 APK Generation:');
    console.log('   1. Visit: https://www.pwabuilder.com/');
    console.log(`   2. Enter URL: ${BASE_URL}`);
    console.log('   3. Download the generated APK');
    
    console.log('\n🔗 Quick Access:');
    console.log(`   • Main App: ${BASE_URL}`);
    console.log(`   • Mobile Entry: ${BASE_URL}/mobile/`);
    console.log(`   • Downloads: ${BASE_URL}/downloads/`);
    
  } else {
    console.log('⚠️  Some tests failed. Please check the issues above.');
    
    console.log('\n🔧 Common Issues:');
    console.log('   • Ensure Cloudflare tunnel is running');
    console.log('   • Check FrankenPHP server is started');
    console.log('   • Verify domain DNS is properly configured');
    console.log('   • Ensure HTTPS is working correctly');
  }
  
  console.log('\n🌐 Cloudflare Tunnel Status:');
  console.log('   ℹ️  Make sure cloudflared is running');
  console.log('   ℹ️  Check tunnel configuration for proper routing');
  console.log('   ℹ️  Verify FrankenPHP is listening on correct port');
}

// Run the tests
runTests().catch(console.error);
