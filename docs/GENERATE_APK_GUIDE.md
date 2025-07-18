# Generate Real APK for DCCPHub

The current APK file is just a placeholder. Follow these steps to generate a real, installable APK:

## Method 1: PWA Builder (Recommended - No Android Studio Required)

### Step 1: Open PWA Builder
1. Go to [https://www.pwabuilder.com/](https://www.pwabuilder.com/)
2. Enter your site URL: `https://portal.dccp.edu.ph`
3. Click "Start" or "Analyze"

### Step 2: Configure Your PWA
1. PWA Builder will analyze your site
2. Review the PWA score and recommendations
3. Click "Package For Stores" or "Build My PWA"

### Step 3: Generate Android APK
1. Select "Android" platform
2. Choose "APK" (not AAB for direct installation)
3. Configure app details:
   - **App Name**: DCCPHub
   - **Package ID**: ph.edu.dccp.hub
   - **Version**: 1.0.0
   - **Icon**: Use your existing icon or upload a new one

### Step 4: Download and Replace
1. Download the generated APK file
2. Rename it to `DCCPHub_latest.apk`
3. Replace the placeholder file:
   ```bash
   # From your project root
   cp /path/to/downloaded/DCCPHub.apk storage/app/apk/DCCPHub_latest.apk
   ```

## Method 2: Using Our Capacitor Setup (Requires Android Studio)

If you want to use Capacitor instead, you'll need to:

1. **Install Android Studio** (see `docs/ANDROID_STUDIO_SETUP.md`)
2. **Set up Android SDK** and environment variables
3. **Run the build script**:
   ```bash
   npm run build:apk
   ```

## Method 3: Quick Manual PWA Builder Steps

1. **Visit**: https://www.pwabuilder.com/
2. **Enter URL**: https://portal.dccp.edu.ph
3. **Click**: "Start" → "Package For Stores" → "Android"
4. **Configure**:
   - Package ID: `ph.edu.dccp.hub`
   - App Name: `DCCPHub`
   - Version: `1.0.0`
5. **Download** the APK
6. **Replace** the placeholder file in `storage/app/apk/DCCPHub_latest.apk`

## Testing the APK

Once you have a real APK:

1. **Enable Unknown Sources** on your Android device:
   - Settings → Security → Unknown Sources (enable)
   - Or Settings → Apps → Special Access → Install Unknown Apps

2. **Download and Install**:
   - Visit: https://portal.dccp.edu.ph/storage/apk/DCCPHub_latest.apk
   - Download the APK
   - Tap to install

3. **Test the App**:
   - Open the installed DCCPHub app
   - Verify it redirects to the login page
   - Test navigation and functionality

## Troubleshooting

### APK Won't Install
- Check if "Unknown Sources" is enabled
- Ensure the APK file is not corrupted
- Try downloading again

### App Doesn't Work Properly
- Check internet connection
- Verify the PWA manifest.json is properly configured
- Test the web version first at https://portal.dccp.edu.ph

### PWA Builder Issues
- Ensure your site has a valid manifest.json
- Check that service worker is registered
- Verify HTTPS is working properly

## File Locations

- **APK Storage**: `storage/app/apk/DCCPHub_latest.apk`
- **Web Access**: `https://portal.dccp.edu.ph/storage/apk/DCCPHub_latest.apk`
- **Download Component**: `resources/js/Components/PWA/MobileAppStatus.vue`

## Next Steps After APK Generation

1. Test the APK on multiple Android devices
2. Consider setting up automated APK builds
3. Plan for Play Store distribution (requires signed APK)
4. Monitor user feedback and app performance
