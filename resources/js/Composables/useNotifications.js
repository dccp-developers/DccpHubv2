import { ref, onMounted, onUnmounted } from 'vue'
import { useToast } from '@/Components/shadcn/ui/toast'
import axios from 'axios'

export function useNotifications() {
  const { toast } = useToast()
  
  // Reactive state
  const notifications = ref([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const error = ref(null)
  
  // Polling interval
  let pollingInterval = null
  const POLLING_INTERVAL = 30000 // 30 seconds

  /**
   * Fetch unread notification count
   */
  const fetchUnreadCount = async () => {
    try {
      const response = await axios.get('/api/notifications/unread-count')
      if (response.data.success) {
        unreadCount.value = response.data.count
      }
    } catch (err) {
      console.error('Failed to fetch unread count:', err)
    }
  }

  /**
   * Fetch notifications
   */
  const fetchNotifications = async (page = 1, perPage = 15, unreadOnly = false) => {
    try {
      loading.value = true
      error.value = null
      
      const response = await axios.get('/api/notifications', {
        params: {
          page,
          per_page: perPage,
          unread_only: unreadOnly
        }
      })

      if (response.data.success) {
        notifications.value = response.data.data.data
        unreadCount.value = response.data.unread_count
        return response.data.data
      }
    } catch (err) {
      error.value = err.message
      console.error('Failed to fetch notifications:', err)
      toast({
        title: 'Error',
        description: 'Failed to load notifications',
        variant: 'destructive'
      })
    } finally {
      loading.value = false
    }
  }

  /**
   * Mark notification as read
   */
  const markAsRead = async (notificationId) => {
    try {
      const response = await axios.patch(`/api/notifications/${notificationId}/read`)
      
      if (response.data.success) {
        // Update local state
        const notification = notifications.value.find(n => n.id === notificationId)
        if (notification) {
          notification.is_read = true
          notification.read_at = new Date().toISOString()
        }
        unreadCount.value = response.data.unread_count
        return true
      }
    } catch (err) {
      console.error('Failed to mark notification as read:', err)
      toast({
        title: 'Error',
        description: 'Failed to mark notification as read',
        variant: 'destructive'
      })
      return false
    }
  }

  /**
   * Mark all notifications as read
   */
  const markAllAsRead = async () => {
    try {
      const response = await axios.patch('/api/notifications/mark-all-read')
      
      if (response.data.success) {
        // Update local state
        notifications.value.forEach(notification => {
          notification.is_read = true
          notification.read_at = new Date().toISOString()
        })
        unreadCount.value = 0
        toast({
          title: 'Success',
          description: response.data.message
        })
        return true
      }
    } catch (err) {
      console.error('Failed to mark all notifications as read:', err)
      toast({
        title: 'Error',
        description: 'Failed to mark all notifications as read',
        variant: 'destructive'
      })
      return false
    }
  }

  /**
   * Clear all notifications
   */
  const clearAll = async () => {
    try {
      const response = await axios.delete('/api/notifications/clear-all')
      
      if (response.data.success) {
        notifications.value = []
        unreadCount.value = 0
        toast({
          title: 'Success',
          description: response.data.message
        })
        return true
      }
    } catch (err) {
      console.error('Failed to clear notifications:', err)
      toast({
        title: 'Error',
        description: 'Failed to clear notifications',
        variant: 'destructive'
      })
      return false
    }
  }

  /**
   * Send test notification
   */
  const sendTestNotification = async () => {
    try {
      const response = await axios.post('/api/notifications/test')
      
      if (response.data.success) {
        unreadCount.value = response.data.unread_count
        toast({
          title: 'Success',
          description: response.data.message
        })

        // Refresh notifications to show the new one
        await fetchNotifications()
        return true
      }
    } catch (err) {
      console.error('Failed to send test notification:', err)
      toast({
        title: 'Error',
        description: 'Failed to send test notification',
        variant: 'destructive'
      })
      return false
    }
  }

  /**
   * Start polling for new notifications
   */
  const startPolling = () => {
    if (pollingInterval) {
      clearInterval(pollingInterval)
    }
    
    pollingInterval = setInterval(() => {
      fetchUnreadCount()
    }, POLLING_INTERVAL)
  }

  /**
   * Stop polling
   */
  const stopPolling = () => {
    if (pollingInterval) {
      clearInterval(pollingInterval)
      pollingInterval = null
    }
  }

  /**
   * Handle real-time notification updates
   */
  const handleRealtimeNotification = (notification) => {
    // Add new notification to the beginning of the list
    notifications.value.unshift(notification)
    unreadCount.value += 1
    
    // Show toast notification
    toast({
      title: notification.title,
      description: notification.message,
      action: notification.action_url ? {
        label: notification.action_text || 'View',
        onClick: () => window.open(notification.action_url, '_blank')
      } : undefined
    })
  }

  /**
   * Initialize notifications
   */
  const initialize = () => {
    fetchUnreadCount()
    startPolling()
  }

  /**
   * Cleanup
   */
  const cleanup = () => {
    stopPolling()
  }

  // Auto-initialize on mount
  onMounted(() => {
    initialize()
  })

  // Cleanup on unmount
  onUnmounted(() => {
    cleanup()
  })

  return {
    // State
    notifications,
    unreadCount,
    loading,
    error,
    
    // Methods
    fetchNotifications,
    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
    clearAll,
    sendTestNotification,
    startPolling,
    stopPolling,
    handleRealtimeNotification,
    initialize,
    cleanup
  }
}
