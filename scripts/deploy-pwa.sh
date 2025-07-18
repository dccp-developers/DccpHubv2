#!/bin/bash

echo "🚀 Deploying DSMS Philex PWA Updates..."

# Change to project directory
cd /home/dccp/projects/dsms-philex-v3

echo "📦 Installing dependencies..."
bun install

echo "🔨 Building assets..."
bun run build

echo "🔄 Clearing Laravel caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "📋 Caching Laravel configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🔧 Syncing Capacitor..."
npx cap sync android

echo "✅ PWA deployment complete!"

echo ""
echo "🌐 Your PWA is now available at:"
echo "   • Main App: https://philexscholar.koamishin.org/"
echo "   • Mobile Entry: https://philexscholar.koamishin.org/mobile"
echo "   • Downloads: https://philexscholar.koamishin.org/downloads"
echo ""
echo "📱 To test PWA installation:"
echo "   1. Open Chrome/Edge on mobile"
echo "   2. Visit: https://philexscholar.koamishin.org"
echo "   3. Look for 'Install' or 'Add to Home Screen'"
echo ""
echo "🔧 To restart FrankenPHP server:"
echo "   ./start-server.sh"
echo ""
echo "📊 To test all endpoints:"
echo "   node scripts/test-production-pwa.js"
