#!/bin/bash

# DCCPHub APK Preparation Script
# This script prepares the APK for download without requiring Android Studio

set -e  # Exit on any error

echo "🚀 Preparing DCCPHub APK for download..."

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

# Step 1: Build the web assets
print_status "Building web assets..."
npm run build
if [ $? -eq 0 ]; then
    print_success "Web assets built successfully"
else
    print_error "Failed to build web assets"
    exit 1
fi

# Step 2: Ensure storage directory exists
print_status "Setting up storage directory..."
mkdir -p storage/app/apk

# Step 3: Check if APK already exists
if [ -f "storage/app/apk/DCCPHub_latest.apk" ]; then
    APK_SIZE=$(du -h "storage/app/apk/DCCPHub_latest.apk" | cut -f1)
    print_success "APK already exists (Size: $APK_SIZE)"
    
    # Ask if user wants to keep existing APK
    echo ""
    echo "Current APK file found. Options:"
    echo "1. Keep existing APK"
    echo "2. Generate new APK with PWA Builder"
    echo "3. Build with Capacitor (requires Android Studio)"
    echo ""
    read -p "Choose option (1-3): " choice
    
    case $choice in
        1)
            print_success "Keeping existing APK"
            ;;
        2)
            print_status "Opening PWA Builder for APK generation..."
            print_status "1. Go to https://www.pwabuilder.com/"
            print_status "2. Enter URL: https://portal.dccp.edu.ph"
            print_status "3. Select Android platform"
            print_status "4. Download generated APK"
            print_status "5. Replace storage/app/apk/DCCPHub_latest.apk with downloaded file"
            
            # Open PWA Builder
            if command -v xdg-open &> /dev/null; then
                xdg-open "https://www.pwabuilder.com/"
            elif command -v open &> /dev/null; then
                open "https://www.pwabuilder.com/"
            else
                print_warning "Please manually open: https://www.pwabuilder.com/"
            fi
            ;;
        3)
            print_status "To build with Capacitor, you need Android Studio installed."
            print_status "See docs/ANDROID_STUDIO_SETUP.md for setup instructions."
            print_status "Then run: npm run build:apk"
            ;;
        *)
            print_warning "Invalid choice. Keeping existing APK."
            ;;
    esac
else
    print_warning "No APK found. Creating placeholder..."
    
    # Create a placeholder APK with instructions
    cat > storage/app/apk/DCCPHub_latest.apk << 'EOF'
# DCCPHub APK Placeholder
# 
# This is a placeholder file. To generate a real APK:
#
# Option 1: PWA Builder (Recommended)
# 1. Go to https://www.pwabuilder.com/
# 2. Enter URL: https://portal.dccp.edu.ph
# 3. Select Android platform and configure
# 4. Download the generated APK
# 5. Replace this file with the downloaded APK
#
# Option 2: Capacitor + Android Studio
# 1. Install Android Studio (see docs/ANDROID_STUDIO_SETUP.md)
# 2. Set up Android SDK and environment variables
# 3. Run: npm run build:apk
#
# Option 3: Manual Capacitor Build
# 1. npm run build
# 2. npx cap copy android
# 3. npx cap sync android
# 4. cd android && ./gradlew assembleDebug
# 5. Copy android/app/build/outputs/apk/debug/app-debug.apk to this location
#
# For more information, see:
# - docs/ANDROID_STUDIO_SETUP.md
# - docs/APK_GENERATION_GUIDE.md
EOF

    print_warning "Placeholder APK created. See file contents for generation instructions."
fi

# Step 4: Ensure symlink exists for web access
print_status "Setting up web access..."
if [ ! -L "public/storage/apk" ]; then
    ln -sf ../../storage/app/apk public/storage/apk
    print_success "APK symlink created"
else
    print_success "APK symlink already exists"
fi

# Step 5: Copy assets to Capacitor if available
if [ -d "android" ]; then
    print_status "Updating Capacitor Android project..."
    npx cap copy android
    npx cap sync android
    print_success "Capacitor project updated"
else
    print_warning "Capacitor Android project not found. Run 'npx cap add android' first."
fi

# Step 6: Display final information
echo ""
print_success "🎉 APK preparation completed!"
echo ""
echo "📱 APK Location: storage/app/apk/DCCPHub_latest.apk"
echo "🌐 Download URL: https://portal.dccp.edu.ph/storage/apk/DCCPHub_latest.apk"

if [ -f "storage/app/apk/DCCPHub_latest.apk" ]; then
    APK_SIZE=$(du -h "storage/app/apk/DCCPHub_latest.apk" | cut -f1)
    echo "📦 APK Size: $APK_SIZE"
fi

echo ""
echo "🔧 To generate a new APK:"
echo "   Option 1: PWA Builder - https://www.pwabuilder.com/"
echo "   Option 2: Android Studio - see docs/ANDROID_STUDIO_SETUP.md"
echo ""
echo "📱 Mobile App Status component will show download options automatically"
echo "🌐 Users can access the download from the hero section on the landing page"
echo ""

print_success "Ready for deployment! 🚀"
