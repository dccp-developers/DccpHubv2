// Capacitor Social Login Handler
// This file handles OAuth flows using the @capgo/capacitor-social-login plugin

import { Capacitor } from '@capacitor/core';
import { SocialLogin } from '@capgo/capacitor-social-login';

/**
 * Check if running in Capacitor native app
 */
export const isCapacitor = () => {
  return Capacitor.isNativePlatform();
};

/**
 * Initialize Social Login plugin
 */
export const initializeSocialLogin = async () => {
  if (isCapacitor()) {
    try {
      await SocialLogin.initialize({
        google: {
          webClientId: '470723514249-74rnbvnh6q640l0iok6src87tk6b76oe.apps.googleusercontent.com',
          androidClientId: '470723514249-74rnbvnh6q640l0iok6src87tk6b76oe.apps.googleusercontent.com',
          scopes: ['profile', 'email'],
          serverClientId: '470723514249-74rnbvnh6q640l0iok6src87tk6b76oe.apps.googleusercontent.com'
        }
      });
      console.log('Social Login initialized successfully');
    } catch (error) {
      console.error('Failed to initialize Social Login:', error);
    }
  }
};

/**
 * Handle Google OAuth login using Capacitor Social Login
 */
export const loginWithGoogle = async () => {
  if (!isCapacitor()) {
    // Fallback to web OAuth for browser
    window.location.href = '/auth/google';
    return;
  }

  try {
    console.log('Starting Google login with Capacitor Social Login...');
    
    const result = await SocialLogin.login({
      provider: 'google',
      options: {
        scopes: ['profile', 'email']
      }
    });

    console.log('Google login result:', result);

    if (result.accessToken) {
      // Send the access token to your Laravel backend
      await handleOAuthCallback(result);
    } else {
      throw new Error('No access token received');
    }

  } catch (error) {
    console.error('Google login failed:', error);
    
    // Fallback to deep link OAuth if plugin fails
    console.log('Falling back to deep link OAuth...');
    await fallbackToDeepLinkOAuth();
  }
};

/**
 * Handle OAuth callback with access token
 */
const handleOAuthCallback = async (result) => {
  try {
    console.log('Handling OAuth callback with result:', result);

    const response = await fetch('/auth/google/callback/mobile', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        access_token: result.accessToken,
        id_token: result.idToken,
        provider: 'google',
        user_info: result.user
      })
    });

    console.log('OAuth callback response status:', response.status);

    if (response.ok) {
      const data = await response.json();
      console.log('OAuth callback successful:', data);

      // Redirect to dashboard or intended page
      window.location.href = data.redirect_url || '/dashboard';
    } else {
      const errorText = await response.text();
      console.error('OAuth callback failed:', response.status, errorText);
      throw new Error(`OAuth callback failed: ${response.status} - ${errorText}`);
    }
  } catch (error) {
    console.error('OAuth callback error:', error);

    // Fallback to regular OAuth flow
    console.log('Falling back to regular OAuth flow...');
    window.location.href = '/auth/redirect/google';
  }
};

/**
 * Fallback to deep link OAuth if plugin fails
 */
const fallbackToDeepLinkOAuth = async () => {
  try {
    // Create OAuth URL with deep link redirect
    const oauthUrl = new URL('/auth/google', window.location.origin);
    oauthUrl.searchParams.set('redirect_uri', 'dccphub://auth/google/callback');
    oauthUrl.searchParams.set('mobile', 'true');

    console.log('Opening OAuth URL with deep link:', oauthUrl.toString());
    
    // Open OAuth URL in system browser
    window.open(oauthUrl.toString(), '_system');
    
  } catch (error) {
    console.error('Deep link OAuth fallback failed:', error);
  }
};

/**
 * Handle deep link OAuth callback
 */
export const handleDeepLinkCallback = (url) => {
  console.log('Handling deep link callback:', url);

  try {
    const urlObj = new URL(url);

    if (urlObj.protocol === 'dccphub:' && urlObj.pathname.includes('/auth/')) {
      const params = new URLSearchParams(urlObj.search);
      const authenticated = params.get('authenticated');
      const success = params.get('success');
      const linked = params.get('linked');
      const provider = params.get('provider');
      const error = params.get('error');

      if (error) {
        console.error('OAuth error from deep link:', error);
        // Show error message to user
        alert('Authentication failed: ' + error);
        return;
      }

      if (authenticated === 'true' && success === 'true') {
        console.log('OAuth authentication successful via deep link');

        // Show success message
        if (linked === 'true') {
          console.log(`${provider} account linked successfully`);
          // Redirect to profile page
          window.location.href = '/profile';
        } else {
          console.log(`Logged in successfully with ${provider}`);
          // Redirect to dashboard
          window.location.href = '/dashboard';
        }
      }
    }
  } catch (error) {
    console.error('Error handling deep link callback:', error);
  }
};



/**
 * Logout from social login
 */
export const logoutFromSocialLogin = async () => {
  if (isCapacitor()) {
    try {
      await SocialLogin.logout({
        provider: 'google'
      });
      console.log('Logged out from social login');
    } catch (error) {
      console.error('Social login logout error:', error);
    }
  }
};

/**
 * Check if user is logged in via social login
 */
export const checkSocialLoginStatus = async () => {
  if (isCapacitor()) {
    try {
      const status = await SocialLogin.getAuthorizationStatus({
        provider: 'google'
      });
      return status.isAuthorized;
    } catch (error) {
      console.error('Error checking social login status:', error);
      return false;
    }
  }
  return false;
};

/**
 * Setup deep link listener
 */
export const setupDeepLinkListener = () => {
  if (isCapacitor()) {
    // Listen for app URL events (deep links)
    window.addEventListener('appUrlOpen', (event) => {
      console.log('App opened via deep link:', event.url);
      handleDeepLinkCallback(event.url);
    });

    // Also listen for Capacitor URL events
    document.addEventListener('capacitor:urlOpen', (event) => {
      console.log('Capacitor URL open:', event.detail.url);
      handleDeepLinkCallback(event.detail.url);
    });
  }
};
