<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button variant="ghost" size="icon" class="relative">
        <BellIcon class="h-5 w-5" />
        <Badge 
          v-if="unreadCount > 0" 
          variant="destructive" 
          class="absolute -top-1 -right-1 h-5 w-5 p-0 text-xs flex items-center justify-center"
        >
          {{ unreadCount > 99 ? '99+' : unreadCount }}
        </Badge>
      </Button>
    </DropdownMenuTrigger>

    <DropdownMenuContent align="end" class="w-80 sm:w-96 max-h-[80vh] sm:max-h-96 overflow-hidden">
      <div class="flex items-center justify-between p-3 border-b">
        <h3 class="font-semibold text-sm">Notifications</h3>
        <div class="flex items-center space-x-1 sm:space-x-2">
          <Button
            v-if="unreadCount > 0"
            variant="ghost"
            size="sm"
            @click="markAllAsRead"
            :disabled="loading"
            class="text-xs h-7 px-2 sm:px-3"
          >
            <span class="hidden sm:inline">Mark all read</span>
            <span class="sm:hidden">Read all</span>
          </Button>
          <Button
            variant="ghost"
            size="sm"
            @click="clearAll"
            :disabled="loading"
            class="text-xs h-7 px-2 sm:px-3 text-destructive hover:text-destructive"
          >
            <span class="hidden sm:inline">Clear all</span>
            <span class="sm:hidden">Clear</span>
          </Button>
        </div>
      </div>

      <div class="max-h-60 sm:max-h-80 overflow-y-auto">
        <div v-if="loading && notifications.length === 0" class="p-4">
          <div class="flex items-center space-x-2">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary"></div>
            <span class="text-sm text-muted-foreground">Loading notifications...</span>
          </div>
        </div>

        <div v-else-if="notifications.length === 0" class="p-8 text-center">
          <BellIcon class="h-12 w-12 mx-auto text-muted-foreground mb-2" />
          <p class="text-sm text-muted-foreground">No notifications yet</p>
        </div>

        <div v-else>
          <NotificationItem
            v-for="notification in notifications"
            :key="notification.id"
            :notification="notification"
            @click="handleNotificationClick"
            @mark-read="markAsRead"
          />
        </div>
      </div>

      <div v-if="hasMore" class="p-3 border-t">
        <Button
          variant="ghost"
          size="sm"
          @click="loadMore"
          :disabled="loading"
          class="w-full text-xs"
        >
          <span v-if="loading">Loading...</span>
          <span v-else>Load more</span>
        </Button>
      </div>
    </DropdownMenuContent>
  </DropdownMenu>

  <!-- Notification Detail Modal -->
  <NotificationModal
    :open="showModal"
    :notification="selectedNotification"
    @update:open="showModal = $event"
    @mark-read="markAsRead"
    @action-clicked="handleActionClick"
  />
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Button } from '@/Components/ui/button.js'
import { Badge } from '@/Components/ui/badge.js'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from '@/Components/shadcn/ui/dropdown-menu'
import { BellIcon } from '@heroicons/vue/24/outline'
import { useToast } from '@/Components/shadcn/ui/toast'
import NotificationItem from './NotificationItem.vue'
import NotificationModal from './NotificationModal.vue'
import axios from 'axios'

const { toast } = useToast()

// Reactive state
const notifications = ref([])
const unreadCount = ref(0)
const loading = ref(false)
const hasMore = ref(false)
const currentPage = ref(1)
const showModal = ref(false)
const selectedNotification = ref(null)

// Load notifications
const loadNotifications = async (page = 1, append = false) => {
  try {
    loading.value = true
    const response = await axios.get('/api/notifications', {
      params: { page, per_page: 10 }
    })

    if (response.data.success) {
      const newNotifications = response.data.data.data
      
      if (append) {
        notifications.value.push(...newNotifications)
      } else {
        notifications.value = newNotifications
      }

      unreadCount.value = response.data.unread_count
      hasMore.value = response.data.data.next_page_url !== null
      currentPage.value = page
    }
  } catch (error) {
    console.error('Failed to load notifications:', error)
    toast({
      title: 'Error',
      description: 'Failed to load notifications',
      variant: 'destructive'
    })
  } finally {
    loading.value = false
  }
}

// Load more notifications
const loadMore = () => {
  if (!loading.value && hasMore.value) {
    loadNotifications(currentPage.value + 1, true)
  }
}

// Mark notification as read
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
    }
  } catch (error) {
    console.error('Failed to mark notification as read:', error)
    toast({
      title: 'Error',
      description: 'Failed to mark notification as read',
      variant: 'destructive'
    })
  }
}

// Mark all as read
const markAllAsRead = async () => {
  try {
    loading.value = true
    const response = await axios.patch('/api/notifications/mark-all-read')
    
    if (response.data.success) {
      notifications.value.forEach(notification => {
        notification.is_read = true
        notification.read_at = new Date().toISOString()
      })
      unreadCount.value = 0
      toast({
        title: 'Success',
        description: response.data.message
      })
    }
  } catch (error) {
    console.error('Failed to mark all notifications as read:', error)
    toast({
      title: 'Error',
      description: 'Failed to mark all notifications as read',
      variant: 'destructive'
    })
  } finally {
    loading.value = false
  }
}

// Clear all notifications
const clearAll = async () => {
  try {
    loading.value = true
    const response = await axios.delete('/api/notifications/clear-all')
    
    if (response.data.success) {
      notifications.value = []
      unreadCount.value = 0
      toast({
        title: 'Success',
        description: response.data.message
      })
    }
  } catch (error) {
    console.error('Failed to clear notifications:', error)
    toast({
      title: 'Error',
      description: 'Failed to clear notifications',
      variant: 'destructive'
    })
  } finally {
    loading.value = false
  }
}

// Handle notification click
const handleNotificationClick = (notification) => {
  selectedNotification.value = notification
  showModal.value = true
  
  // Mark as read if not already read
  if (!notification.is_read) {
    markAsRead(notification.id)
  }
}

// Handle action click
const handleActionClick = (notification) => {
  if (notification.action_url) {
    window.open(notification.action_url, '_blank')
  }
  showModal.value = false
}

// Refresh notifications
const refresh = () => {
  loadNotifications()
}

// Load notifications on mount
onMounted(() => {
  loadNotifications()
})

// Expose methods for parent components
defineExpose({
  refresh,
  loadNotifications
})
</script>
