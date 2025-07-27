<template>
  <div class="fixed bottom-0 left-0 right-0 z-50 bg-background border-t border-border md:hidden">
    <div class="grid grid-cols-5 h-16">
      <template v-for="item in navigationItems" :key="item.name">
        <!-- Development items -->
        <button
          v-if="item.isDevelopment"
          @click="$emit('showDevelopmentModal')"
          :class="[
            'flex flex-col items-center justify-center space-y-1 text-xs transition-colors relative',
            'text-muted-foreground hover:text-foreground hover:bg-accent'
          ]"
        >
          <component :is="item.icon" class="h-5 w-5" />
          <span class="text-[10px] font-medium">{{ item.name }}</span>
          <div class="absolute -top-1 -right-1 h-3 w-6 bg-orange-500 text-white rounded-full flex items-center justify-center text-[8px] font-bold">
            Dev
          </div>
        </button>

        <!-- Regular navigation items -->
        <Link
          v-else
          :href="item.href"
          :class="[
            'flex flex-col items-center justify-center space-y-1 text-xs transition-colors relative',
            item.current
              ? 'text-primary bg-primary/10'
              : 'text-muted-foreground hover:text-foreground hover:bg-accent'
          ]"
        >
          <component :is="item.icon" class="h-5 w-5" />
          <span class="text-[10px] font-medium">{{ item.name }}</span>
          <div
            v-if="item.badge"
            class="absolute -top-1 -right-1 h-4 w-4 bg-destructive text-destructive-foreground rounded-full flex items-center justify-center text-[8px] font-bold"
          >
            {{ item.badge }}
          </div>
        </Link>
      </template>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import {
  HomeIcon,
  AcademicCapIcon,
  UsersIcon,
  CalendarIcon,
  ChartBarIcon
} from '@heroicons/vue/24/outline'

const navigationItems = [
  {
    name: 'Dashboard',
    href: route('faculty.dashboard'),
    icon: HomeIcon,
    current: route().current('faculty.dashboard')
  },
  {
    name: 'Classes',
    href: route('faculty.classes.index'),
    icon: AcademicCapIcon,
    current: route().current('faculty.classes.*')
  },
  {
    name: 'Students',
    href: route('faculty.students.index'),
    icon: UsersIcon,
    current: route().current('faculty.students.*')
  },
  {
    name: 'Schedule',
    href: route('faculty.schedule.index'),
    icon: CalendarIcon,
    current: route().current('faculty.schedule.*')
  },
  {
    name: 'Grades',
    href: '#',
    icon: ChartBarIcon,
    current: false,
    isDevelopment: true
  }
]

defineEmits(['showDevelopmentModal'])
</script>
