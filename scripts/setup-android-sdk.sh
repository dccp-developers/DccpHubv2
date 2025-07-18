#!/bin/bash

echo "🤖 Setting up Local Android SDK for APK Build"
echo "=============================================="
echo ""

# Configuration
PROJECT_ROOT="$(cd "$(/usr/bin/dirname "$0")/.." && pwd)"
ANDROID_HOME="$PROJECT_ROOT/.android-sdk"
CMDLINE_TOOLS_DIR="$ANDROID_HOME/cmdline-tools"
LATEST_DIR="$CMDLINE_TOOLS_DIR/latest"

echo "📁 Creating local Android SDK directories..."
echo "   SDK Location: $ANDROID_HOME"
/bin/mkdir -p "$ANDROID_HOME"
/bin/mkdir -p "$CMDLINE_TOOLS_DIR"

# Download Android command line tools
CMDLINE_TOOLS_URL="https://dl.google.com/android/repository/commandlinetools-linux-11076708_latest.zip"
CMDLINE_TOOLS_ZIP="$ANDROID_HOME/commandlinetools-linux-latest.zip"

echo "📥 Downloading Android Command Line Tools..."
if [ ! -f "$CMDLINE_TOOLS_ZIP" ]; then
    if command -v wget >/dev/null 2>&1; then
        wget -O "$CMDLINE_TOOLS_ZIP" "$CMDLINE_TOOLS_URL"
    elif command -v curl >/dev/null 2>&1; then
        curl -L "$CMDLINE_TOOLS_URL" -o "$CMDLINE_TOOLS_ZIP"
    else
        echo "❌ Neither wget nor curl found. Cannot download SDK tools."
        exit 1
    fi
    echo "✅ Command line tools downloaded"
else
    echo "✅ Command line tools already downloaded"
fi

# Extract command line tools
echo "📦 Extracting command line tools..."
if [ ! -d "$LATEST_DIR" ]; then
    cd "$CMDLINE_TOOLS_DIR"
    if command -v unzip >/dev/null 2>&1; then
        unzip -q "$CMDLINE_TOOLS_ZIP"
        /bin/mv cmdline-tools latest
        /bin/rm "$CMDLINE_TOOLS_ZIP"
        echo "✅ Command line tools extracted"
    else
        echo "❌ unzip not found. Cannot extract SDK tools."
        exit 1
    fi
else
    echo "✅ Command line tools already extracted"
fi

# Set environment variables
export ANDROID_HOME="$ANDROID_HOME"
export ANDROID_SDK_ROOT="$ANDROID_HOME"
export PATH="$PATH:$ANDROID_HOME/cmdline-tools/latest/bin:$ANDROID_HOME/platform-tools"

echo "🔧 Setting up environment variables..."

# Create environment file for this project
cat > "$PROJECT_ROOT/.android-env" << EOF
export ANDROID_HOME="$ANDROID_HOME"
export ANDROID_SDK_ROOT="$ANDROID_HOME"
export PATH="\$PATH:$ANDROID_HOME/cmdline-tools/latest/bin:$ANDROID_HOME/platform-tools:$ANDROID_HOME/build-tools/34.0.0"
EOF
echo "✅ Environment file created at $PROJECT_ROOT/.android-env"

# Create local.properties for Android project
echo "📝 Creating local.properties..."
if [ -d "$PROJECT_ROOT/android" ]; then
    echo "sdk.dir=$ANDROID_HOME" > "$PROJECT_ROOT/android/local.properties"
    echo "✅ local.properties created"
else
    echo "⚠️  Android project directory not found, skipping local.properties"
fi

# Accept licenses and install SDK components
echo "📋 Installing Android SDK components..."

# Create licenses directory and accept licenses
/bin/mkdir -p "$ANDROID_HOME/licenses"
echo "24333f8a63b6825ea9c5514f83c2829b004d1fee" > "$ANDROID_HOME/licenses/android-sdk-license"
echo "84831b9409646a918e30573bab4c9c91346d8abd" > "$ANDROID_HOME/licenses/android-sdk-preview-license"
echo "✅ SDK licenses accepted"

# Install required SDK components
echo "📦 Installing platform-tools..."
"$LATEST_DIR/bin/sdkmanager" "platform-tools" 2>/dev/null || echo "⚠️  Platform tools installation may have issues"

echo "📦 Installing build-tools..."
"$LATEST_DIR/bin/sdkmanager" "build-tools;34.0.0" 2>/dev/null || echo "⚠️  Build tools installation may have issues"

echo "📦 Installing Android platform..."
"$LATEST_DIR/bin/sdkmanager" "platforms;android-34" 2>/dev/null || echo "⚠️  Platform installation may have issues"

echo ""
echo "🎉 Local Android SDK setup completed!"
echo ""
echo "📊 SDK Location: $ANDROID_HOME"
echo "📊 Environment file: $PROJECT_ROOT/.android-env"

echo ""
echo "🚀 To use this SDK:"
echo "   1. Run: source .android-env"
echo "   2. Run: ./scripts/build-apk-unified.sh capacitor"
echo ""
echo "Or build manually:"
echo "   source .android-env"
echo "   cd android"
echo "   ./gradlew assembleDebug"
echo ""
