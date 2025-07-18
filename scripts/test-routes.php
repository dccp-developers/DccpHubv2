<?php

// Simple route test script
echo "🔍 Testing Laravel Routes...\n\n";

// Check if files exist
$mobileFile = __DIR__ . '/../public/mobile/index.html';
$downloadsFile = __DIR__ . '/../public/downloads/index.html';

echo "📁 File Existence Check:\n";
echo "   Mobile file: " . ($mobileFile && file_exists($mobileFile) ? "✅ EXISTS" : "❌ MISSING") . "\n";
echo "   Downloads file: " . ($downloadsFile && file_exists($downloadsFile) ? "✅ EXISTS" : "❌ MISSING") . "\n\n";

if (file_exists($mobileFile)) {
    echo "📱 Mobile file size: " . filesize($mobileFile) . " bytes\n";
}

if (file_exists($downloadsFile)) {
    echo "📥 Downloads file size: " . filesize($downloadsFile) . " bytes\n";
}

echo "\n🔗 Route URLs to test:\n";
echo "   • https://philexscholar.koamishin.org/mobile\n";
echo "   • https://philexscholar.koamishin.org/downloads\n";

echo "\n💡 If routes are still 404, try:\n";
echo "   1. Restart FrankenPHP server: ./start-server.sh\n";
echo "   2. Clear route cache: php artisan route:clear\n";
echo "   3. Cache routes again: php artisan route:cache\n";
echo "   4. Check if Cloudflare is caching 404 responses\n";

echo "\n🌐 Alternative: Access files directly:\n";
echo "   • https://philexscholar.koamishin.org/mobile/index.html\n";
echo "   • https://philexscholar.koamishin.org/downloads/index.html\n";
