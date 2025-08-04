#!/bin/bash

# DCCPHub Capacitor APK Build Script
# This script builds the APK using Capacitor and Android Studio/Gradle

set -e  # Exit on any error

echo "🚀 Starting DCCPHub Capacitor APK Build Process with OAuth Fixes..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(/usr/bin/dirname "$0")/.." && pwd)"
PROJECT_ROOT="$SCRIPT_DIR"
APK_OUTPUT_DIR="$PROJECT_ROOT/storage/app/apk"
TIMESTAMP=$(/bin/date +"%Y%m%d_%H%M%S")

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

# Function to copy custom app icons
copy_app_icons() {
    print_status "Copying custom app icons from public folder..."

    # Source images from public folder
    ICON_192="$PROJECT_ROOT/public/android-chrome-192x192.png"
    ICON_512="$PROJECT_ROOT/public/android-chrome-512x512.png"

    # Check if source icons exist
    if [ ! -f "$ICON_192" ] || [ ! -f "$ICON_512" ]; then
        print_warning "Android Chrome icons not found in public folder. Using default Capacitor icons."
        return 0
    fi

    # Android mipmap directories
    ANDROID_RES="$PROJECT_ROOT/android/app/src/main/res"

    # Create temporary directory for resized icons
    TEMP_ICONS_DIR="$PROJECT_ROOT/temp_icons"
    /bin/mkdir -p "$TEMP_ICONS_DIR"

    # Check if ImageMagick is available for resizing
    if command -v convert &> /dev/null; then
        print_status "Using ImageMagick to resize icons..."

        # Generate different sizes for Android
        convert "$ICON_512" -resize 48x48 "$TEMP_ICONS_DIR/ic_launcher_mdpi.png"
        convert "$ICON_512" -resize 72x72 "$TEMP_ICONS_DIR/ic_launcher_hdpi.png"
        convert "$ICON_512" -resize 96x96 "$TEMP_ICONS_DIR/ic_launcher_xhdpi.png"
        convert "$ICON_512" -resize 144x144 "$TEMP_ICONS_DIR/ic_launcher_xxhdpi.png"
        convert "$ICON_512" -resize 192x192 "$TEMP_ICONS_DIR/ic_launcher_xxxhdpi.png"

        # Copy resized icons to Android project
        cp "$TEMP_ICONS_DIR/ic_launcher_mdpi.png" "$ANDROID_RES/mipmap-mdpi/ic_launcher.png"
        cp "$TEMP_ICONS_DIR/ic_launcher_mdpi.png" "$ANDROID_RES/mipmap-mdpi/ic_launcher_round.png"
        cp "$TEMP_ICONS_DIR/ic_launcher_mdpi.png" "$ANDROID_RES/mipmap-mdpi/ic_launcher_foreground.png"

        cp "$TEMP_ICONS_DIR/ic_launcher_hdpi.png" "$ANDROID_RES/mipmap-hdpi/ic_launcher.png"
        cp "$TEMP_ICONS_DIR/ic_launcher_hdpi.png" "$ANDROID_RES/mipmap-hdpi/ic_launcher_round.png"
        cp "$TEMP_ICONS_DIR/ic_launcher_hdpi.png" "$ANDROID_RES/mipmap-hdpi/ic_launcher_foreground.png"

        cp "$TEMP_ICONS_DIR/ic_launcher_xhdpi.png" "$ANDROID_RES/mipmap-xhdpi/ic_launcher.png"
        cp "$TEMP_ICONS_DIR/ic_launcher_xhdpi.png" "$ANDROID_RES/mipmap-xhdpi/ic_launcher_round.png"
        cp "$TEMP_ICONS_DIR/ic_launcher_xhdpi.png" "$ANDROID_RES/mipmap-xhdpi/ic_launcher_foreground.png"

        cp "$TEMP_ICONS_DIR/ic_launcher_xxhdpi.png" "$ANDROID_RES/mipmap-xxhdpi/ic_launcher.png"
        cp "$TEMP_ICONS_DIR/ic_launcher_xxhdpi.png" "$ANDROID_RES/mipmap-xxhdpi/ic_launcher_round.png"
        cp "$TEMP_ICONS_DIR/ic_launcher_xxhdpi.png" "$ANDROID_RES/mipmap-xxhdpi/ic_launcher_foreground.png"

        cp "$TEMP_ICONS_DIR/ic_launcher_xxxhdpi.png" "$ANDROID_RES/mipmap-xxxhdpi/ic_launcher.png"
        cp "$TEMP_ICONS_DIR/ic_launcher_xxxhdpi.png" "$ANDROID_RES/mipmap-xxxhdpi/ic_launcher_round.png"
        cp "$TEMP_ICONS_DIR/ic_launcher_xxxhdpi.png" "$ANDROID_RES/mipmap-xxxhdpi/ic_launcher_foreground.png"

        print_success "Custom app icons copied successfully"
    else
        print_warning "ImageMagick not found. Copying 192x192 icon to all sizes (may not be optimal)."

        # Fallback: copy 192x192 to all mipmap directories
        cp "$ICON_192" "$ANDROID_RES/mipmap-mdpi/ic_launcher.png"
        cp "$ICON_192" "$ANDROID_RES/mipmap-mdpi/ic_launcher_round.png"
        cp "$ICON_192" "$ANDROID_RES/mipmap-mdpi/ic_launcher_foreground.png"

        cp "$ICON_192" "$ANDROID_RES/mipmap-hdpi/ic_launcher.png"
        cp "$ICON_192" "$ANDROID_RES/mipmap-hdpi/ic_launcher_round.png"
        cp "$ICON_192" "$ANDROID_RES/mipmap-hdpi/ic_launcher_foreground.png"

        cp "$ICON_192" "$ANDROID_RES/mipmap-xhdpi/ic_launcher.png"
        cp "$ICON_192" "$ANDROID_RES/mipmap-xhdpi/ic_launcher_round.png"
        cp "$ICON_192" "$ANDROID_RES/mipmap-xhdpi/ic_launcher_foreground.png"

        cp "$ICON_192" "$ANDROID_RES/mipmap-xxhdpi/ic_launcher.png"
        cp "$ICON_192" "$ANDROID_RES/mipmap-xxhdpi/ic_launcher_round.png"
        cp "$ICON_192" "$ANDROID_RES/mipmap-xxhdpi/ic_launcher_foreground.png"

        cp "$ICON_192" "$ANDROID_RES/mipmap-xxxhdpi/ic_launcher.png"
        cp "$ICON_192" "$ANDROID_RES/mipmap-xxxhdpi/ic_launcher_round.png"
        cp "$ICON_192" "$ANDROID_RES/mipmap-xxxhdpi/ic_launcher_foreground.png"

        print_success "Custom app icons copied (using 192x192 for all sizes)"
    fi

    # Clean up temporary directory
    /bin/rm -rf "$TEMP_ICONS_DIR"
}

# Function to check prerequisites
check_prerequisites() {
    print_status "Checking prerequisites..."

    # Check if we're in the correct directory
    if [ ! -f "package.json" ]; then
        print_error "package.json not found. Please run this script from the project root."
        exit 1
    fi

    # Check if Capacitor is installed
    if ! command -v npx &> /dev/null; then
        print_error "npx not found. Please install Node.js and npm."
        exit 1
    fi

    # Check if Node.js is available
    if ! command -v node &> /dev/null; then
        print_error "Node.js not found. Please install Node.js."
        exit 1
    fi

    # Check if Capacitor config exists
    if [ ! -f "capacitor.config.ts" ]; then
        print_error "capacitor.config.ts not found. Please ensure Capacitor is properly configured."
        exit 1
    fi

    # Check if Android project exists
    if [ ! -d "android" ]; then
        print_error "Android project directory not found. Please run 'npx cap add android' first."
        exit 1
    fi

    # Create APK output directory
    /bin/mkdir -p "$APK_OUTPUT_DIR"

    print_success "Prerequisites check completed"
}

# Main build function
build_apk() {
    print_status "Starting APK build process..."

    # Step 1: Build the web assets
    print_status "Building web assets..."
    if npm run build; then
        print_success "Web assets built successfully"
    else
        print_error "Failed to build web assets"
        exit 1
    fi

    # Step 2: Copy assets to Android project
    print_status "Copying assets to Android project..."
    if npx cap copy android; then
        print_success "Assets copied to Android project"
    else
        print_error "Failed to copy assets to Android project"
        exit 1
    fi

    # Step 3: Copy custom app icons
    print_status "Copying custom app icons..."
    copy_app_icons

    # Step 4: Sync Capacitor
    print_status "Syncing Capacitor..."
    if npx cap sync android; then
        print_success "Capacitor synced successfully"
    else
        print_error "Failed to sync Capacitor"
        exit 1
    fi

    # Step 5: Build APK using Gradle
    print_status "Building APK with Gradle..."
    cd android

    # Check if gradlew exists
    if [ ! -f "./gradlew" ]; then
        print_error "gradlew not found in android directory"
        exit 1
    fi

    # Make gradlew executable
    /bin/chmod +x ./gradlew

    # Clean previous builds
    print_status "Cleaning previous builds..."
    ./gradlew clean

    # Build debug APK
    print_status "Building debug APK..."
    if ./gradlew assembleDebug; then
        print_success "Debug APK built successfully"
    else
        print_error "Failed to build debug APK"
        print_error "Check the Gradle output above for specific errors"
        cd ..
        exit 1
    fi

    # Go back to project root
    cd ..
}

# Function to copy and organize APK files
copy_apk_files() {
    print_status "Copying APK to storage directory..."

    # Find the built APK
    APK_PATH="android/app/build/outputs/apk/debug/app-debug.apk"

    if [ -f "$APK_PATH" ]; then
        # Create timestamped filename
        APK_FILENAME="DCCPHub_${TIMESTAMP}.apk"
        LATEST_APK_FILENAME="DCCPHub_latest.apk"

        # Copy APK with timestamp
        cp "$APK_PATH" "$APK_OUTPUT_DIR/$APK_FILENAME"
        print_success "APK copied to $APK_OUTPUT_DIR/$APK_FILENAME"

        # Create/update latest symlink
        cd "$APK_OUTPUT_DIR"
        ln -sf "$APK_FILENAME" "$LATEST_APK_FILENAME"
        cd "$PROJECT_ROOT"
        print_success "Latest APK symlink updated"

        # Get APK size
        APK_SIZE=$(du -h "$APK_OUTPUT_DIR/$APK_FILENAME" | cut -f1)

        # Set proper permissions
        /bin/chmod 644 "$APK_OUTPUT_DIR/$APK_FILENAME"

        return 0
    else
        print_error "APK not found at expected location: $APK_PATH"
        print_error "Available files in android/app/build/outputs/apk/:"
        find android/app/build/outputs/apk/ -name "*.apk" 2>/dev/null || echo "No APK files found"
        return 1
    fi
}

# Function to display build summary
display_summary() {
    echo ""
    print_success "✅ APK Build Complete!"
    echo ""
    echo "📱 APK Files:"
    ls -la "$APK_OUTPUT_DIR"/*.apk 2>/dev/null || echo "No APK files found"
    echo ""
    echo "📦 APK Size: $APK_SIZE"
    echo "🌐 Download URL: https://portal.dccp.edu.ph/apk/download/DCCPHub_latest.apk"
    echo "📁 Storage Path: $APK_OUTPUT_DIR"
    echo ""
    echo "🔧 Build Information:"
    echo "   • Build Type: Debug"
    echo "   • Timestamp: $TIMESTAMP"
    echo "   • Platform: Android"
    echo ""
    echo "📋 Next Steps:"
    echo "   1. Test the APK on an Android device"
    echo "   2. For release build, run: cd android && ./gradlew assembleRelease"
    echo "   3. Download from: https://portal.dccp.edu.ph/download/apk"
    echo "   4. To create GitHub release, run: git tag v1.0.x && git push origin v1.0.x"
    echo ""
}

# Main execution
main() {
    check_prerequisites
    build_apk

    if copy_apk_files; then
        display_summary
        print_success "🎉 DCCPHub APK build process completed successfully!"
        exit 0
    else
        print_error "APK build completed but file copy failed"
        exit 1
    fi
}

# Run main function
main
