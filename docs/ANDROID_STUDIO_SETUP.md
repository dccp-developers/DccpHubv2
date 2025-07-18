# Android Studio Setup for DCCPHub APK Building

This guide will help you set up Android Studio to build APK files for DCCPHub using Capacitor.

## Prerequisites

- Node.js and npm installed
- DCCPHub project cloned and dependencies installed

## Step 1: Install Android Studio

### Download and Install
1. Go to [Android Studio Download Page](https://developer.android.com/studio)
2. Download Android Studio for your operating system
3. Install Android Studio following the setup wizard

### Initial Setup
1. Open Android Studio
2. Complete the setup wizard
3. Install the Android SDK (API level 33 or higher recommended)
4. Install Android SDK Build-Tools
5. Install Android Emulator (optional, for testing)

## Step 2: Configure Environment Variables

### Linux/macOS
Add these lines to your `~/.bashrc`, `~/.zshrc`, or `~/.profile`:

```bash
export ANDROID_HOME=$HOME/Android/Sdk
export PATH=$PATH:$ANDROID_HOME/emulator
export PATH=$PATH:$ANDROID_HOME/platform-tools
export PATH=$PATH:$ANDROID_HOME/cmdline-tools/latest/bin
```

### Windows
1. Open System Properties → Advanced → Environment Variables
2. Add new system variable:
   - Variable name: `ANDROID_HOME`
   - Variable value: `C:\Users\YourUsername\AppData\Local\Android\Sdk`
3. Add to PATH:
   - `%ANDROID_HOME%\emulator`
   - `%ANDROID_HOME%\platform-tools`
   - `%ANDROID_HOME%\cmdline-tools\latest\bin`

## Step 3: Verify Installation

Open a new terminal and run:
```bash
adb --version
```

You should see the Android Debug Bridge version.

## Step 4: Build APK with Capacitor

### Method 1: Using Our Build Script
```bash
npm run build:apk
```

### Method 2: Manual Steps
```bash
# Build web assets
npm run build

# Copy to Android project
npx cap copy android

# Sync Capacitor
npx cap sync android

# Open in Android Studio
npx cap open android
```

### Method 3: Command Line Build
```bash
# Navigate to android directory
cd android

# Build debug APK
./gradlew assembleDebug

# Build release APK (requires signing)
./gradlew assembleRelease
```

## Step 5: APK Signing (For Release)

### Generate Keystore
```bash
keytool -genkey -v -keystore dccp-hub.keystore -alias dccp-hub -keyalg RSA -keysize 2048 -validity 10000
```

### Configure Signing in Android Studio
1. Open `android/app/build.gradle`
2. Add signing configuration:

```gradle
android {
    signingConfigs {
        release {
            storeFile file('dccp-hub.keystore')
            storePassword 'your-store-password'
            keyAlias 'dccp-hub'
            keyPassword 'your-key-password'
        }
    }
    buildTypes {
        release {
            signingConfig signingConfigs.release
            minifyEnabled false
            proguardFiles getDefaultProguardFile('proguard-android.txt'), 'proguard-rules.pro'
        }
    }
}
```

## Step 6: APK Locations

After building, APK files will be located at:
- **Debug APK**: `android/app/build/outputs/apk/debug/app-debug.apk`
- **Release APK**: `android/app/build/outputs/apk/release/app-release.apk`

## Troubleshooting

### Common Issues

1. **SDK location not found**
   - Ensure ANDROID_HOME is set correctly
   - Create `android/local.properties` with: `sdk.dir=/path/to/android/sdk`

2. **Gradle build fails**
   - Update Gradle wrapper: `./gradlew wrapper --gradle-version=8.0`
   - Clean and rebuild: `./gradlew clean assembleDebug`

3. **Capacitor sync issues**
   - Remove and re-add Android platform:
     ```bash
     npx cap remove android
     npx cap add android
     ```

4. **Build tools version issues**
   - Update build tools in Android Studio SDK Manager
   - Ensure compileSdkVersion matches installed SDK

### Performance Tips

1. **Enable Gradle Daemon**
   ```bash
   echo "org.gradle.daemon=true" >> ~/.gradle/gradle.properties
   ```

2. **Increase Gradle Memory**
   ```bash
   echo "org.gradle.jvmargs=-Xmx4g -XX:MaxMetaspaceSize=512m" >> ~/.gradle/gradle.properties
   ```

3. **Use Parallel Builds**
   ```bash
   echo "org.gradle.parallel=true" >> ~/.gradle/gradle.properties
   ```

## APK Distribution

Once built, copy the APK to the storage directory:
```bash
cp android/app/build/outputs/apk/debug/app-debug.apk storage/app/apk/DCCPHub_latest.apk
```

The APK will be available for download at:
`https://portal.dccp.edu.ph/storage/apk/DCCPHub_latest.apk`

## Next Steps

1. Test the APK on physical Android devices
2. Set up automated builds with GitHub Actions
3. Configure app signing for Play Store distribution
4. Implement update notifications in the app
