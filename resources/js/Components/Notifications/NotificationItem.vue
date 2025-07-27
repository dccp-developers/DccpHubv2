<template>
  <div
    @click="$emit('click', notification)"
    :class="[
      'p-3 border-b border-border hover:bg-accent cursor-pointer transition-colors',
      !notification.is_read ? 'bg-primary/5' : ''
    ]"
  >
    <div class="flex items-start space-x-2 sm:space-x-3">
      <!-- Notification Icon -->
      <div class="flex-shrink-0 mt-0.5">
        <div
          :class="[
            'w-6 h-6 sm:w-8 sm:h-8 rounded-full flex items-center justify-center',
            getIconClasses(notification.type)
          ]"
        >
          <component :is="getIcon(notification.type)" class="w-3 h-3 sm:w-4 sm:h-4" />
        </div>
      </div>

      <!-- Notification Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <h4 :class="[
              'text-sm font-medium truncate',
              !notification.is_read ? 'text-foreground' : 'text-muted-foreground'
            ]">
              {{ notification.title }}
            </h4>
            <p :class="[
              'text-xs mt-1 line-clamp-2',
              !notification.is_read ? 'text-foreground/80' : 'text-muted-foreground'
            ]">
              {{ notification.message }}
            </p>
          </div>

          <!-- Priority Badge -->
          <Badge
            v-if="notification.priority !== 'normal'"
            :variant="getPriorityVariant(notification.priority)"
            class="ml-2 text-xs"
          >
            {{ notification.priority }}
          </Badge>
        </div>

        <!-- Notification Footer -->
        <div class="flex items-center justify-between mt-2">
          <span class="text-xs text-muted-foreground">
            {{ formatTime(notification.created_at) }}
          </span>

          <div class="flex items-center space-x-1 sm:space-x-2">
            <!-- Action Button -->
            <Button
              v-if="notification.action_url && notification.action_text"
              variant="ghost"
              size="sm"
              @click.stop="handleActionClick"
              class="h-6 px-1 sm:px-2 text-xs"
            >
              <span class="hidden sm:inline">{{ notification.action_text }}</span>
              <span class="sm:hidden">Action</span>
            </Button>

            <!-- Mark as Read Button -->
            <Button
              v-if="!notification.is_read"
              variant="ghost"
              size="sm"
              @click.stop="$emit('mark-read', notification.id)"
              class="h-6 px-1 sm:px-2 text-xs"
            >
              <span class="hidden sm:inline">Mark read</span>
              <span class="sm:hidden">✓</span>
            </Button>

            <!-- Unread Indicator -->
            <div
              v-if="!notification.is_read"
              class="w-2 h-2 bg-primary rounded-full"
            ></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Button } from '@/Components/ui/button.js'
import { Badge } from '@/Components/ui/badge.js'
import {
  InformationCircleIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  notification: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['click', 'mark-read', 'action-click'])

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
  const date = new Date(timestamp)
  const now = new Date()
  const diffInSeconds = Math.floor((now - date) / 1000)

  if (diffInSeconds < 60) {
    return 'Just now'
  } else if (diffInSeconds < 3600) {
    const minutes = Math.floor(diffInSeconds / 60)
    return `${minutes}m ago`
  } else if (diffInSeconds < 86400) {
    const hours = Math.floor(diffInSeconds / 3600)
    return `${hours}h ago`
  } else if (diffInSeconds < 604800) {
    const days = Math.floor(diffInSeconds / 86400)
    return `${days}d ago`
  } else {
    return date.toLocaleDateString()
  }
}

// Handle action click
const handleActionClick = () => {
  emit('action-click', props.notification)
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
