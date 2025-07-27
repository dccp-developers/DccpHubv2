<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="w-[95vw] max-w-md sm:w-full">
      <DialogHeader>
        <div class="flex items-center space-x-2 sm:space-x-3">
          <!-- Notification Icon -->
          <div
            :class="[
              'w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center flex-shrink-0',
              getIconClasses(notification?.type || 'info')
            ]"
          >
            <component :is="getIcon(notification?.type || 'info')" class="w-4 h-4 sm:w-5 sm:h-5" />
          </div>

          <div class="flex-1 min-w-0">
            <DialogTitle class="text-left">
              {{ notification?.title || 'Notification' }}
            </DialogTitle>
            <div class="flex items-center space-x-2 mt-1">
              <Badge
                v-if="notification?.priority && notification.priority !== 'normal'"
                :variant="getPriorityVariant(notification.priority)"
                class="text-xs"
              >
                {{ notification.priority }}
              </Badge>
              <span class="text-xs text-muted-foreground">
                {{ formatTime(notification?.created_at) }}
              </span>
            </div>
          </div>
        </div>
      </DialogHeader>

      <div class="py-4">
        <DialogDescription class="text-sm text-foreground whitespace-pre-wrap">
          {{ notification?.message || '' }}
        </DialogDescription>

        <!-- Additional Data -->
        <div v-if="notification?.data && Object.keys(notification.data).length > 0" class="mt-4">
          <h4 class="text-sm font-medium mb-2">Additional Information</h4>
          <div class="bg-muted rounded-md p-3">
            <pre class="text-xs text-muted-foreground whitespace-pre-wrap">{{ formatData(notification.data) }}</pre>
          </div>
        </div>
      </div>

      <DialogFooter class="flex-col sm:flex-row gap-2">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-2 w-full sm:w-auto">
          <!-- Mark as Read Button -->
          <Button
            v-if="notification && !notification.is_read"
            variant="outline"
            size="sm"
            @click="handleMarkAsRead"
            class="w-full sm:w-auto"
          >
            <CheckCircleIcon class="w-4 h-4 mr-2" />
            <span class="hidden sm:inline">Mark as Read</span>
            <span class="sm:hidden">Mark Read</span>
          </Button>

          <!-- Action Button -->
          <Button
            v-if="notification?.action_url && notification?.action_text"
            @click="handleActionClick"
            class="w-full sm:w-auto"
          >
            <span class="truncate">{{ notification.action_text }}</span>
            <ArrowTopRightOnSquareIcon class="w-4 h-4 ml-2 flex-shrink-0" />
          </Button>

          <!-- Close Button -->
          <Button
            variant="ghost"
            @click="$emit('update:open', false)"
            class="w-full sm:w-auto"
          >
            Close
          </Button>
        </div>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { computed } from 'vue'
import { Button } from '@/Components/ui/button.js'
import { Badge } from '@/Components/ui/badge.js'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/shadcn/ui/dialog'
import {
  InformationCircleIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
  ArrowTopRightOnSquareIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  open: {
    type: Boolean,
    default: false
  },
  notification: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:open', 'mark-read', 'action-clicked'])

// Get icon based on notification type
const getIcon = (type) => {
  const icons = {
    info: InformationCircleIcon,
    success: CheckCircleIcon,
    warning: ExclamationTriangleIcon,
    error: XCircleIcon,
  }
  return icons[type] || InformationCircleIcon
}

// Get icon classes based on notification type
const getIconClasses = (type) => {
  const classes = {
    info: 'bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400',
    success: 'bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400',
    warning: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/20 dark:text-yellow-400',
    error: 'bg-red-100 text-red-600 dark:bg-red-900/20 dark:text-red-400',
  }
  return classes[type] || classes.info
}

// Get priority variant for badge
const getPriorityVariant = (priority) => {
  const variants = {
    low: 'secondary',
    normal: 'secondary',
    high: 'default',
    urgent: 'destructive',
  }
  return variants[priority] || 'secondary'
}

// Format time relative to now
const formatTime = (timestamp) => {
  if (!timestamp) return ''
  
  const date = new Date(timestamp)
  const now = new Date()
  const diffInSeconds = Math.floor((now - date) / 1000)

  if (diffInSeconds < 60) {
    return 'Just now'
  } else if (diffInSeconds < 3600) {
    const minutes = Math.floor(diffInSeconds / 60)
    return `${minutes} minute${minutes > 1 ? 's' : ''} ago`
  } else if (diffInSeconds < 86400) {
    const hours = Math.floor(diffInSeconds / 3600)
    return `${hours} hour${hours > 1 ? 's' : ''} ago`
  } else if (diffInSeconds < 604800) {
    const days = Math.floor(diffInSeconds / 86400)
    return `${days} day${days > 1 ? 's' : ''} ago`
  } else {
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }
}

// Format additional data for display
const formatData = (data) => {
  if (!data) return ''
  
  try {
    return JSON.stringify(data, null, 2)
  } catch (error) {
    return String(data)
  }
}

// Handle mark as read
const handleMarkAsRead = () => {
  if (props.notification) {
    emit('mark-read', props.notification.id)
  }
}

// Handle action click
const handleActionClick = () => {
  if (props.notification) {
    emit('action-clicked', props.notification)
  }
}
</script>
