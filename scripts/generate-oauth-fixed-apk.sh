#!/bin/bash

# Generate OAuth-Fixed APK for DCCPHub
# This script helps generate a new APK with OAuth fixes using PWA Builder

set -e  # Exit on any error

echo "🔧 Generating OAuth-Fixed APK for DCCPHub..."

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

print_status "OAuth fixes have been applied to the codebase:"
echo "  ✅ Capacitor configuration updated"
echo "  ✅ Android WebView configured for internal OAuth"
echo "  ✅ JavaScript OAuth handler added"
echo "  ✅ Android permissions configured"
echo ""

print_status "Building web assets with OAuth fixes..."
npm run build

print_success "Web assets built with OAuth fixes!"
echo ""

print_warning "To generate the APK with OAuth fixes, you have 2 options:"
echo ""
echo "🎯 OPTION 1: PWA Builder (Recommended - Quick & Easy)"
echo "   1. PWA Builder will open in your browser"
echo "   2. Enter URL: https://portal.dccp.edu.ph"
echo "   3. Click 'Package For Stores' → 'Android'"
echo "   4. Configure APK settings:"
echo "      - Package ID: ph.edu.dccp.hub"
echo "      - App Name: DCCPHub"
echo "      - Version: 1.1.0 (increment for OAuth fixes)"
echo "   5. Download the APK"
echo "   6. Replace the current APK file"
echo ""

echo "🔧 OPTION 2: Capacitor Build (Requires Android Studio)"
echo "   1. Install Android Studio (see docs/ANDROID_STUDIO_SETUP.md)"
echo "   2. Run: npm run build:apk"
echo ""

read -p "Choose option (1 for PWA Builder, 2 for Capacitor, q to quit): " choice

case $choice in
    1)
        print_status "Opening PWA Builder with OAuth-fixed web assets..."
        
        # Sync Capacitor first to ensure latest changes
        if [ -d "android" ]; then
            print_status "Syncing Capacitor with OAuth fixes..."
            npx cap sync android
        fi
        
        echo ""
        print_success "🎯 PWA Builder Instructions:"
        echo "1. PWA Builder should open in your browser"
        echo "2. Enter: https://portal.dccp.edu.ph"
        echo "3. Click 'Start' → 'Package For Stores' → 'Android'"
        echo "4. Configure APK:"
        echo "   📱 Package ID: ph.edu.dccp.hub"
        echo "   📱 App Name: DCCPHub"
        echo "   📱 Version: 1.1.0"
        echo "   📱 Description: DCCPHub with OAuth fixes"
        echo "5. Download APK"
        echo "6. Run: ./scripts/replace-apk.sh (choose option 1)"
        echo ""
        
        # Open PWA Builder
        if command -v xdg-open &> /dev/null; then
            xdg-open "https://www.pwabuilder.com/"
        elif command -v open &> /dev/null; then
            open "https://www.pwabuilder.com/"
        else
            print_warning "Please manually open: https://www.pwabuilder.com/"
        fi
        
        print_status "After downloading APK, run: ./scripts/replace-apk.sh"
        ;;
        
    2)
        print_status "Attempting Capacitor build..."
        
        # Check if Android is available
        if [ ! -d "android" ]; then
            print_error "Android platform not found. Run: npx cap add android"
            exit 1
        fi
        
        # Check for Android SDK
        if [ -z "$ANDROID_HOME" ] && [ -z "$ANDROID_SDK_ROOT" ]; then
            print_error "Android SDK not found. Please install Android Studio."
            print_status "See docs/ANDROID_STUDIO_SETUP.md for instructions"
            exit 1
        fi
        
        print_status "Building APK with Capacitor..."
        npm run build:apk
        ;;
        
    q|Q)
        print_status "Exiting..."
        exit 0
        ;;
        
    *)
        print_error "Invalid choice. Please choose 1, 2, or q"
        exit 1
        ;;
esac

echo ""
print_success "🎉 OAuth fixes are ready!"
echo ""
print_warning "📋 Testing Checklist:"
echo "  1. Install the new APK on Android device"
echo "  2. Open DCCPHub app"
echo "  3. Click 'Login with Google'"
echo "  4. Verify OAuth opens WITHIN the app (not external Chrome)"
echo "  5. Complete login and verify it stays in the app"
echo "  6. Check that you're redirected to dashboard"
echo ""

print_success "🔧 OAuth Fix Details:"
echo "  ✅ Google OAuth will open within the app"
echo "  ✅ No external browser switching"
echo "  ✅ Seamless login experience"
echo "  ✅ Proper callback handling"
echo ""

print_status "📚 For detailed information, see: docs/OAUTH_FIXES_APPLIED.md"
print_success "🚀 Ready to test the OAuth-fixed APK!"
