#!/bin/bash

# DCCPHub APK Replacement Script
# This script helps you replace the placeholder APK with a real one

set -e  # Exit on any error

echo "🔄 DCCPHub APK Replacement Tool"

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

# Check current APK status
APK_PATH="storage/app/apk/DCCPHub_latest.apk"

if [ -f "$APK_PATH" ]; then
    APK_SIZE=$(du -h "$APK_PATH" | cut -f1)
    print_status "Current APK: $APK_PATH (Size: $APK_SIZE)"
    
    # Check if it's the placeholder
    if grep -q "placeholder APK file" "$APK_PATH" 2>/dev/null; then
        print_warning "Current APK is a placeholder file (not installable)"
        IS_PLACEHOLDER=true
    else
        print_success "Current APK appears to be a real APK file"
        IS_PLACEHOLDER=false
    fi
else
    print_error "No APK file found at $APK_PATH"
    exit 1
fi

echo ""
echo "📱 APK Replacement Options:"
echo "1. Replace with downloaded APK file"
echo "2. Generate new APK with PWA Builder"
echo "3. Build APK with Capacitor (requires Android Studio)"
echo "4. Keep current APK"
echo ""

read -p "Choose option (1-4): " choice

case $choice in
    1)
        echo ""
        print_status "Please provide the path to your downloaded APK file:"
        read -p "APK file path: " NEW_APK_PATH
        
        if [ ! -f "$NEW_APK_PATH" ]; then
            print_error "File not found: $NEW_APK_PATH"
            exit 1
        fi
        
        # Check if it's actually an APK file
        if file "$NEW_APK_PATH" | grep -q "Android"; then
            print_success "Valid Android APK detected"
        else
            print_warning "File may not be a valid APK. Continue anyway? (y/n)"
            read -p "> " confirm
            if [ "$confirm" != "y" ]; then
                print_status "Operation cancelled"
                exit 0
            fi
        fi
        
        # Backup current APK
        if [ "$IS_PLACEHOLDER" = false ]; then
            BACKUP_NAME="storage/app/apk/DCCPHub_backup_$(date +%Y%m%d_%H%M%S).apk"
            cp "$APK_PATH" "$BACKUP_NAME"
            print_status "Current APK backed up to: $BACKUP_NAME"
        fi
        
        # Replace APK
        cp "$NEW_APK_PATH" "$APK_PATH"
        NEW_SIZE=$(du -h "$APK_PATH" | cut -f1)
        print_success "APK replaced successfully! New size: $NEW_SIZE"
        ;;
        
    2)
        print_status "Opening PWA Builder for APK generation..."
        echo ""
        echo "📋 Instructions:"
        echo "1. PWA Builder should open in your browser"
        echo "2. Enter URL: https://portal.dccp.edu.ph"
        echo "3. Click 'Start' or 'Analyze'"
        echo "4. Select 'Package For Stores' → 'Android'"
        echo "5. Configure:"
        echo "   - Package ID: ph.edu.dccp.hub"
        echo "   - App Name: DCCPHub"
        echo "   - Version: 1.0.0"
        echo "6. Download the APK"
        echo "7. Run this script again with option 1 to replace"
        echo ""
        
        # Open PWA Builder
        if command -v xdg-open &> /dev/null; then
            xdg-open "https://www.pwabuilder.com/"
        elif command -v open &> /dev/null; then
            open "https://www.pwabuilder.com/"
        else
            print_warning "Please manually open: https://www.pwabuilder.com/"
        fi
        
        print_status "After downloading, run: ./scripts/replace-apk.sh"
        ;;
        
    3)
        print_status "Building APK with Capacitor..."
        
        # Check if Android Studio is available
        if ! command -v gradle &> /dev/null && [ ! -d "android" ]; then
            print_error "Android Studio/Gradle not found or Android platform not added"
            print_status "Please see docs/ANDROID_STUDIO_SETUP.md for setup instructions"
            exit 1
        fi
        
        print_status "Running Capacitor build..."
        npm run build:apk
        ;;
        
    4)
        print_status "Keeping current APK"
        ;;
        
    *)
        print_error "Invalid choice"
        exit 1
        ;;
esac

# Final status
echo ""
if [ -f "$APK_PATH" ]; then
    FINAL_SIZE=$(du -h "$APK_PATH" | cut -f1)
    print_success "📱 Final APK Status:"
    echo "   Location: $APK_PATH"
    echo "   Size: $FINAL_SIZE"
    echo "   Download URL: https://portal.dccp.edu.ph/storage/apk/DCCPHub_latest.apk"
    
    # Check if it's still a placeholder
    if grep -q "placeholder APK file" "$APK_PATH" 2>/dev/null; then
        print_warning "⚠️  APK is still a placeholder - not installable on Android devices"
        echo "   Use option 1 or 2 to replace with a real APK"
    else
        print_success "✅ APK appears to be installable"
        echo "   Test by downloading on an Android device"
    fi
fi

echo ""
print_success "🚀 APK management completed!"
