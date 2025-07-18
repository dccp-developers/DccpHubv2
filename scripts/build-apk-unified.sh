#!/bin/bash

# DCCPHub Unified APK Build Script
# This script provides a unified interface for building APKs with multiple methods

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configuration
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SCRIPTS_DIR="$PROJECT_ROOT/scripts"

# Function to print colored output
print_header() {
    echo -e "${CYAN}================================${NC}"
    echo -e "${CYAN}  DCCPHub APK Build System${NC}"
    echo -e "${CYAN}================================${NC}"
    echo ""
}

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

# Function to show usage
show_usage() {
    echo "Usage: $0 [METHOD]"
    echo ""
    echo "Available build methods:"
    echo "  capacitor    - Build using Capacitor (recommended)"
    echo "  pwa          - Build using PWA Builder"
    echo "  auto         - Auto-detect best method (default)"
    echo ""
    echo "Examples:"
    echo "  $0 capacitor"
    echo "  $0 auto"
    echo ""
}

# Function to check if Capacitor is properly configured
check_capacitor() {
    if [ -f "$PROJECT_ROOT/capacitor.config.ts" ] && [ -d "$PROJECT_ROOT/android" ]; then
        return 0
    else
        return 1
    fi
}

# Function to check if PWA Builder is available
check_pwa_builder() {
    if command -v npx &> /dev/null; then
        return 0
    else
        return 1
    fi
}

# Function to build with Capacitor
build_with_capacitor() {
    print_status "Building APK using Capacitor method..."
    
    if [ ! -f "$SCRIPTS_DIR/build-apk-capacitor.sh" ]; then
        print_error "Capacitor build script not found"
        return 1
    fi
    
    chmod +x "$SCRIPTS_DIR/build-apk-capacitor.sh"
    bash "$SCRIPTS_DIR/build-apk-capacitor.sh"
}

# Function to build with PWA Builder
build_with_pwa() {
    print_status "Building APK using PWA Builder method..."
    
    if [ ! -f "$SCRIPTS_DIR/build-apk.sh" ]; then
        print_error "PWA Builder build script not found"
        return 1
    fi
    
    chmod +x "$SCRIPTS_DIR/build-apk.sh"
    bash "$SCRIPTS_DIR/build-apk.sh"
}

# Function to auto-detect best build method
auto_detect_method() {
    print_status "Auto-detecting best build method..."
    
    if check_capacitor; then
        print_success "Capacitor configuration detected - using Capacitor method"
        return 0  # Use Capacitor
    elif check_pwa_builder; then
        print_warning "Capacitor not configured - falling back to PWA Builder method"
        return 1  # Use PWA Builder
    else
        print_error "No suitable build method available"
        return 2  # Error
    fi
}

# Main function
main() {
    print_header
    
    # Change to project root
    cd "$PROJECT_ROOT"
    
    # Parse command line arguments
    METHOD="${1:-auto}"
    
    case "$METHOD" in
        "capacitor")
            if check_capacitor; then
                build_with_capacitor
            else
                print_error "Capacitor is not properly configured"
                print_error "Please run 'npx cap add android' first"
                exit 1
            fi
            ;;
        "pwa")
            if check_pwa_builder; then
                build_with_pwa
            else
                print_error "PWA Builder is not available"
                print_error "Please install Node.js and npm first"
                exit 1
            fi
            ;;
        "auto")
            auto_detect_method
            case $? in
                0)
                    build_with_capacitor
                    ;;
                1)
                    build_with_pwa
                    ;;
                2)
                    print_error "No suitable build method available"
                    print_error "Please install Node.js/npm and configure Capacitor"
                    exit 1
                    ;;
            esac
            ;;
        "help"|"-h"|"--help")
            show_usage
            exit 0
            ;;
        *)
            print_error "Unknown build method: $METHOD"
            echo ""
            show_usage
            exit 1
            ;;
    esac
}

# Run main function
main "$@"
