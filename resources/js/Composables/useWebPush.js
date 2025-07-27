import { ref, onMounted, onUnmounted } from 'vue'
import { useToast } from '@/Components/shadcn/ui/toast'
import axios from 'axios'

export function useWebPush() {
  const { toast } = useToast()
  
  // Reactive state
  const isSupported = ref(false)
  const isSubscribed = ref(false)
  const subscription = ref(null)
  const permission = ref('default')
  const loading = ref(false)
  const error = ref(null)

  /**
   * Check if web push is supported
   */
  const checkSupport = () => {
    isSupported.value = 'serviceWorker' in navigator && 'PushManager' in window
    if (isSupported.value) {
      permission.value = Notification.permission
    }
    return isSupported.value
  }

  /**
   * Register service worker
   */
  const registerServiceWorker = async () => {
    if (!isSupported.value) {
      throw new Error('Web Push is not supported')
    }

    try {
      const registration = await navigator.serviceWorker.register('/notification-sw.js')
      console.log('Service Worker registered:', registration)
      return registration
    } catch (err) {
      console.error('Service Worker registration failed:', err)
      throw err
    }
  }

  /**
   * Request notification permission
   */
  const requestPermission = async () => {
    if (!isSupported.value) {
      throw new Error('Web Push is not supported')
    }

    try {
      const result = await Notification.requestPermission()
      permission.value = result
      
      if (result === 'granted') {
        toast({
          title: 'Success',
          description: 'Notifications enabled successfully!'
        })
        return true
      } else if (result === 'denied') {
        toast({
          title: 'Error',
          description: 'Notifications were denied. Please enable them in your browser settings.',
          variant: 'destructive'
        })
        return false
      } else {
        toast({
          title: 'Warning',
          description: 'Notification permission was dismissed.'
        })
        return false
      }
    } catch (err) {
      console.error('Failed to request permission:', err)
      error.value = err.message
      toast.error('Failed to request notification permission')
      return false
    }
  }

  /**
   * Subscribe to push notifications
   */
  const subscribe = async () => {
    if (!isSupported.value) {
      throw new Error('Web Push is not supported')
    }

    if (permission.value !== 'granted') {
      const granted = await requestPermission()
      if (!granted) {
        return false
      }
    }

    try {
      loading.value = true
      error.value = null

      // Register service worker
      const registration = await registerServiceWorker()

      // Get VAPID public key from environment
      const vapidPublicKey = import.meta.env.VITE_VAPID_PUBLIC_KEY
      if (!vapidPublicKey) {
        throw new Error('VAPID public key not found')
      }

      // Subscribe to push notifications
      const pushSubscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
      })

      subscription.value = pushSubscription
      isSubscribed.value = true

      // Send subscription to server
      await saveSubscription(pushSubscription)

      toast({
        title: 'Success',
        description: 'Successfully subscribed to push notifications!'
      })
      return true
    } catch (err) {
      console.error('Failed to subscribe:', err)
      error.value = err.message
      toast({
        title: 'Error',
        description: 'Failed to subscribe to push notifications',
        variant: 'destructive'
      })
      return false
    } finally {
      loading.value = false
    }
  }

  /**
   * Unsubscribe from push notifications
   */
  const unsubscribe = async () => {
    if (!subscription.value) {
      return true
    }

    try {
      loading.value = true
      error.value = null

      // Unsubscribe from push manager
      await subscription.value.unsubscribe()

      // Remove subscription from server
      await removeSubscription(subscription.value)

      subscription.value = null
      isSubscribed.value = false

      toast({
        title: 'Success',
        description: 'Successfully unsubscribed from push notifications'
      })
      return true
    } catch (err) {
      console.error('Failed to unsubscribe:', err)
      error.value = err.message
      toast({
        title: 'Error',
        description: 'Failed to unsubscribe from push notifications',
        variant: 'destructive'
      })
      return false
    } finally {
      loading.value = false
    }
  }

  /**
   * Save subscription to server
   */
  const saveSubscription = async (pushSubscription) => {
    try {
      await axios.post('/api/webpush/subscribe', {
        subscription: pushSubscription.toJSON()
      })
    } catch (err) {
      console.error('Failed to save subscription:', err)
      throw err
    }
  }

  /**
   * Remove subscription from server
   */
  const removeSubscription = async (pushSubscription) => {
    try {
      await axios.post('/api/webpush/unsubscribe', {
        subscription: pushSubscription.toJSON()
      })
    } catch (err) {
      console.error('Failed to remove subscription:', err)
      throw err
    }
  }

  /**
   * Check existing subscription
   */
  const checkSubscription = async () => {
    if (!isSupported.value) {
      return false
    }

    try {
      const registration = await navigator.serviceWorker.getRegistration('/notification-sw.js')
      if (!registration) {
        return false
      }

      const pushSubscription = await registration.pushManager.getSubscription()
      if (pushSubscription) {
        subscription.value = pushSubscription
        isSubscribed.value = true
        return true
      }

      return false
    } catch (err) {
      console.error('Failed to check subscription:', err)
      return false
    }
  }

  /**
   * Convert VAPID key
   */
  const urlBase64ToUint8Array = (base64String) => {
    const padding = '='.repeat((4 - base64String.length % 4) % 4)
    const base64 = (base64String + padding)
      .replace(/-/g, '+')
      .replace(/_/g, '/')

    const rawData = window.atob(base64)
    const outputArray = new Uint8Array(rawData.length)

    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i)
    }
    return outputArray
  }

  /**
   * Initialize web push
   */
  const initialize = async () => {
    if (!checkSupport()) {
      console.warn('Web Push is not supported')
      return false
    }

    // Check existing subscription
    await checkSubscription()
    return true
  }

  // Auto-initialize on mount
  onMounted(() => {
    initialize()
  })

  return {
    // State
    isSupported,
    isSubscribed,
    subscription,
    permission,
    loading,
    error,

    // Methods
    checkSupport,
    requestPermission,
    subscribe,
    unsubscribe,
    checkSubscription,
    initialize
  }
}
