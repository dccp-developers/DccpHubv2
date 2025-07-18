#!/bin/bash

echo "🤖 Android Studio Setup for DSMS Philex APK Build"
echo "=================================================="
echo ""

# Check if running on Linux
if [[ "$OSTYPE" != "linux-gnu"* ]]; then
    echo "❌ This script is designed for Linux. For other OS:"
    echo "   • Windows: Download from https://developer.android.com/studio"
    echo "   • macOS: Download from https://developer.android.com/studio"
    exit 1
fi

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check if Java is installed
echo "🔍 Checking Java installation..."
if command_exists java; then
    JAVA_VERSION=$(java -version 2>&1 | head -n 1 | cut -d'"' -f2)
    echo "✅ Java found: $JAVA_VERSION"
else
    echo "❌ Java not found. Installing OpenJDK 17..."
    sudo apt update
    sudo apt install -y openjdk-17-jdk
    echo "✅ Java installed"
fi

# Set JAVA_HOME if not set
if [ -z "$JAVA_HOME" ]; then
    echo "🔧 Setting JAVA_HOME..."
    export JAVA_HOME=/usr/lib/jvm/java-17-openjdk-amd64
    echo "export JAVA_HOME=/usr/lib/jvm/java-17-openjdk-amd64" >> ~/.bashrc
    echo "✅ JAVA_HOME set to $JAVA_HOME"
fi

# Create Android directory
ANDROID_HOME="$HOME/Android"
mkdir -p "$ANDROID_HOME"

# Download Android Studio
echo "📥 Downloading Android Studio..."
ANDROID_STUDIO_URL="https://redirector.gvt1.com/edgedl/android/studio/ide-zips/2024.2.1.11/android-studio-2024.2.1.11-linux.tar.gz"
DOWNLOAD_DIR="$HOME/Downloads"
ANDROID_STUDIO_FILE="android-studio-linux.tar.gz"

if [ ! -f "$DOWNLOAD_DIR/$ANDROID_STUDIO_FILE" ]; then
    echo "⏳ Downloading Android Studio (this may take a while)..."
    wget -O "$DOWNLOAD_DIR/$ANDROID_STUDIO_FILE" "$ANDROID_STUDIO_URL"
    echo "✅ Android Studio downloaded"
else
    echo "✅ Android Studio already downloaded"
fi

# Extract Android Studio
echo "📦 Extracting Android Studio..."
if [ ! -d "$ANDROID_HOME/android-studio" ]; then
    tar -xzf "$DOWNLOAD_DIR/$ANDROID_STUDIO_FILE" -C "$ANDROID_HOME"
    echo "✅ Android Studio extracted"
else
    echo "✅ Android Studio already extracted"
fi

# Set up environment variables
echo "🔧 Setting up environment variables..."
ANDROID_SDK_ROOT="$ANDROID_HOME/Sdk"
export ANDROID_HOME="$ANDROID_SDK_ROOT"
export ANDROID_SDK_ROOT="$ANDROID_SDK_ROOT"
export PATH="$PATH:$ANDROID_SDK_ROOT/tools:$ANDROID_SDK_ROOT/platform-tools:$ANDROID_SDK_ROOT/cmdline-tools/latest/bin"

# Add to bashrc
echo "" >> ~/.bashrc
echo "# Android Studio Environment" >> ~/.bashrc
echo "export ANDROID_HOME=\"$ANDROID_SDK_ROOT\"" >> ~/.bashrc
echo "export ANDROID_SDK_ROOT=\"$ANDROID_SDK_ROOT\"" >> ~/.bashrc
echo "export PATH=\"\$PATH:\$ANDROID_SDK_ROOT/tools:\$ANDROID_SDK_ROOT/platform-tools:\$ANDROID_SDK_ROOT/cmdline-tools/latest/bin\"" >> ~/.bashrc

echo "✅ Environment variables configured"

# Create desktop shortcut
echo "🖥️ Creating desktop shortcut..."
DESKTOP_FILE="$HOME/Desktop/Android Studio.desktop"
cat > "$DESKTOP_FILE" << EOF
[Desktop Entry]
Version=1.0
Type=Application
Name=Android Studio
Comment=The Official IDE for Android
Exec=$ANDROID_HOME/android-studio/bin/studio.sh
Icon=$ANDROID_HOME/android-studio/bin/studio.png
Categories=Development;IDE;
Terminal=false
StartupWMClass=jetbrains-studio
EOF

chmod +x "$DESKTOP_FILE"
echo "✅ Desktop shortcut created"

# Install required packages
echo "📦 Installing required packages..."
sudo apt update
sudo apt install -y libc6:i386 libncurses5:i386 libstdc++6:i386 lib32z1 libbz2-1.0:i386

echo ""
echo "🎉 Android Studio setup completed!"
echo ""
echo "📋 Next Steps:"
echo "1. Start Android Studio: $ANDROID_HOME/android-studio/bin/studio.sh"
echo "2. Complete the setup wizard"
echo "3. Install Android SDK and build tools"
echo "4. Create/import your project"
echo ""
echo "🔧 Manual Steps Required:"
echo "1. Open Android Studio"
echo "2. Go through the setup wizard"
echo "3. Install Android SDK (API 34 recommended)"
echo "4. Install Android SDK Build-Tools"
echo "5. Install Android SDK Platform-Tools"
echo "6. Install Android SDK Command-line Tools"
echo ""
echo "🚀 To build your APK:"
echo "   cd $(pwd)"
echo "   ./scripts/build-apk-android-studio.sh"
echo ""
echo "⚠️  Note: You may need to restart your terminal or run:"
echo "   source ~/.bashrc"
echo ""
