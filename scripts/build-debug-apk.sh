#!/bin/bash

# Build Debug APK for DCCPHub with OAuth Fixes
# This script builds a proper unsigned debug APK that can be installed on Android devices

set -e  # Exit on any error

echo "🔧 Building DCCPHub Debug APK with OAuth Fixes..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if we're in the correct directory
if [ ! -f "package.json" ]; then
    print_error "package.json not found. Please run this script from the project root."
    exit 1
fi

# Check if Android platform exists
if [ ! -d "android" ]; then
    print_error "Android platform not found. Run: npx cap add android"
    exit 1
fi

# Step 1: Build web assets
print_status "Building web assets with OAuth fixes..."
npm run build
print_success "Web assets built successfully"

# Step 2: Sync Capacitor
print_status "Syncing Capacitor with latest changes..."
npx cap sync android
print_success "Capacitor synced successfully"

# Step 3: Build debug APK
print_status "Building debug APK with Gradle..."
cd android
./gradlew assembleDebug --stacktrace
cd ..

# Check if APK was built
APK_PATH="android/app/build/outputs/apk/debug/app-debug.apk"
if [ ! -f "$APK_PATH" ]; then
    print_error "APK build failed. APK not found at: $APK_PATH"
    exit 1
fi

# Step 4: Copy APK to storage
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
NEW_APK_NAME="DCCPHub_debug_${TIMESTAMP}.apk"
STORAGE_PATH="storage/app/apk/${NEW_APK_NAME}"

print_status "Copying APK to storage directory..."
cp "$APK_PATH" "$STORAGE_PATH"

# Step 5: Update symlink
print_status "Updating download symlink..."
cd storage/app/apk
rm -f DCCPHub_latest.apk
ln -sf "$NEW_APK_NAME" DCCPHub_latest.apk
cd ../../..

# Step 6: Verify APK
APK_SIZE=$(du -h "$STORAGE_PATH" | cut -f1)
print_success "APK built and deployed successfully!"

echo ""
print_success "📱 APK Details:"
echo "   File: $NEW_APK_NAME"
echo "   Size: $APK_SIZE"
echo "   Type: Debug (unsigned)"
echo "   Location: $STORAGE_PATH"
echo "   Download URL: https://portal.dccp.edu.ph/storage/apk/DCCPHub_latest.apk"

echo ""
print_success "🔧 OAuth Fixes Included:"
echo "   ✅ Internal OAuth handling"
echo "   ✅ No external browser switching"
echo "   ✅ Seamless Google login"
echo "   ✅ Proper callback handling"

echo ""
print_warning "📋 Installation Instructions:"
echo "   1. Enable 'Unknown Sources' on your Android device"
echo "   2. Download: https://portal.dccp.edu.ph/storage/apk/DCCPHub_latest.apk"
echo "   3. Install the APK"
echo "   4. Test Google OAuth login"

echo ""
print_status "🧪 Testing Checklist:"
echo "   □ Install APK on Android device"
echo "   □ Open DCCPHub app"
echo "   □ Click 'Login with Google'"
echo "   □ Verify OAuth opens WITHIN the app"
echo "   □ Complete login without external browser"
echo "   □ Check redirect to dashboard"

echo ""
print_success "🚀 Debug APK ready for testing!"

# Optional: Show APK info
if command -v aapt &> /dev/null; then
    echo ""
    print_status "📦 APK Information:"
    aapt dump badging "$STORAGE_PATH" | grep -E "(package|application-label|platformBuildVersionName)"
fi
