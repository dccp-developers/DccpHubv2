<template>
  <div class="relative inline-block">
    <slot />
    <Badge
      v-if="count > 0"
      :variant="variant"
      :class="[
        'absolute flex items-center justify-center min-w-[1.25rem] h-5 text-xs font-medium',
        positionClasses,
        pulseAnimation && 'animate-pulse'
      ]"
    >
      {{ displayCount }}
    </Badge>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Badge } from '@/Components/ui/badge.js'

const props = defineProps({
  count: {
    type: Number,
    default: 0
  },
  maxCount: {
    type: Number,
    default: 99
  },
  variant: {
    type: String,
    default: 'destructive',
    validator: (value) => ['default', 'secondary', 'destructive', 'outline'].includes(value)
  },
  position: {
    type: String,
    default: 'top-right',
    validator: (value) => ['top-right', 'top-left', 'bottom-right', 'bottom-left'].includes(value)
  },
  pulseAnimation: {
    type: Boolean,
    default: false
  }
})

// Compute display count with max limit
const displayCount = computed(() => {
  if (props.count > props.maxCount) {
    return `${props.maxCount}+`
  }
  return props.count.toString()
})

// Compute position classes
const positionClasses = computed(() => {
  const positions = {
    'top-right': '-top-1 -right-1',
    'top-left': '-top-1 -left-1',
    'bottom-right': '-bottom-1 -right-1',
    'bottom-left': '-bottom-1 -left-1'
  }
  return positions[props.position] || positions['top-right']
})
</script>
