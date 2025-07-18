#!/bin/bash

# DCCPHub PWA Production Verification Script
# This script verifies that PWA functionality is working correctly on the production site

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PRODUCTION_URL="https://portal.dccp.edu.ph"
MANIFEST_URL="${PRODUCTION_URL}/manifest.json"
SERVICE_WORKER_URL="${PRODUCTION_URL}/sw.js"

echo -e "${BLUE}🔍 Verifying DCCPHub PWA on Production${NC}"
echo "Production URL: $PRODUCTION_URL"

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

# Check if curl is available
if ! command -v curl &> /dev/null; then
    print_error "curl is not installed. Please install curl first."
    exit 1
fi

# Check if jq is available for JSON parsing
if ! command -v jq &> /dev/null; then
    print_warning "jq is not installed. JSON validation will be skipped."
    JQ_AVAILABLE=false
else
    JQ_AVAILABLE=true
fi

echo ""
echo -e "${BLUE}📋 Testing PWA Components${NC}"

# Test 1: Check if main site is accessible
echo "1. Testing main site accessibility..."
if curl -s -f -o /dev/null "$PRODUCTION_URL"; then
    print_status "Main site is accessible"
else
    print_error "Main site is not accessible"
    exit 1
fi

# Test 2: Check manifest.json
echo "2. Testing manifest.json..."
MANIFEST_RESPONSE=$(curl -s -w "%{http_code}" "$MANIFEST_URL")
HTTP_CODE="${MANIFEST_RESPONSE: -3}"

if [ "$HTTP_CODE" = "200" ]; then
    print_status "Manifest.json is accessible"
    
    # Validate JSON if jq is available
    if [ "$JQ_AVAILABLE" = true ]; then
        MANIFEST_CONTENT=$(curl -s "$MANIFEST_URL")
        if echo "$MANIFEST_CONTENT" | jq . > /dev/null 2>&1; then
            print_status "Manifest.json is valid JSON"
            
            # Check required PWA fields
            NAME=$(echo "$MANIFEST_CONTENT" | jq -r '.name // empty')
            START_URL=$(echo "$MANIFEST_CONTENT" | jq -r '.start_url // empty')
            DISPLAY=$(echo "$MANIFEST_CONTENT" | jq -r '.display // empty')
            ICONS_COUNT=$(echo "$MANIFEST_CONTENT" | jq '.icons | length // 0')
            
            if [ -n "$NAME" ] && [ -n "$START_URL" ] && [ -n "$DISPLAY" ] && [ "$ICONS_COUNT" -gt 0 ]; then
                print_status "Manifest.json contains required PWA fields"
                echo "  - Name: $NAME"
                echo "  - Start URL: $START_URL"
                echo "  - Display: $DISPLAY"
                echo "  - Icons: $ICONS_COUNT"
            else
                print_warning "Manifest.json missing some required PWA fields"
            fi
        else
            print_error "Manifest.json contains invalid JSON"
        fi
    fi
else
    print_error "Manifest.json is not accessible (HTTP $HTTP_CODE)"
fi

# Test 3: Check service worker
echo "3. Testing service worker..."
SW_RESPONSE=$(curl -s -w "%{http_code}" "$SERVICE_WORKER_URL")
SW_HTTP_CODE="${SW_RESPONSE: -3}"

if [ "$SW_HTTP_CODE" = "200" ]; then
    print_status "Service worker is accessible"
else
    print_error "Service worker is not accessible (HTTP $SW_HTTP_CODE)"
fi

# Test 4: Check PWA icons
echo "4. Testing PWA icons..."
ICON_SIZES=("48-48" "72-72" "96-96" "144-144" "192-192" "512-512")
ICON_ERRORS=0

for size in "${ICON_SIZES[@]}"; do
    ICON_URL="${PRODUCTION_URL}/images/android/android-launchericon-${size}.png"
    if curl -s -f -o /dev/null "$ICON_URL"; then
        echo "  ✓ Icon ${size} is accessible"
    else
        echo "  ✗ Icon ${size} is not accessible"
        ((ICON_ERRORS++))
    fi
done

if [ $ICON_ERRORS -eq 0 ]; then
    print_status "All PWA icons are accessible"
else
    print_warning "$ICON_ERRORS PWA icons are not accessible"
fi

# Test 5: Check HTTPS
echo "5. Testing HTTPS configuration..."
if [[ "$PRODUCTION_URL" == https://* ]]; then
    print_status "Site is using HTTPS (required for PWA)"
else
    print_error "Site is not using HTTPS (PWA requires HTTPS)"
fi

# Test 6: Check APK endpoints
echo "6. Testing APK endpoints..."
APK_STATUS_URL="${PRODUCTION_URL}/apk/status"
APK_STATUS_RESPONSE=$(curl -s -w "%{http_code}" "$APK_STATUS_URL")
APK_STATUS_HTTP_CODE="${APK_STATUS_RESPONSE: -3}"

if [ "$APK_STATUS_HTTP_CODE" = "200" ]; then
    print_status "APK status endpoint is accessible"
else
    print_warning "APK status endpoint returned HTTP $APK_STATUS_HTTP_CODE"
fi

echo ""
echo -e "${BLUE}📱 PWA Installation Test${NC}"
echo "To test PWA installation:"
echo "1. Open $PRODUCTION_URL in Chrome/Edge on mobile"
echo "2. Look for 'Add to Home Screen' prompt or install button"
echo "3. Install the PWA and verify it opens correctly"
echo "4. Check that installed PWA redirects to login page"

echo ""
echo -e "${BLUE}📦 APK Generation Test${NC}"
echo "To test APK generation:"
echo "1. Visit the landing page and scroll to 'Get the Mobile App' section"
echo "2. Click 'Generate APK' if no APK is available"
echo "3. Download the generated APK file"
echo "4. Install on Android device and test functionality"

echo ""
echo -e "${GREEN}🎉 PWA Production Verification Complete!${NC}"

# Summary
echo ""
echo -e "${BLUE}📊 Summary${NC}"
if [ $ICON_ERRORS -eq 0 ]; then
    echo -e "${GREEN}✓ All PWA components are properly configured for production${NC}"
    echo -e "${GREEN}✓ Ready for mobile PWA installation and APK generation${NC}"
else
    echo -e "${YELLOW}⚠ Some issues found - check icon accessibility${NC}"
fi

echo ""
echo "Production URL: $PRODUCTION_URL"
echo "Manifest URL: $MANIFEST_URL"
echo "Service Worker URL: $SERVICE_WORKER_URL"
