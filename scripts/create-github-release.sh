#!/bin/bash

# DCCPHub GitHub Release Creation Script
# This script creates a new GitHub release and triggers the APK build workflow

set -e

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

# Function to get the latest tag
get_latest_tag() {
    git describe --tags --abbrev=0 2>/dev/null || echo "v0.0.0"
}

# Function to increment version
increment_version() {
    local version=$1
    local bump_type=$2
    
    # Remove 'v' prefix if present
    version=${version#v}
    
    # Split version into parts
    IFS='.' read -ra VERSION_PARTS <<< "$version"
    local major=${VERSION_PARTS[0]:-0}
    local minor=${VERSION_PARTS[1]:-0}
    local patch=${VERSION_PARTS[2]:-0}
    
    # Increment based on bump type
    case $bump_type in
        major)
            major=$((major + 1))
            minor=0
            patch=0
            ;;
        minor)
            minor=$((minor + 1))
            patch=0
            ;;
        patch)
            patch=$((patch + 1))
            ;;
        *)
            print_error "Invalid bump type: $bump_type. Use: major, minor, or patch"
            exit 1
            ;;
    esac
    
    echo "v${major}.${minor}.${patch}"
}

# Main function
main() {
    print_status "🚀 DCCPHub GitHub Release Creation"
    echo ""
    
    # Check if we're in a git repository
    if [ ! -d ".git" ]; then
        print_error "Not in a git repository. Please run this script from the project root."
        exit 1
    fi
    
    # Check if we're on the main branch
    current_branch=$(git branch --show-current)
    if [ "$current_branch" != "main" ]; then
        print_warning "You're not on the main branch (current: $current_branch)"
        read -p "Do you want to continue? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_status "Aborted."
            exit 0
        fi
    fi
    
    # Check for uncommitted changes
    if [ -n "$(git status --porcelain)" ]; then
        print_warning "You have uncommitted changes:"
        git status --short
        echo ""
        read -p "Do you want to continue? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_status "Please commit your changes first."
            exit 0
        fi
    fi
    
    # Get current version
    latest_tag=$(get_latest_tag)
    print_status "Current latest tag: $latest_tag"
    
    # Ask for version bump type
    echo ""
    echo "Select version bump type:"
    echo "1) Patch (bug fixes) - ${latest_tag} → $(increment_version "$latest_tag" "patch")"
    echo "2) Minor (new features) - ${latest_tag} → $(increment_version "$latest_tag" "minor")"
    echo "3) Major (breaking changes) - ${latest_tag} → $(increment_version "$latest_tag" "major")"
    echo "4) Custom version"
    echo "5) Cancel"
    echo ""
    
    read -p "Enter your choice (1-5): " -n 1 -r choice
    echo ""
    
    case $choice in
        1)
            new_version=$(increment_version "$latest_tag" "patch")
            bump_type="patch"
            ;;
        2)
            new_version=$(increment_version "$latest_tag" "minor")
            bump_type="minor"
            ;;
        3)
            new_version=$(increment_version "$latest_tag" "major")
            bump_type="major"
            ;;
        4)
            read -p "Enter custom version (e.g., v1.2.3): " custom_version
            if [[ ! $custom_version =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
                print_error "Invalid version format. Use format: v1.2.3"
                exit 1
            fi
            new_version=$custom_version
            bump_type="custom"
            ;;
        5)
            print_status "Cancelled."
            exit 0
            ;;
        *)
            print_error "Invalid choice."
            exit 1
            ;;
    esac
    
    print_status "New version will be: $new_version"
    echo ""
    
    # Confirm
    read -p "Create release $new_version? (y/N): " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_status "Cancelled."
        exit 0
    fi
    
    # Create and push tag
    print_status "Creating tag: $new_version"
    git tag -a "$new_version" -m "Release $new_version"
    
    print_status "Pushing tag to GitHub..."
    git push origin "$new_version"
    
    print_success "✅ Tag $new_version created and pushed!"
    echo ""
    print_status "🔄 GitHub Actions workflow will now:"
    echo "   1. Build the APK with custom icons"
    echo "   2. Create a GitHub release"
    echo "   3. Upload the APK to the release"
    echo ""
    print_status "📱 You can monitor the progress at:"
    echo "   https://github.com/yukazakiri/DccpHubv2/actions"
    echo ""
    print_status "📦 Once complete, the APK will be available at:"
    echo "   https://portal.dccp.edu.ph/download/apk"
    echo ""
}

# Run main function
main "$@"
