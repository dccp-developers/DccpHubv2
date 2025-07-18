#!/bin/bash

echo "🏗️ Building APK with Android Studio Tools"
echo "=========================================="
echo ""

# Check if Android SDK is available
if [ -z "$ANDROID_HOME" ]; then
    # Try to set ANDROID_HOME if SDK exists in default location
    if [ -d "$HOME/Android/Sdk" ]; then
        echo "🔧 Setting up Android environment variables..."
        export ANDROID_HOME="$HOME/Android/Sdk"
        export ANDROID_SDK_ROOT="$ANDROID_HOME"
        export PATH="$PATH:$ANDROID_HOME/cmdline-tools/latest/bin:$ANDROID_HOME/platform-tools"
        echo "✅ ANDROID_HOME set to: $ANDROID_HOME"
    else
        echo "❌ ANDROID_HOME not set and Android SDK not found"
        echo "💡 Please run setup-android-sdk.sh first to install Android SDK"
        exit 1
    fi
fi

if [ ! -d "$ANDROID_HOME" ]; then
    echo "❌ Android SDK not found at $ANDROID_HOME"
    echo "💡 Please install Android Studio and SDK first"
    exit 1
fi

# Check if project has android directory
if [ ! -d "android" ]; then
    echo "❌ Android project not found. Initializing Capacitor Android project..."
    npx cap add android
    echo "✅ Android project created"
fi

# Sync Capacitor
echo "🔄 Syncing Capacitor..."
npx cap sync android

# Check if gradlew exists
if [ ! -f "android/gradlew" ]; then
    echo "❌ Gradle wrapper not found"
    exit 1
fi

# Make gradlew executable
chmod +x android/gradlew

echo "🔧 Android Build Configuration:"
echo "   ANDROID_HOME: $ANDROID_HOME"
echo "   Project: $(pwd)/android"
echo ""

# Function to build APK
build_apk() {
    local build_type=$1
    echo "🏗️ Building $build_type APK..."
    
    cd android
    
    if [ "$build_type" = "debug" ]; then
        ./gradlew assembleDebug
        APK_PATH="app/build/outputs/apk/debug/app-debug.apk"
    else
        ./gradlew assembleRelease
        APK_PATH="app/build/outputs/apk/release/app-release-unsigned.apk"
    fi
    
    if [ $? -eq 0 ]; then
        echo "✅ $build_type APK build successful!"
        
        if [ -f "$APK_PATH" ]; then
            echo "📱 APK Location: android/$APK_PATH"
            
            # Copy to downloads directory
            cp "$APK_PATH" "../public/downloads/dsms-philex.apk"
            echo "✅ APK copied to public/downloads/dsms-philex.apk"
            
            # Update APK info
            cd ..
            node scripts/update-apk-info.js "android/$APK_PATH" "$build_type"
            
            echo ""
            echo "🎉 APK ready for download!"
            echo "🔗 URL: https://philexscholar.koamishin.org/downloads/dsms-philex.apk"
            
        else
            echo "❌ APK file not found at expected location"
            echo "🔍 Searching for APK files..."
            find app/build/outputs/apk -name "*.apk" -type f
        fi
    else
        echo "❌ APK build failed"
        echo ""
        echo "🔧 Common solutions:"
        echo "1. Check Android SDK installation"
        echo "2. Ensure all required SDK components are installed"
        echo "3. Check Gradle and build tool versions"
        echo "4. Try cleaning: ./gradlew clean"
        exit 1
    fi
    
    cd ..
}

# Function to open Android Studio
open_android_studio() {
    echo "🚀 Opening Android Studio..."
    if [ -f "$HOME/Android/android-studio/bin/studio.sh" ]; then
        "$HOME/Android/android-studio/bin/studio.sh" "$(pwd)/android" &
        echo "✅ Android Studio opened with project"
        echo ""
        echo "📋 In Android Studio:"
        echo "1. Wait for Gradle sync to complete"
        echo "2. Go to Build → Build Bundle(s) / APK(s) → Build APK(s)"
        echo "3. Wait for build to complete"
        echo "4. APK will be in app/build/outputs/apk/"
    else
        echo "❌ Android Studio not found"
        echo "💡 Please install Android Studio first"
    fi
}

# Function to create keystore for signing
create_keystore() {
    echo "🔐 Creating keystore for APK signing..."
    
    KEYSTORE_PATH="android/app/dsms-philex-keystore.jks"
    
    if [ -f "$KEYSTORE_PATH" ]; then
        echo "✅ Keystore already exists"
        return
    fi
    
    echo "📝 Please provide keystore information:"
    read -p "Enter keystore password: " -s KEYSTORE_PASSWORD
    echo ""
    read -p "Enter key alias (e.g., dsms-philex): " KEY_ALIAS
    read -p "Enter key password: " -s KEY_PASSWORD
    echo ""
    read -p "Enter your name: " DNAME_CN
    read -p "Enter organization: " DNAME_O
    read -p "Enter city: " DNAME_L
    read -p "Enter state/province: " DNAME_ST
    read -p "Enter country code (e.g., PH): " DNAME_C
    
    keytool -genkey -v -keystore "$KEYSTORE_PATH" \
        -alias "$KEY_ALIAS" \
        -keyalg RSA \
        -keysize 2048 \
        -validity 10000 \
        -storepass "$KEYSTORE_PASSWORD" \
        -keypass "$KEY_PASSWORD" \
        -dname "CN=$DNAME_CN, O=$DNAME_O, L=$DNAME_L, ST=$DNAME_ST, C=$DNAME_C"
    
    if [ $? -eq 0 ]; then
        echo "✅ Keystore created successfully"
        
        # Create signing config
        cat > android/app/signing.properties << EOF
storeFile=dsms-philex-keystore.jks
storePassword=$KEYSTORE_PASSWORD
keyAlias=$KEY_ALIAS
keyPassword=$KEY_PASSWORD
EOF
        
        echo "✅ Signing configuration created"
        echo "⚠️  Keep your keystore and passwords safe!"
    else
        echo "❌ Failed to create keystore"
    fi
}

# Main menu
echo "🎯 Choose build option:"
echo "1. Build Debug APK (quick, for testing)"
echo "2. Build Release APK (optimized, for distribution)"
echo "3. Open in Android Studio (manual build)"
echo "4. Create signing keystore"
echo "5. Clean build cache"
echo ""
read -p "Enter choice (1-5): " choice

case $choice in
    1)
        build_apk "debug"
        ;;
    2)
        echo "⚠️  Release builds require signing. Create keystore first if needed."
        read -p "Continue with release build? (y/n): " confirm
        if [ "$confirm" = "y" ]; then
            build_apk "release"
        fi
        ;;
    3)
        open_android_studio
        ;;
    4)
        create_keystore
        ;;
    5)
        echo "🧹 Cleaning build cache..."
        cd android
        ./gradlew clean
        cd ..
        echo "✅ Build cache cleaned"
        ;;
    *)
        echo "❌ Invalid choice"
        exit 1
        ;;
esac

echo ""
echo "📱 APK Build Complete!"
echo ""
echo "🔧 Troubleshooting:"
echo "• If build fails, check Android SDK installation"
echo "• Ensure all required SDK components are installed"
echo "• Try cleaning build cache: ./gradlew clean"
echo "• Check Capacitor documentation: https://capacitorjs.com/docs/android"
echo ""
