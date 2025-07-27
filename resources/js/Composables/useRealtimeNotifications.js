import { ref, onMounted, onUnmounted } from 'vue'
import { usePusher } from './usePusher'
import { useNotifications } from './useNotifications'
import { usePage } from '@inertiajs/vue3'

export function useRealtimeNotifications() {
  const { subscribe, listen, unsubscribe, connected } = usePusher()
  const { handleRealtimeNotification, fetchUnreadCount } = useNotifications()
  const page = usePage()
  
  const isListening = ref(false)
  const channelName = ref(null)

  /**
   * Start listening for real-time notifications
   */
  const startListening = () => {
    if (isListening.value) {
      return
    }

    try {
      const user = page.props.auth?.user
      if (!user) {
        console.warn('User not authenticated, cannot listen for notifications')
        return
      }

      // Subscribe to user-specific private channel
      channelName.value = `notifications.${user.id}`
      const channel = subscribe(channelName.value, true)

      if (channel) {
        // Listen for faculty notification events
        listen(channelName.value, 'faculty.notification', (data) => {
          console.log('Received real-time faculty notification:', data)
          handleRealtimeNotification(data)
        })

        // Listen for student notification events
        listen(channelName.value, 'student.notification', (data) => {
          console.log('Received real-time student notification:', data)
          handleRealtimeNotification(data)
        })

        // Listen for notification read events (from other devices)
        listen(channelName.value, 'notification.read', (data) => {
          console.log('Notification marked as read:', data)
          fetchUnreadCount()
        })

        // Listen for bulk notification events
        listen(channelName.value, 'notifications.marked-all-read', () => {
          console.log('All notifications marked as read')
          fetchUnreadCount()
        })

        listen(channelName.value, 'notifications.cleared', () => {
          console.log('All notifications cleared')
          fetchUnreadCount()
        })

        isListening.value = true
        console.log(`Started listening for notifications on channel: ${channelName.value}`)
      }
    } catch (error) {
      console.error('Failed to start listening for notifications:', error)
    }
  }

  /**
   * Stop listening for real-time notifications
   */
  const stopListening = () => {
    if (!isListening.value || !channelName.value) {
      return
    }

    try {
      unsubscribe(channelName.value)
      isListening.value = false
      channelName.value = null
      console.log('Stopped listening for notifications')
    } catch (error) {
      console.error('Failed to stop listening for notifications:', error)
    }
  }

  /**
   * Restart listening (useful for reconnection)
   */
  const restartListening = () => {
    stopListening()
    setTimeout(() => {
      startListening()
    }, 1000)
  }

  // Auto-start listening when component mounts and Pusher is connected
  onMounted(() => {
    // Wait for Pusher to connect before starting to listen
    const checkConnection = () => {
      if (connected.value) {
        startListening()
      } else {
        setTimeout(checkConnection, 1000)
      }
    }
    checkConnection()
  })

  // Cleanup on unmount
  onUnmounted(() => {
    stopListening()
  })

  return {
    // State
    isListening,
    channelName,
    connected,

    // Methods
    startListening,
    stopListening,
    restartListening
  }
}
