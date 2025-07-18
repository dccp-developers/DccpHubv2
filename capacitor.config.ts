import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'ph.edu.dccp.hub',
  appName: 'DCCPHub',
  webDir: 'public',
  includePlugins: [],
  copy: {
    exclude: [
      'storage/**/*',
      'storage/app/apk/**/*',
      '**/*.apk',
      'node_modules/**/*'
    ]
  },
  server: {
    url: 'https://portal.dccp.edu.ph',
    cleartext: true
  },
  android: {
    buildOptions: {
      keystorePath: 'android/app/dccp-hub.keystore',
      keystoreAlias: 'dccp-hub'
    },
    allowMixedContent: true,
    captureInput: true,
    webContentsDebuggingEnabled: false,
    appendUserAgent: 'Chrome/120.0.0.0 Mobile Safari/537.36 DCCPHub-Mobile-App',
    overrideUserAgent: 'Mozilla/5.0 (Linux; Android 13; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36 DCCPHub-Mobile-App',
    // Handle OAuth and external links internally
    allowNavigation: [
      'https://portal.dccp.edu.ph',
      'https://portal.dccp.edu.ph/*',
      'https://accounts.google.com',
      'https://accounts.google.com/*',
      'https://*.google.com',
      'https://*.googleapis.com',
      'https://oauth.googleusercontent.com',
      'https://oauth.googleusercontent.com/*'
    ]
  },
  plugins: {
    SplashScreen: {
      launchShowDuration: 2000,
      backgroundColor: '#ffffff',
      androidSplashResourceName: 'splash',
      showSpinner: false
    },
    Browser: {
      windowName: '_self',
      presentationStyle: 'fullscreen',
      showTitle: false,
      toolbarColor: '#ffffff',
      showNavigationButtons: false,
      showCloseButton: false
    },
    SocialLogin: {
      google: {
        webClientId: '470723514249-74rnbvnh6q640l0iok6src87tk6b76oe.apps.googleusercontent.com',
        androidClientId: '470723514249-74rnbvnh6q640l0iok6src87tk6b76oe.apps.googleusercontent.com'
      }
    }
  }
};

export default config;
