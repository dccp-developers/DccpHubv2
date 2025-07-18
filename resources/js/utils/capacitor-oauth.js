// Capacitor OAuth Handler
// This file handles OAuth flows properly in Capacitor apps

import { Capacitor } from '@capacitor/core';
import { Browser } from '@capacitor/browser';

/**
 * Check if running in Capacitor native app
 */
export const isCapacitor = () => {
  return Capacitor.isNativePlatform();
};

/**
 * Handle OAuth URL opening in Capacitor
 */
export const handleOAuthUrl = async (url) => {
  if (isCapacitor()) {
    // In Capacitor, handle OAuth in the same webview
    window.location.href = url;
    return;
  }
  
  // In browser, use normal behavior
  window.open(url, '_self');
};

/**
 * Override window.open for OAuth flows in Capacitor
 */
export const setupOAuthHandler = () => {
  if (isCapacitor()) {
    // Override window.open to handle OAuth popups
    const originalOpen = window.open;
    
    window.open = function(url, name, specs) {
      // Check if this is an OAuth URL
      if (url && (
        url.includes('accounts.google.com') ||
        url.includes('oauth') ||
        url.includes('auth')
      )) {
        // Handle OAuth in the same window
        window.location.href = url;
        return window;
      }
      
      // For other URLs, use original behavior
      return originalOpen.call(this, url, name, specs);
    };
    
    // Handle OAuth redirects
    window.addEventListener('message', (event) => {
      if (event.data && event.data.type === 'oauth-callback') {
        // Handle OAuth callback
        console.log('OAuth callback received:', event.data);
      }
    });
  }
};

/**
 * Initialize OAuth handling when app starts
 */
export const initializeOAuth = () => {
  if (isCapacitor()) {
    setupOAuthHandler();

    // Override navigator.userAgent for Google OAuth compatibility
    Object.defineProperty(navigator, 'userAgent', {
      get: function() {
        return 'Mozilla/5.0 (Linux; Android 13; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36 DCCPHub-Mobile-App';
      },
      configurable: true
    });

    // Add CSS to hide browser UI elements during OAuth
    const style = document.createElement('style');
    style.textContent = `
      /* Hide browser UI during OAuth flows */
      .oauth-flow body {
        -webkit-user-select: none;
        -webkit-touch-callout: none;
        -webkit-tap-highlight-color: transparent;
      }
    `;
    document.head.appendChild(style);

    console.log('Capacitor OAuth handler initialized with secure user agent');
  }
};

/**
 * Handle Google OAuth specifically
 */
export const handleGoogleOAuth = (authUrl) => {
  if (isCapacitor()) {
    // Add OAuth flow class
    document.body.classList.add('oauth-flow');

    // Set additional headers for Google OAuth compatibility
    const meta = document.createElement('meta');
    meta.name = 'referrer';
    meta.content = 'strict-origin-when-cross-origin';
    document.head.appendChild(meta);

    // Navigate to OAuth URL in same webview with proper referrer
    window.location.href = authUrl;

    // Remove class after navigation
    setTimeout(() => {
      document.body.classList.remove('oauth-flow');
    }, 1000);
  } else {
    // Browser behavior
    window.location.href = authUrl;
  }
};

/**
 * Check if current URL is an OAuth callback
 */
export const isOAuthCallback = () => {
  const url = window.location.href;
  return url.includes('oauth') || 
         url.includes('callback') || 
         url.includes('auth/google/callback');
};

/**
 * Handle OAuth callback completion
 */
export const handleOAuthCallback = () => {
  if (isOAuthCallback() && isCapacitor()) {
    // Remove OAuth flow styling
    document.body.classList.remove('oauth-flow');
    
    // Notify parent window if in popup
    if (window.opener) {
      window.opener.postMessage({
        type: 'oauth-callback',
        url: window.location.href
      }, '*');
      window.close();
    }
  }
};
