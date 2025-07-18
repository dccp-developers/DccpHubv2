<script setup>
import { Button } from '@/Components/shadcn/ui/button'
import { Icon } from '@iconify/vue'
import { useChangeCase } from '@vueuse/integrations/useChangeCase'
import { ref, onMounted } from 'vue'

const props = defineProps({
  provider: {
    type: Object,
    required: true,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const isMobileApp = ref(false)
const isLoading = ref(false)

// Detect if running in mobile app
onMounted(() => {
  // Check for Capacitor
  isMobileApp.value = !!(window.Capacitor && window.Capacitor.isNativePlatform())
  
  // Also check user agent as fallback
  if (!isMobileApp.value) {
    const userAgent = navigator.userAgent || ''
    isMobileApp.value = userAgent.includes('DCCPHub-Mobile-App') || 
                       userAgent.includes('Capacitor')
  }
})

// Handle OAuth login
const handleOAuthLogin = async () => {
  if (isLoading.value || props.disabled) return
  
  isLoading.value = true
  
  try {
    if (isMobileApp.value) {
      // Use in-app browser for mobile app
      await handleMobileAppOAuth()
    } else {
      // Use regular redirect for web
      window.location.href = route('oauth.redirect', { provider: props.provider.slug })
    }
  } catch (error) {
    console.error('OAuth login error:', error)
    // Fallback to regular redirect
    window.location.href = route('oauth.redirect', { provider: props.provider.slug })
  } finally {
    isLoading.value = false
  }
}

// Handle OAuth in mobile app using Capacitor Browser
const handleMobileAppOAuth = async () => {
  if (!window.Capacitor) {
    throw new Error('Capacitor not available')
  }
  
  const { Browser } = window.Capacitor.Plugins
  
  if (!Browser) {
    throw new Error('Browser plugin not available')
  }
  
  // Get the OAuth URL
  const oauthUrl = route('oauth.redirect', { provider: props.provider.slug })
  
  // Open OAuth flow in in-app browser
  const result = await Browser.open({
    url: oauthUrl,
    windowName: '_self',
    presentationStyle: 'popover',
    showTitle: true,
    toolbarColor: '#ffffff',
    showNavigationButtons: true,
    showCloseButton: true,
    clearCache: false,
    clearSessionCache: false
  })
  
  // Listen for URL changes to detect successful authentication
  Browser.addListener('browserPageLoaded', (info) => {
    console.log('Browser page loaded:', info.url)
    
    // Check if we've been redirected to the dashboard (successful login)
    if (info.url.includes('/dashboard') || info.url.includes('?authenticated=true')) {
      // Close the browser and reload the main app
      Browser.close()
      window.location.reload()
    }
  })
  
  // Handle browser finished (user closed manually)
  Browser.addListener('browserFinished', () => {
    console.log('Browser finished')
    // Check if user is now authenticated by making a request
    checkAuthenticationStatus()
  })
}

// Check if user is now authenticated
const checkAuthenticationStatus = async () => {
  try {
    const response = await fetch('/api/user', {
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      // User is authenticated, redirect to dashboard
      window.location.href = '/dashboard'
    }
  } catch (error) {
    console.log('User not authenticated yet')
  }
}
</script>

<template>
  <Button
    :disabled="disabled || isLoading"
    class="bg-background text-foreground hover:bg-secondary disabled:opacity-50 dark:hover:bg-primary/80 dark:bg-primary dark:text-primary-foreground"
    @click="handleOAuthLogin"
  >
    <Icon 
      v-if="!isLoading"
      :icon="provider.icon" 
      class="mr-2 h-4 w-4" 
    />
    <Icon 
      v-else
      icon="mdi:loading" 
      class="mr-2 h-4 w-4 animate-spin" 
    />
    {{ isLoading ? 'Signing in...' : `Sign In With ${useChangeCase(provider.slug, 'sentenceCase')}` }}
  </Button>
</template>
