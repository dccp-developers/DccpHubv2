<template>
  <div class="space-y-1">
    <template v-for="item in navigation" :key="item.name">
      <!-- Handle development items with modal -->
      <button
        v-if="item.isDevelopment"
        @click="$emit('showDevelopmentModal')"
        :class="[
          'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
          'group flex items-center w-full px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 text-left'
        ]"
      >
        <component
          :is="item.icon"
          :class="[
            'text-muted-foreground group-hover:text-accent-foreground',
            'mr-3 flex-shrink-0 h-5 w-5 transition-colors duration-200'
          ]"
        />
        <span class="flex-1">{{ item.name }}</span>

        <!-- Development badge -->
        <Badge
          variant="secondary"
          class="ml-auto text-xs bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200"
        >
          Dev
        </Badge>
      </button>

      <!-- Regular navigation items -->
      <Link
        v-else
        :href="item.href"
        :class="[
          item.current
            ? 'bg-primary text-primary-foreground'
            : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
          'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200'
        ]"
      >
        <component
          :is="item.icon"
          :class="[
            item.current ? 'text-primary-foreground' : 'text-muted-foreground group-hover:text-accent-foreground',
            'mr-3 flex-shrink-0 h-5 w-5 transition-colors duration-200'
          ]"
        />
        <span class="flex-1">{{ item.name }}</span>

        <!-- Badge for items with notifications -->
        <Badge
          v-if="item.badge"
          variant="secondary"
          class="ml-auto text-xs"
        >
          {{ item.badge }}
        </Badge>
      </Link>
    </template>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { Badge } from '@/Components/ui/badge.js'

defineProps({
  navigation: {
    type: Array,
    required: true
  }
})

defineEmits(['showDevelopmentModal'])
</script>
