#!/bin/bash

# DCCPHub GitHub Release Description Update Script
# This script updates release descriptions based on commit message analysis

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

# GitHub API configuration
GITHUB_REPO="yukazakiri/DccpHubv2"
GITHUB_API_BASE="https://api.github.com"

# Function to check if GitHub token is available
check_github_token() {
    if [ -z "$GITHUB_TOKEN" ]; then
        print_error "GITHUB_TOKEN environment variable is required"
        print_status "Please set your GitHub token: export GITHUB_TOKEN=your_token_here"
        exit 1
    fi
}

# Function to get release ID from tag
get_release_id() {
    local tag=$1
    curl -s -H "Authorization: token $GITHUB_TOKEN" \
        "$GITHUB_API_BASE/repos/$GITHUB_REPO/releases/tags/$tag" | \
        grep '"id":' | head -1 | sed 's/.*"id": *\([0-9]*\).*/\1/'
}

# Function to update release description
update_release() {
    local release_id=$1
    local new_body=$2
    local tag_name=$3
    
    print_status "Updating release $tag_name (ID: $release_id)"
    
    # Create JSON payload
    cat > /tmp/release_update.json << EOF
{
  "body": $(echo "$new_body" | sed 's/\\/\\\\/g' | sed 's/"/\\"/g' | sed ':a;N;$!ba;s/\n/\\n/g' | sed 's/^/"/;s/$/"/')
}
EOF
    
    # Update the release
    response=$(curl -s -X PATCH \
        -H "Authorization: token $GITHUB_TOKEN" \
        -H "Content-Type: application/json" \
        -d @/tmp/release_update.json \
        "$GITHUB_API_BASE/repos/$GITHUB_REPO/releases/$release_id")
    
    # Check if update was successful
    if echo "$response" | grep -q '"id":'; then
        print_success "✅ Successfully updated release $tag_name"
    else
        print_error "❌ Failed to update release $tag_name"
        echo "Response: $response"
    fi
    
    # Clean up
    rm -f /tmp/release_update.json
}

# Enhanced release descriptions based on commit analysis
update_v1_0_0() {
    local description='# DCCPHub Mobile App v1.0.0 🎉

## 🚀 Initial Release

This is the first official release of the DCCPHub mobile application - a comprehensive attendance and course management system for DCCP faculty and students.

## ✨ What'\''s New

### 🆕 Core Features
- **GitHub Releases Integration** - Automated APK distribution system
- **Secure Authentication** with Google OAuth integration
- **Mobile-Optimized Interface** designed for faculty and student workflows
- **PWA Capabilities** with offline support
- **Custom DCCP Branding** with official logos and icons

### 🔧 Technical Foundation
- Built with **Laravel** backend and **Vue.js** frontend
- **Android APK** built with Capacitor
- **Real-time Dashboard** with attendance analytics
- **Responsive Design** optimized for mobile devices

## 📱 Installation Instructions

1. **Download** the APK file below
2. **Enable** "Install from unknown sources" in your Android settings:
   - Go to Settings > Security > Unknown Sources (Android 7 and below)
   - Go to Settings > Apps > Special Access > Install Unknown Apps (Android 8+)
3. **Install** the downloaded APK file
4. **Open** the DCCPHub app and sign in

## 📊 Build Information

- **Version**: 1.0.0
- **Build Type**: Production
- **Platform**: Android
- **Target API**: 34
- **Minimum Android**: 7.0 (API 24)
- **Built**: Manual release

## 🎯 Target Audience

- **Faculty Members** - Attendance tracking and class management
- **Students** - Course enrollment and attendance monitoring
- **Administrators** - System oversight and analytics

---

**Note**: This release establishes the foundation for future enhancements. Please report any issues through the appropriate channels.'
    
    local release_id=$(get_release_id "v1.0.0")
    if [ -n "$release_id" ]; then
        update_release "$release_id" "$description" "v1.0.0"
    else
        print_error "Could not find release ID for v1.0.0"
    fi
}

update_v1_0_1() {
    local description='# DCCPHub Mobile App v1.0.1 🔄

## 🚀 Workflow & Build System Improvements

This release focuses on improving the build system and implementing automated GitHub release workflows.

## ✨ What'\''s New

### 🔧 Build System Enhancements
- **Committed APK Workflow** - Implemented automated GitHub releases from committed APK files
- **Improved Build Process** - Enhanced APK generation with better consistency
- **Build Timestamp**: 2025-08-04 04:01:28 UTC

### 🛠️ Technical Improvements
- Streamlined release process for faster deployments
- Better integration between build system and GitHub releases
- Enhanced build reproducibility

## 📱 Installation Instructions

1. **Download** the APK file below
2. **Enable** "Install from unknown sources" in your Android settings:
   - Go to Settings > Security > Unknown Sources (Android 7 and below)
   - Go to Settings > Apps > Special Access > Install Unknown Apps (Android 8+)
3. **Install** the downloaded APK file
4. **Open** the DCCPHub app and sign in

## 📊 Build Information

- **Version**: 1.0.1
- **Build Type**: Debug
- **Platform**: Android
- **Size**: ~12MB
- **Target API**: 34
- **Minimum Android**: 7.0 (API 24)
- **Built**: 2025-08-04 04:01:28 UTC

## 🔄 Changes from v1.0.0

### Added
- Automated GitHub release workflow
- Committed APK build system

### Improved
- Build process reliability
- Release deployment automation

---

**Note**: This is a debug build for testing purposes. The focus of this release is on improving our development and deployment workflows.'
    
    local release_id=$(get_release_id "v1.0.1")
    if [ -n "$release_id" ]; then
        update_release "$release_id" "$description" "v1.0.1"
    else
        print_error "Could not find release ID for v1.0.1"
    fi
}

update_v1_0_2() {
    local description='# DCCPHub Mobile App v1.0.2 🎨

## 🔧 UI/UX Fixes & Icon Improvements

This release addresses visual issues and improves the overall user experience, particularly focusing on app drawer display and icon configuration.

## ✨ What'\''s New

### 🎨 Visual Improvements
- **Fixed Adaptive Icon Configuration** - Resolved app drawer display issues for better app visibility
- **Enhanced App Icon Display** - Improved icon rendering across different Android launchers
- **Updated Build Process** - Refined APK generation for better consistency

### 🛠️ Technical Fixes
- Corrected adaptive icon implementation for proper Android compliance
- Improved app launcher integration
- **Build Timestamp**: 2025-08-07 03:16:47 UTC

## 📱 Installation Instructions

1. **Download** the APK file below
2. **Enable** "Install from unknown sources" in your Android settings:
   - Go to Settings > Security > Unknown Sources (Android 7 and below)
   - Go to Settings > Apps > Special Access > Install Unknown Apps (Android 8+)
3. **Install** the downloaded APK file
4. **Open** the DCCPHub app and sign in

## 📊 Build Information

- **Version**: 1.0.2
- **Build Type**: Debug
- **Platform**: Android
- **Size**: ~12MB
- **Target API**: 34
- **Minimum Android**: 7.0 (API 24)
- **Built**: 2025-08-07 03:16:47 UTC

## 🔄 Changes from v1.0.1

### Fixed
- ✅ **Adaptive icon configuration** for proper app drawer display
- ✅ **App icon rendering** across different Android launchers
- ✅ **Build consistency** improvements

### Technical Details
- Updated adaptive icon implementation
- Enhanced DCCP branding integration
- Improved Android launcher compatibility

---

**Note**: This release specifically addresses visual display issues reported in previous versions. Users should notice improved app icon appearance in their device'\''s app drawer.'
    
    local release_id=$(get_release_id "v1.0.2")
    if [ -n "$release_id" ]; then
        update_release "$release_id" "$description" "v1.0.2"
    else
        print_error "Could not find release ID for v1.0.2"
    fi
}

update_v1_1_0() {
    local description='# DCCPHub Mobile App v1.1.0 📚

## 🚀 Major Feature Update - Attendance Management System

This release introduces comprehensive attendance management capabilities and significant UI/UX improvements, making DCCPHub a complete solution for faculty and student needs.

## ✨ What'\''s New

### 📊 Attendance Management System
- **Comprehensive Attendance Tracking** - Full-featured system for faculty to manage student attendance
- **Faculty Attendance Features** - Enhanced tools for recording and analyzing attendance data
- **Student Attendance Views** - Improved interfaces for students to view their attendance records

### 🎨 UI/UX Enhancements
- **Redesigned Today'\''s Overview** - Improved dashboard with better information display
- **Enhanced Quick Actions** - Streamlined faculty class list interface
- **Improved Layout Responsiveness** - Better mobile experience across all screen sizes
- **Simplified Navigation Components** - More intuitive user interface design

### 📋 Faculty Management
- **Missing Student Request System** - Tools for managing student absence requests
- **Faculty Deadline Management** - Enhanced deadline tracking and notification features
- **Improved Class Management** - Better tools for organizing and managing classes

## 📱 Installation Instructions

1. **Download** the APK file below
2. **Enable** "Install from unknown sources" in your Android settings:
   - Go to Settings > Security > Unknown Sources (Android 7 and below)
   - Go to Settings > Apps > Special Access > Install Unknown Apps (Android 8+)
3. **Install** the downloaded APK file
4. **Open** the DCCPHub app and sign in

## 📊 Build Information

- **Version**: 1.1.0
- **Build Type**: Debug
- **Platform**: Android
- **Size**: ~12MB
- **Target API**: 34
- **Minimum Android**: 7.0 (API 24)
- **Built**: 2025-08-10 01:10:51 UTC

## 🔄 Changes from v1.0.2

### 🆕 Added
- ✨ **Complete attendance management system**
- 📋 **Missing student request and faculty deadline management**
- 🎨 **Redesigned Today'\''s Overview and Quick Actions**
- 📱 **Enhanced mobile responsiveness**

### 🔧 Improved
- 🎯 **Faculty class list interface**
- 🧭 **Navigation component simplification**
- 📊 **Layout responsiveness across devices**
- 📚 **Project documentation and dependencies**

### 🛠️ Technical Enhancements
- Enhanced component structure
- Improved mobile optimization
- Better layout management
- Updated documentation

## 🎯 Key Benefits

- **Faculty**: Complete attendance tracking with analytics and deadline management
- **Students**: Better visibility into attendance records and course information  
- **All Users**: Improved interface design and mobile experience

---

**Note**: This is a significant feature release that transforms DCCPHub into a comprehensive attendance management platform. The enhanced UI and new features greatly improve the user experience for both faculty and students.'
    
    local release_id=$(get_release_id "v1.1.0")
    if [ -n "$release_id" ]; then
        update_release "$release_id" "$description" "v1.1.0"
    else
        print_error "Could not find release ID for v1.1.0"
    fi
}

# Main function
main() {
    print_status "🚀 DCCPHub Release Description Update Tool"
    echo ""
    
    # Check GitHub token
    check_github_token
    
    # Ask which releases to update
    echo "Select releases to update:"
    echo "1) v1.0.0 - Initial Release"
    echo "2) v1.0.1 - Workflow Improvements" 
    echo "3) v1.0.2 - UI/UX Fixes"
    echo "4) v1.1.0 - Attendance Management System"
    echo "5) All releases"
    echo "6) Cancel"
    echo ""
    
    read -p "Enter your choice (1-6): " -n 1 -r choice
    echo ""
    
    case $choice in
        1)
            update_v1_0_0
            ;;
        2)
            update_v1_0_1
            ;;
        3)
            update_v1_0_2
            ;;
        4)
            update_v1_1_0
            ;;
        5)
            print_status "Updating all releases..."
            update_v1_0_0
            update_v1_0_1
            update_v1_0_2
            update_v1_1_0
            print_success "🎉 All releases updated!"
            ;;
        6)
            print_status "Cancelled."
            exit 0
            ;;
        *)
            print_error "Invalid choice."
            exit 1
            ;;
    esac
    
    echo ""
    print_success "✅ Release description update completed!"
    print_status "📱 You can view the updated releases at:"
    echo "   https://github.com/yukazakiri/DccpHubv2/releases"
}

# Run main function
main "$@"