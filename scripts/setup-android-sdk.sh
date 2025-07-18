#!/bin/bash

echo "🤖 Setting up Android SDK for APK Build"
echo "======================================="
echo ""

# Set up directories
ANDROID_HOME="$HOME/Android/Sdk"
CMDLINE_TOOLS_DIR="$ANDROID_HOME/cmdline-tools"
LATEST_DIR="$CMDLINE_TOOLS_DIR/latest"

echo "📁 Creating Android SDK directories..."
mkdir -p "$ANDROID_HOME"
mkdir -p "$CMDLINE_TOOLS_DIR"

# Download Android command line tools
CMDLINE_TOOLS_URL="https://dl.google.com/android/repository/commandlinetools-linux-11076708_latest.zip"
DOWNLOAD_DIR="$HOME/Downloads"
CMDLINE_TOOLS_ZIP="commandlinetools-linux-latest.zip"

echo "📥 Downloading Android Command Line Tools..."
if [ ! -f "$DOWNLOAD_DIR/$CMDLINE_TOOLS_ZIP" ]; then
    wget -O "$DOWNLOAD_DIR/$CMDLINE_TOOLS_ZIP" "$CMDLINE_TOOLS_URL"
    echo "✅ Command line tools downloaded"
else
    echo "✅ Command line tools already downloaded"
fi

# Extract command line tools
echo "📦 Extracting command line tools..."
if [ ! -d "$LATEST_DIR" ]; then
    cd "$CMDLINE_TOOLS_DIR"
    unzip -q "$DOWNLOAD_DIR/$CMDLINE_TOOLS_ZIP"
    mv cmdline-tools latest
    echo "✅ Command line tools extracted"
else
    echo "✅ Command line tools already extracted"
fi

# Set environment variables
export ANDROID_HOME="$ANDROID_HOME"
export ANDROID_SDK_ROOT="$ANDROID_HOME"
export PATH="$PATH:$ANDROID_HOME/cmdline-tools/latest/bin:$ANDROID_HOME/platform-tools"

echo "🔧 Setting up environment variables..."
# Add to bashrc if not already present
if ! grep -q "ANDROID_HOME" ~/.bashrc; then
    echo "" >> ~/.bashrc
    echo "# Android SDK Environment" >> ~/.bashrc
    echo "export ANDROID_HOME=\"$ANDROID_HOME\"" >> ~/.bashrc
    echo "export ANDROID_SDK_ROOT=\"\$ANDROID_HOME\"" >> ~/.bashrc
    echo "export PATH=\"\$PATH:\$ANDROID_HOME/cmdline-tools/latest/bin:\$ANDROID_HOME/platform-tools\"" >> ~/.bashrc
    echo "✅ Environment variables added to ~/.bashrc"
else
    echo "✅ Environment variables already configured"
fi

# Create local.properties for Android project
echo "📝 Creating local.properties..."
echo "sdk.dir=$ANDROID_HOME" > android/local.properties
echo "✅ local.properties created"

# Accept licenses and install SDK components
echo "📋 Installing Android SDK components..."
echo "⚠️  You may need to accept licenses..."

# Accept all licenses
yes | "$LATEST_DIR/bin/sdkmanager" --licenses 2>/dev/null || true

# Install required SDK components
"$LATEST_DIR/bin/sdkmanager" "platform-tools" "platforms;android-34" "build-tools;34.0.0"

echo ""
echo "🎉 Android SDK setup completed!"
echo ""
echo "📊 Installed components:"
"$LATEST_DIR/bin/sdkmanager" --list_installed 2>/dev/null | head -10

echo ""
echo "🚀 Now you can build your APK:"
echo "   cd android"
echo "   ./gradlew assembleDebug"
echo ""
echo "Or use the build script:"
echo "   ./scripts/build-apk-android-studio.sh"
echo ""
