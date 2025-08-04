<script setup>
import { Button } from '@/Components/shadcn/ui/button'
import { Icon } from '@iconify/vue'
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

  // Debug provider object
  console.log('MobileSocialLoginButton provider:', props.provider)
  console.log('Provider slug:', props.provider?.slug)
  console.log('Provider type:', typeof props.provider?.slug)
})

// Handle OAuth login
const handleOAuthLogin = async () => {
  if (isLoading.value || props.disabled) return

  isLoading.value = true

  try {
    if (isMobileApp.value) {
      // Always use external browser with deep link return for mobile app
      await handleMobileAppOAuth()
    } else {
      // Use regular redirect for web
      window.location.href = route('oauth.redirect', { provider: props.provider.slug })
    }
  } catch (error) {
    console.error('OAuth login error:', error)
    // Fallback to regular OAuth
    window.location.href = route('oauth.redirect', { provider: props.provider.slug })
  } finally {
    isLoading.value = false
  }
}

// Handle OAuth in mobile app using external browser with deep link return
const handleMobileAppOAuth = async () => {
  try {
    // Create OAuth URL with deep link redirect
    const baseUrl = window.location.origin
    const oauthUrl = new URL(route('oauth.redirect', { provider: props.provider.slug }), baseUrl)

    // Add deep link redirect URI for returning to the app
    oauthUrl.searchParams.set('redirect_uri', `dccphub://auth/${props.provider.slug}/callback`)
    oauthUrl.searchParams.set('mobile', 'true')
    oauthUrl.searchParams.set('return_to_app', 'true')

    console.log('Opening OAuth in external browser with deep link return:', oauthUrl.toString())

    // Always open in external system browser
    if (window.Capacitor && window.Capacitor.Plugins.Browser) {
      // Use Capacitor Browser plugin to open in system browser
      await window.Capacitor.Plugins.Browser.open({
        url: oauthUrl.toString(),
        windowName: '_system',
        presentationStyle: 'fullscreen'
      })
    } else {
      // Fallback: open in system browser
      window.open(oauthUrl.toString(), '_blank')
    }

    console.log('OAuth flow initiated in external browser. Waiting for deep link callback...')

  } catch (error) {
    console.error('External browser OAuth error:', error)
    throw error
  }
}

// Get provider name with fallback
const getProviderName = () => {
  try {
    if (!props.provider || typeof props.provider !== 'object') {
      console.warn('Invalid provider object:', props.provider)
      return 'Google'
    }

    const slug = props.provider.slug
    if (!slug || typeof slug !== 'string') {
      console.warn('Invalid provider slug:', slug)
      return 'Google'
    }

    // Capitalize first letter and handle common cases
    const name = slug.charAt(0).toUpperCase() + slug.slice(1).toLowerCase()
    return name === 'X' ? 'X' : name
  } catch (error) {
    console.error('Error getting provider name:', error)
    return 'Google'
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
    <template v-if="isLoading">
      Signing in...
    </template>
    <template v-else>
      Sign In With {{ getProviderName() }}
    </template>
  </Button>
</template>
