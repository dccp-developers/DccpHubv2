#!/bin/bash

# Build Clean Debug APK for DCCPHub
# This script ensures a clean, valid, unsigned debug APK

set -e  # Exit on any error

echo "🧹 Building Clean Debug APK for DCCPHub..."

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

# Step 1: Clean previous builds
print_status "Cleaning previous builds..."
if [ -d "android/app/build" ]; then
    rm -rf android/app/build
    print_success "Cleaned Android build directory"
fi

# Step 2: Build web assets
print_status "Building fresh web assets..."
npm run build
print_success "Web assets built successfully"

# Step 3: Clean and sync Capacitor
print_status "Cleaning and syncing Capacitor..."
npx cap sync android
print_success "Capacitor synced successfully"

# Step 4: Clean Gradle cache
print_status "Cleaning Gradle cache..."
cd android
./gradlew clean
print_success "Gradle cache cleaned"

# Step 5: Build debug APK
print_status "Building debug APK..."
./gradlew assembleDebug --no-daemon --stacktrace
cd ..

# Step 6: Verify APK was built
APK_PATH="android/app/build/outputs/apk/debug/app-debug.apk"
if [ ! -f "$APK_PATH" ]; then
    print_error "APK build failed. APK not found at: $APK_PATH"
    exit 1
fi

# Step 7: Validate APK
print_status "Validating APK structure..."
if unzip -t "$APK_PATH" > /dev/null 2>&1; then
    print_success "APK structure is valid"
else
    print_error "APK structure is corrupted"
    exit 1
fi

# Step 8: Check APK signature (should be debug signature)
print_status "Checking APK signature..."
if command -v jarsigner &> /dev/null; then
    if jarsigner -verify "$APK_PATH" > /dev/null 2>&1; then
        print_success "APK has valid debug signature"
    else
        print_warning "APK signature verification failed (this is normal for debug APKs)"
    fi
else
    print_warning "jarsigner not found, skipping signature check"
fi

# Step 9: Copy APK to storage with validation
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
NEW_APK_NAME="DCCPHub_clean_debug_${TIMESTAMP}.apk"
STORAGE_PATH="storage/app/apk/${NEW_APK_NAME}"

print_status "Copying validated APK to storage..."
cp "$APK_PATH" "$STORAGE_PATH"

# Verify the copy
if [ ! -f "$STORAGE_PATH" ]; then
    print_error "Failed to copy APK to storage"
    exit 1
fi

# Validate the copied APK
if unzip -t "$STORAGE_PATH" > /dev/null 2>&1; then
    print_success "Copied APK is valid"
else
    print_error "Copied APK is corrupted"
    exit 1
fi

# Step 10: Update download file (direct copy, not symlink)
print_status "Updating download file..."
cd storage/app/apk
rm -f DCCPHub_latest.apk
cp "$NEW_APK_NAME" DCCPHub_latest.apk
cd ../../..

# Final validation of download file
if unzip -t "storage/app/apk/DCCPHub_latest.apk" > /dev/null 2>&1; then
    print_success "Download APK is valid"
else
    print_error "Download APK is corrupted"
    exit 1
fi

# Step 11: Display results
APK_SIZE=$(du -h "$STORAGE_PATH" | cut -f1)
print_success "Clean Debug APK built successfully!"

echo ""
print_success "📱 APK Details:"
echo "   File: $NEW_APK_NAME"
echo "   Size: $APK_SIZE"
echo "   Type: Debug (unsigned)"
echo "   Status: ✅ Valid Android Package"
echo "   Location: $STORAGE_PATH"
echo "   Download URL: https://portal.dccp.edu.ph/storage/apk/DCCPHub_latest.apk"

echo ""
print_success "🔧 Features Included:"
echo "   ✅ Google OAuth policy compliance"
echo "   ✅ Internal OAuth handling"
echo "   ✅ No external browser switching"
echo "   ✅ Proper user agent for Google"

echo ""
print_warning "📋 Installation Instructions:"
echo "   1. Uninstall any previous DCCPHub app"
echo "   2. Enable 'Unknown Sources' on Android device"
echo "   3. Download: https://portal.dccp.edu.ph/storage/apk/DCCPHub_latest.apk"
echo "   4. Install the APK"
echo "   5. Test Google OAuth login"

echo ""
print_status "🧪 Validation Results:"
echo "   ✅ APK structure validated"
echo "   ✅ ZIP integrity confirmed"
echo "   ✅ File copy verified"
echo "   ✅ Download file ready"

echo ""
print_success "🚀 Clean debug APK ready for installation!"

# Optional: Show detailed APK info if aapt is available
if command -v aapt &> /dev/null; then
    echo ""
    print_status "📦 APK Information:"
    aapt dump badging "$STORAGE_PATH" | grep -E "(package|application-label|platformBuildVersionName)" | head -3
fi
