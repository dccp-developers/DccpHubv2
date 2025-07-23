<template>
  <AlertDialog :open="open" @update:open="$emit('update:open', $event)">
    <AlertDialogContent class="sm:max-w-md">
      <AlertDialogHeader>
        <div class="flex items-center space-x-2">
          <div class="flex-shrink-0">
            <ExclamationTriangleIcon class="h-6 w-6 text-orange-500" />
          </div>
          <AlertDialogTitle class="text-lg font-semibold">
            Section Under Development
          </AlertDialogTitle>
        </div>
        <AlertDialogDescription class="text-sm text-muted-foreground mt-2">
          This section is currently being developed and is not yet available. 
          We're working hard to bring you this feature as soon as possible.
        </AlertDialogDescription>
      </AlertDialogHeader>

      <div class="bg-orange-50 dark:bg-orange-950/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4 mt-4">
        <div class="flex items-start space-x-3">
          <ClockIcon class="h-5 w-5 text-orange-600 dark:text-orange-400 mt-0.5 flex-shrink-0" />
          <div class="flex-1">
            <h4 class="text-sm font-medium text-orange-800 dark:text-orange-200">
              What to expect:
            </h4>
            <ul class="mt-2 text-sm text-orange-700 dark:text-orange-300 space-y-1">
              <li>• Full functionality will be available soon</li>
              <li>• You'll be notified when it's ready</li>
              <li>• Thank you for your patience</li>
            </ul>
          </div>
        </div>
      </div>

      <AlertDialogFooter class="mt-6">
        <AlertDialogAction 
          @click="$emit('update:open', false)"
          class="w-full sm:w-auto"
        >
          Got it, thanks!
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>

<script setup>
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/Components/shadcn/ui/alert-dialog'
import { ExclamationTriangleIcon, ClockIcon } from '@heroicons/vue/24/outline'

defineProps({
  open: {
    type: Boolean,
    default: false
  }
})

defineEmits(['update:open'])
</script>
