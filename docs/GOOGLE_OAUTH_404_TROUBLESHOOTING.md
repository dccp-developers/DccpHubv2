# Google OAuth 404 Troubleshooting Guide

## ✅ **Issues Fixed**

The 404 error when logging in with Google from the APK has been resolved with the following fixes:

### **1. Corrected Android Client ID**
**Problem**: Using client secret instead of client ID
**Solution**: Updated to use the same web client ID for Android

**Before:**
```typescript
androidClientId: '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' // This was the client secret
```

**After:**
```typescript
androidClientId: '470723514249-74rnbvnh6q640l0iok6src87tk6b76oe.apps.googleusercontent.com' // Correct client ID
```

### **2. Enhanced Error Handling**
- ✅ Added detailed logging to OAuth callback
- ✅ Added fallback to regular OAuth flow
- ✅ Better error messages for debugging

### **3. Route Verification**
- ✅ Confirmed mobile OAuth routes are registered
- ✅ Cleared route cache
- ✅ Added test endpoint for verification

## 📱 **New APK Built**

### **APK Details:**
- **File**: `DCCPHub_clean_debug_20250709_152145.apk`
- **Size**: `11MB`
- **Features**: Fixed Google OAuth + Better Error Handling
- **Download**: `https://portal.dccp.edu.ph/storage/apk/DCCPHub_latest.apk`

## 🔧 **Configuration Applied**

### **Capacitor Config (`capacitor.config.ts`):**
```typescript
SocialLogin: {
  google: {
    webClientId: '470723514249-74rnbvnh6q640l0iok6src87tk6b76oe.apps.googleusercontent.com',
    androidClientId: '470723514249-74rnbvnh6q640l0iok6src87tk6b76oe.apps.googleusercontent.com'
  }
}
```

### **Environment Variables (`.env`):**
```env
GOOGLE_CLIENT_ID=470723514249-74rnbvnh6q640l0iok6src87tk6b76oe.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=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
GOOGLE_REDIRECT_URI=/auth/callback/google
```

## 🔄 **OAuth Flow Options**

### **Option 1: Capacitor Social Login (Primary)**
1. **User clicks** "Login with Google"
2. **Plugin attempts** native OAuth
3. **On success**: Posts to `/auth/google/callback/mobile`
4. **On failure**: Falls back to Option 2

### **Option 2: Deep Link Fallback**
1. **Opens system browser** with OAuth URL
2. **Includes deep link** redirect: `dccphub://auth/google/callback`
3. **Browser completes** OAuth
4. **App receives** deep link callback

### **Option 3: Regular Web OAuth (Final Fallback)**
1. **Redirects to** `/auth/redirect/google`
2. **Standard Laravel** Socialite flow
3. **Web-based** authentication

## 🧪 **Testing Instructions**

### **1. Install New APK**
```bash
# Download latest APK with fixes
https://portal.dccp.edu.ph/storage/apk/DCCPHub_latest.apk

# Install on Android device
# Enable Unknown Sources first
```

### **2. Test Google OAuth**
1. **Open DCCPHub app**
2. **Click "Login with Google"**
3. **Check browser console** for logs (if using Chrome DevTools)
4. **Expected**: Successful login without 404 error

### **3. Debug Steps (If Still Getting 404)**

#### **Check Route Registration:**
```bash
# On server, verify routes exist
php artisan route:list | grep "auth.*mobile"
```

#### **Test Mobile OAuth Endpoint:**
```bash
# Test if endpoint is accessible
curl -X GET https://portal.dccp.edu.ph/auth/mobile/test
```

#### **Check Laravel Logs:**
```bash
# Monitor logs during OAuth attempt
tail -f storage/logs/laravel.log
```

## 🔍 **Common Issues & Solutions**

### **Issue 1: Still Getting 404**
**Possible Causes:**
- Route cache not cleared
- Web server configuration
- Laravel configuration cache

**Solutions:**
```bash
# Clear all caches
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### **Issue 2: OAuth Plugin Not Working**
**Symptoms:**
- Plugin initialization fails
- Falls back to deep link immediately

**Solutions:**
1. **Check Google Cloud Console** OAuth configuration
2. **Verify client IDs** are correct
3. **Check Android package name** matches app ID

### **Issue 3: Deep Link Not Working**
**Symptoms:**
- System browser opens but doesn't return to app
- Deep link callback not received

**Solutions:**
1. **Check Android manifest** deep link configuration
2. **Verify URL scheme** `dccphub://` is registered
3. **Test deep link** manually: `adb shell am start -W -a android.intent.action.VIEW -d "dccphub://auth/test"`

## 📋 **Google Cloud Console Setup**

### **Required OAuth 2.0 Credentials:**

1. **Web Application** (for web and token validation)
   - **Client ID**: `470723514249-74rnbvnh6q640l0iok6src87tk6b76oe.apps.googleusercontent.com`
   - **Client Secret**: `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`

2. **Authorized Redirect URIs:**
   ```
   https://portal.dccp.edu.ph/auth/callback/google
   dccphub://auth/google/callback
   ```

3. **Authorized JavaScript Origins:**
   ```
   https://portal.dccp.edu.ph
   ```

### **Optional: Android Application Credentials**
For better security, you can create separate Android credentials:
1. **Application Type**: Android
2. **Package Name**: `ph.edu.dccp.hub`
3. **SHA-1 Certificate Fingerprint**: (from your keystore)

## 🚀 **Expected Results**

After installing the new APK:

1. **Google OAuth** works within the app
2. **No 404 errors** during authentication
3. **Successful login** and redirect to dashboard
4. **Fallback mechanisms** work if primary method fails

## 📚 **Debugging Tools**

### **Chrome DevTools (for WebView debugging):**
1. **Enable WebView debugging** in app
2. **Connect device** to computer
3. **Open Chrome**: `chrome://inspect`
4. **Select app WebView** and inspect console

### **Android Logs:**
```bash
# View app logs
adb logcat | grep DCCPHub

# View system logs for deep links
adb logcat | grep Intent
```

### **Laravel Logs:**
```bash
# Monitor OAuth attempts
tail -f storage/logs/laravel.log | grep -i oauth
```

## 🎯 **Success Indicators**

✅ **OAuth plugin initializes** without errors  
✅ **Login button** triggers OAuth flow  
✅ **No 404 errors** in network requests  
✅ **Successful authentication** and redirect  
✅ **User logged in** to dashboard  

The Google OAuth 404 issue should now be completely resolved! 🚀📱
