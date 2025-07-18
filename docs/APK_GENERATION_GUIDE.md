# DCCPHub APK Generation Guide

This guide explains how to generate an Android APK file for DCCPHub using PWA Builder.

## Prerequisites

1. **PWA Requirements Met**
   - ✅ HTTPS enabled (https://portal.dccp.edu.ph)
   - ✅ Valid manifest.json
   - ✅ Service worker registered
   - ✅ All PWA icons available

## Method 1: PWA Builder Web Interface (Recommended)

### Step 1: Access PWA Builder
1. Go to [https://www.pwabuilder.com/](https://www.pwabuilder.com/)
2. Enter your PWA URL: `https://portal.dccp.edu.ph`
3. Click "Start" to analyze your PWA

### Step 2: Generate Android Package
1. Click on "Android" platform
2. Configure the following settings:
   - **Package ID**: `ph.edu.dccp.hub`
   - **App Name**: `DCCPHub`
   - **Display Mode**: `standalone`
   - **Orientation**: `portrait`
   - **Theme Color**: `#000000`
   - **Background Color**: `#ffffff`

### Step 3: Download APK
1. Click "Generate Package"
2. Wait for the build process to complete
3. Download the generated APK file
4. Rename it to `DCCPHub_v1.0.0.apk`

### Step 4: Deploy to Public Directory
1. Upload the APK to `public/downloads/DCCPHub_latest.apk`
2. Update the download link in the mobile app component

## Method 2: Automated Generation (Future Implementation)

### Using PWA Builder API
```bash
# Install PWA Builder CLI
npm install -g @pwabuilder/cli

# Generate APK (when API is available)
pwa package --platform android --url https://portal.dccp.edu.ph
```

### Using Capacitor (Alternative)
```bash
# Install Capacitor
npm install @capacitor/core @capacitor/cli @capacitor/android

# Initialize Capacitor
npx cap init DCCPHub ph.edu.dccp.hub

# Add Android platform
npx cap add android

# Build and generate APK
npm run build
npx cap copy
npx cap open android
```

## APK Signing (Production)

### Generate Keystore
```bash
keytool -genkey -v -keystore dccp-hub.keystore -alias dccp-hub -keyalg RSA -keysize 2048 -validity 10000
```

### Sign APK
```bash
jarsigner -verbose -sigalg SHA1withRSA -digestalg SHA1 -keystore dccp-hub.keystore DCCPHub.apk dccp-hub
```

### Optimize APK
```bash
zipalign -v 4 DCCPHub.apk DCCPHub-signed.apk
```

## Testing Checklist

- [ ] APK installs successfully on Android device
- [ ] App opens and redirects to login page
- [ ] All PWA features work offline
- [ ] Icons and splash screen display correctly
- [ ] App behaves like a native Android app
- [ ] No browser UI elements visible

## Deployment Steps

1. **Generate APK** using PWA Builder web interface
2. **Test APK** on multiple Android devices
3. **Upload to server**: Place in `public/downloads/DCCPHub_latest.apk`
4. **Update download links** in the mobile app component
5. **Test download** from the website

## File Locations

- **APK Storage**: `public/downloads/DCCPHub_latest.apk`
- **Download URL**: `https://portal.dccp.edu.ph/downloads/DCCPHub_latest.apk`
- **Manifest**: `https://portal.dccp.edu.ph/manifest.json`
- **Service Worker**: `https://portal.dccp.edu.ph/sw.js`

## Troubleshooting

### APK Won't Install
- Enable "Install from unknown sources" in Android settings
- Check if APK is properly signed
- Verify APK is not corrupted

### App Doesn't Work Offline
- Check service worker registration
- Verify caching strategies
- Test network connectivity

### Icons Not Displaying
- Verify icon paths in manifest.json
- Check icon file formats (PNG recommended)
- Ensure all icon sizes are available

## Security Considerations

- Always sign APKs for production
- Use HTTPS for all resources
- Implement proper CSP headers
- Validate all user inputs

## Future Enhancements

- Implement automatic APK generation on deployment
- Add APK versioning and update notifications
- Create CI/CD pipeline for APK builds
- Add Google Play Store distribution
