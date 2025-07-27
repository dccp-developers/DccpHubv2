<template>
  <div class="min-h-screen bg-background flex items-center justify-center p-4">
    <div class="max-w-md w-full">
      <!-- Modal Card -->
      <div class="bg-card border border-border rounded-lg shadow-lg p-6">
        <!-- Icon -->
        <div class="flex justify-center mb-4">
          <div :class="[
            'w-16 h-16 rounded-full flex items-center justify-center',
            statusConfig.bgColor
          ]">
            <component :is="statusConfig.icon" :class="[
              'w-8 h-8',
              statusConfig.iconColor
            ]" />
          </div>
        </div>

        <!-- Title -->
        <h1 class="text-xl font-semibold text-center text-foreground mb-2">
          {{ title }}
        </h1>

        <!-- Message -->
        <p class="text-muted-foreground text-center mb-6">
          {{ message }}
        </p>

        <!-- Route Info (Development Mode) -->
        <div v-if="$page.props.app?.env === 'local' && routeName" 
             class="bg-muted rounded-md p-3 mb-6">
          <p class="text-xs text-muted-foreground">
            <strong>Route:</strong> {{ routeName }}
          </p>
          <p class="text-xs text-muted-foreground">
            <strong>Status:</strong> {{ status }}
          </p>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-3">
          <!-- Go Back Button -->
          <Button
            v-if="canGoBack"
            variant="outline"
            class="flex-1"
            @click="goBack"
          >
            <ArrowLeftIcon class="w-4 h-4 mr-2" />
            Go Back
          </Button>

          <!-- Home Button -->
          <Button
            variant="default"
            class="flex-1"
            @click="goHome"
          >
            <HomeIcon class="w-4 h-4 mr-2" />
            Go to {{ homeLabel }}
          </Button>
        </div>

        <!-- Additional Actions for Specific Statuses -->
        <div v-if="status === 'maintenance'" class="mt-4 pt-4 border-t border-border">
          <p class="text-xs text-muted-foreground text-center">
            You can check our status page for updates or contact support if this is urgent.
          </p>
          <div class="flex justify-center gap-2 mt-2">
            <Button variant="ghost" size="sm" @click="refreshPage">
              <ArrowPathIcon class="w-4 h-4 mr-1" />
              Refresh
            </Button>
            <Button variant="ghost" size="sm" as="a" href="mailto:support@dccphub.com">
              <EnvelopeIcon class="w-4 h-4 mr-1" />
              Contact Support
            </Button>
          </div>
        </div>

        <div v-else-if="status === 'development'" class="mt-4 pt-4 border-t border-border">
          <p class="text-xs text-muted-foreground text-center">
            This feature is coming soon! Follow our updates for the latest information.
          </p>
          <div class="flex justify-center mt-2">
            <Button variant="ghost" size="sm" @click="refreshPage">
              <ArrowPathIcon class="w-4 h-4 mr-1" />
              Check Again
            </Button>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="text-center mt-6">
        <p class="text-xs text-muted-foreground">
          &copy; {{ new Date().getFullYear() }} DccpHub. All rights reserved.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Button } from '@/Components/shadcn/ui/button'
import {
  XCircleIcon,
  ExclamationTriangleIcon,
  WrenchScrewdriverIcon,
  CodeBracketIcon,
  ArrowLeftIcon,
  HomeIcon,
  ArrowPathIcon,
  EnvelopeIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  status: {
    type: String,
    required: true,
  },
  title: {
    type: String,
    required: true,
  },
  message: {
    type: String,
    required: true,
  },
  routeName: {
    type: String,
    default: null,
  },
  canGoBack: {
    type: Boolean,
    default: false,
  },
})

// Status configuration
const statusConfig = computed(() => {
  const configs = {
    disabled: {
      icon: XCircleIcon,
      bgColor: 'bg-destructive/10',
      iconColor: 'text-destructive',
    },
    maintenance: {
      icon: WrenchScrewdriverIcon,
      bgColor: 'bg-warning/10',
      iconColor: 'text-warning',
    },
    development: {
      icon: CodeBracketIcon,
      bgColor: 'bg-info/10',
      iconColor: 'text-info',
    },
  }

  return configs[props.status] || configs.disabled
})

// Determine home route and label based on user role
const homeLabel = computed(() => {
  const user = $page.props.auth?.user
  if (!user) return 'Home'
  
  switch (user.role) {
    case 'faculty':
      return 'Faculty Dashboard'
    case 'admin':
      return 'Admin Panel'
    case 'student':
      return 'Student Dashboard'
    default:
      return 'Dashboard'
  }
})

const homeRoute = computed(() => {
  const user = $page.props.auth?.user
  if (!user) return 'welcome'
  
  switch (user.role) {
    case 'faculty':
      return 'faculty.dashboard'
    case 'admin':
      return 'filament.admin.pages.dashboard'
    case 'student':
      return 'dashboard'
    default:
      return 'dashboard'
  }
})

// Methods
const goBack = () => {
  window.history.back()
}

const goHome = () => {
  router.visit(route(homeRoute.value))
}

const refreshPage = () => {
  window.location.reload()
}
</script>

<style scoped>
/* Custom styles for better visual hierarchy */
.bg-info\/10 {
  background-color: rgb(59 130 246 / 0.1);
}

.text-info {
  color: rgb(59 130 246);
}

.bg-warning\/10 {
  background-color: rgb(245 158 11 / 0.1);
}

.text-warning {
  color: rgb(245 158 11);
}
</style>
