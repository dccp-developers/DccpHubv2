#!/bin/bash

# Test APK Download Script
# This script tests the APK download functionality to ensure it works properly

set -e  # Exit on any error

echo "🧪 Testing APK Download Functionality..."

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

# Test 1: Check if APK file exists
print_status "Testing APK file existence..."
APK_PATH="storage/app/apk/DCCPHub_latest.apk"

if [ -f "$APK_PATH" ]; then
    APK_SIZE=$(du -h "$APK_PATH" | cut -f1)
    print_success "APK file exists: $APK_PATH (Size: $APK_SIZE)"
else
    print_error "APK file not found: $APK_PATH"
    exit 1
fi

# Test 2: Validate APK structure
print_status "Validating APK structure..."
if unzip -t "$APK_PATH" > /dev/null 2>&1; then
    print_success "APK structure is valid"
else
    print_error "APK structure is corrupted"
    exit 1
fi

# Test 3: Check file type
print_status "Checking file type..."
FILE_TYPE=$(file "$APK_PATH")
if echo "$FILE_TYPE" | grep -q "Android package"; then
    print_success "File type is correct: Android package"
else
    print_warning "File type check: $FILE_TYPE"
fi

# Test 4: Check symlink in public directory
print_status "Checking public symlink..."
if [ -L "public/storage/apk" ]; then
    print_success "Public symlink exists"
    if [ -f "public/storage/apk/DCCPHub_latest.apk" ]; then
        print_success "APK accessible via public symlink"
    else
        print_error "APK not accessible via public symlink"
    fi
else
    print_error "Public symlink missing"
    print_status "Creating symlink..."
    mkdir -p public/storage
    ln -sf ../../storage/app/apk public/storage/apk
    print_success "Symlink created"
fi

# Test 5: Test download with curl (if available)
if command -v curl &> /dev/null; then
    print_status "Testing download with curl..."
    
    # Test direct file access
    RESPONSE=$(curl -s -I "http://localhost/storage/apk/DCCPHub_latest.apk" 2>/dev/null || echo "FAILED")
    
    if echo "$RESPONSE" | grep -q "200 OK"; then
        print_success "Direct file download works"
        
        # Check content type
        if echo "$RESPONSE" | grep -q "application/vnd.android.package-archive"; then
            print_success "Correct MIME type served"
        else
            print_warning "MIME type may not be correct"
        fi
    else
        print_warning "Direct file download test failed (this is normal if server is not running locally)"
    fi
else
    print_warning "curl not available, skipping download test"
fi

# Test 6: Check Laravel route
print_status "Checking Laravel route configuration..."
if grep -q "downloadLatestAPK" routes/web.php; then
    print_success "Laravel download route configured"
else
    print_error "Laravel download route not found"
fi

# Test 7: Check .htaccess configuration
print_status "Checking .htaccess configuration..."
if grep -q "application/vnd.android.package-archive" public/.htaccess; then
    print_success ".htaccess APK MIME type configured"
else
    print_warning ".htaccess APK MIME type not configured"
fi

# Test 8: File permissions
print_status "Checking file permissions..."
if [ -r "$APK_PATH" ]; then
    print_success "APK file is readable"
else
    print_error "APK file is not readable"
fi

# Test 9: Check APK size
print_status "Checking APK size..."
APK_SIZE_BYTES=$(stat -f%z "$APK_PATH" 2>/dev/null || stat -c%s "$APK_PATH" 2>/dev/null || echo "0")

if [ "$APK_SIZE_BYTES" -gt 5000000 ]; then  # Greater than 5MB
    print_success "APK size is reasonable: $(du -h "$APK_PATH" | cut -f1)"
else
    print_error "APK size is too small: $(du -h "$APK_PATH" | cut -f1) - may be corrupted"
fi

# Summary
echo ""
print_success "🎯 APK Download Test Summary:"
echo "   📱 APK File: $APK_PATH"
echo "   📦 Size: $(du -h "$APK_PATH" | cut -f1)"
echo "   🔗 Public URL: https://portal.dccp.edu.ph/storage/apk/DCCPHub_latest.apk"
echo "   🌐 Laravel Route: /storage/apk/DCCPHub_latest.apk"

echo ""
print_status "📋 Troubleshooting Tips:"
echo "   1. If download fails, try the Laravel route: /apk/download/DCCPHub_latest.apk"
echo "   2. Check server logs for any errors"
echo "   3. Ensure web server has read permissions on storage directory"
echo "   4. Verify .htaccess rules are being processed"

echo ""
print_success "🚀 APK download test completed!"

# Optional: Create a test download
read -p "Do you want to create a test download? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    print_status "Creating test download..."
    cp "$APK_PATH" "/tmp/DCCPHub_test_download.apk"
    print_success "Test download created: /tmp/DCCPHub_test_download.apk"
    print_status "You can transfer this file to your Android device for testing"
fi
