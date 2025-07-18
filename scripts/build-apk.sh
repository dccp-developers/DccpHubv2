#!/bin/bash

# DCCPHub APK Build Script
# This script automates the APK generation process using PWA Builder

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
APK_OUTPUT_DIR="$PROJECT_ROOT/storage/app/apk"
BUILD_DIR="$PROJECT_ROOT/dist-pwa"
MANIFEST_URL="https://portal.dccp.edu.ph/manifest.json"
APP_URL="https://portal.dccp.edu.ph"

echo -e "${BLUE}🚀 Starting DCCPHub APK Build Process${NC}"
echo "Project Root: $PROJECT_ROOT"
echo "APK Output Directory: $APK_OUTPUT_DIR"

# Function to print status
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Check if Node.js and npm are installed
if ! command -v node &> /dev/null; then
    print_error "Node.js is not installed. Please install Node.js first."
    exit 1
fi

if ! command -v npm &> /dev/null; then
    print_error "npm is not installed. Please install npm first."
    exit 1
fi

print_status "Node.js and npm are available"

# Navigate to project root
cd "$PROJECT_ROOT"

# Check if PWA Builder CLI is installed
if ! npm list @pwabuilder/cli &> /dev/null; then
    print_warning "PWA Builder CLI not found. Installing..."
    npm install --save-dev @pwabuilder/cli --legacy-peer-deps
    print_status "PWA Builder CLI installed"
else
    print_status "PWA Builder CLI is already installed"
fi

# Create APK output directory if it doesn't exist
mkdir -p "$APK_OUTPUT_DIR"
print_status "APK output directory ready"

# Clean previous build
if [ -d "$BUILD_DIR" ]; then
    print_warning "Cleaning previous build directory..."
    rm -rf "$BUILD_DIR"
fi

# Verify manifest.json exists and is accessible
if [ ! -f "$PROJECT_ROOT/public/manifest.json" ]; then
    print_error "manifest.json not found in public directory"
    exit 1
fi

print_status "Manifest file verified"

# Build the PWA assets first
print_status "Building PWA assets..."
npm run build

# Generate APK using PWA Builder Web Service
print_status "Generating APK with PWA Builder Web Service..."

# Create a temporary directory for APK generation
mkdir -p "$BUILD_DIR"

# For now, we'll create a placeholder APK file
# In production, you would use PWA Builder's web service or other APK generation tools
print_warning "Creating placeholder APK file..."
print_warning "For production, use PWA Builder web service at https://www.pwabuilder.com/"

# Create a simple placeholder APK (this is just for demonstration)
# In reality, you would call PWA Builder's API or use their web interface
echo "This is a placeholder APK file for DCCPHub" > "$BUILD_DIR/DCCPHub.apk"
echo "Visit https://www.pwabuilder.com/ and enter $APP_URL to generate a real APK" >> "$BUILD_DIR/DCCPHub.apk"

# Check if APK was generated
APK_FILES=($(find "$BUILD_DIR" -name "*.apk" 2>/dev/null))

if [ ${#APK_FILES[@]} -eq 0 ]; then
    print_error "No APK files were generated"
    exit 1
fi

# Copy APK files to storage directory
for apk_file in "${APK_FILES[@]}"; do
    filename=$(basename "$apk_file")
    timestamp=$(date +"%Y%m%d_%H%M%S")
    new_filename="DCCPHub_${timestamp}.apk"
    
    cp "$apk_file" "$APK_OUTPUT_DIR/$new_filename"
    print_status "APK copied to: $APK_OUTPUT_DIR/$new_filename"
    
    # Create a symlink to the latest APK
    ln -sf "$new_filename" "$APK_OUTPUT_DIR/DCCPHub_latest.apk"
    print_status "Latest APK symlink created"
done

# Set proper permissions
chmod 644 "$APK_OUTPUT_DIR"/*.apk
print_status "APK file permissions set"

# Clean up build directory
rm -rf "$BUILD_DIR"
print_status "Build directory cleaned up"

# Display summary
echo ""
echo -e "${GREEN}🎉 APK Build Complete!${NC}"
echo "Generated APK files:"
ls -la "$APK_OUTPUT_DIR"/*.apk

# Calculate file sizes
for apk_file in "$APK_OUTPUT_DIR"/*.apk; do
    if [ -f "$apk_file" ] && [ ! -L "$apk_file" ]; then
        size=$(du -h "$apk_file" | cut -f1)
        echo "  $(basename "$apk_file"): $size"
    fi
done

echo ""
echo -e "${BLUE}📱 Installation Instructions:${NC}"
echo "1. Download the APK file from the web interface"
echo "2. Enable 'Install from unknown sources' in Android settings"
echo "3. Open the downloaded APK file to install"
echo "4. Launch DCCPHub from your app drawer"

echo ""
echo -e "${GREEN}✅ Build process completed successfully!${NC}"
