<script setup>
import { router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/shadcn/ui/card';
import { Button } from '@/Components/shadcn/ui/button';
import { Icon } from '@iconify/vue';

// Quick action items
const quickActions = [
  {
    id: 'schedule',
    title: 'My Schedule',
    description: 'View class timetable',
    icon: 'lucide:calendar',
    color: 'text-blue-600',
    bgColor: 'bg-blue-50 hover:bg-blue-100',
    route: '/schedule'
  },
  {
    id: 'grades',
    title: 'Grades',
    description: 'Check academic performance',
    icon: 'lucide:graduation-cap',
    color: 'text-green-600',
    bgColor: 'bg-green-50 hover:bg-green-100',
    route: '/grades'
  },
  {
    id: 'tuition',
    title: 'Tuition',
    description: 'View payment details',
    icon: 'lucide:credit-card',
    color: 'text-purple-600',
    bgColor: 'bg-purple-50 hover:bg-purple-100',
    route: '/tuition'
  },
  {
    id: 'subjects',
    title: 'Subjects',
    description: 'Enrolled courses',
    icon: 'lucide:book-open',
    color: 'text-orange-600',
    bgColor: 'bg-orange-50 hover:bg-orange-100',
    route: '/subjects'
  },
  {
    id: 'attendance',
    title: 'Attendance',
    description: 'Track class attendance',
    icon: 'lucide:user-check',
    color: 'text-indigo-600',
    bgColor: 'bg-indigo-50 hover:bg-indigo-100',
    route: '/attendance'
  },
  {
    id: 'profile',
    title: 'Profile',
    description: 'Update personal info',
    icon: 'lucide:user',
    color: 'text-gray-600',
    bgColor: 'bg-gray-50 hover:bg-gray-100',
    route: '/profile'
  }
];

// Navigate to action
const navigateToAction = (action) => {
  router.visit(action.route);
};
</script>

<template>
  <Card class="border-0 shadow-sm">
    <CardHeader class="pb-2">
      <CardTitle class="text-sm font-semibold">Quick Actions</CardTitle>
    </CardHeader>
    <CardContent class="p-3">
      <!-- Simplified Grid View -->
      <div class="grid grid-cols-3 md:grid-cols-6 gap-2">
        <button
          v-for="action in (Array.isArray(quickActions) ? quickActions.slice(0, 6) : [])"
          :key="action.id"
          @click="navigateToAction(action)"
          class="group p-2 rounded-lg border border-border/50 hover:bg-accent/50 transition-colors"
        >
          <div class="flex flex-col items-center text-center space-y-1">
            <div :class="`p-1.5 rounded-lg ${action.bgColor} ${action.color}`">
              <Icon :icon="action.icon" class="w-4 h-4" />
            </div>
            <span class="text-xs font-medium truncate">{{ action.title }}</span>
          </div>
        </button>
      </div>
    </CardContent>
  </Card>
</template>

<style scoped>
.animate-pulse-subtle {
  animation: pulse-subtle 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse-subtle {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.8;
  }
}

/* Ensure proper touch targets on mobile */
@media (max-width: 768px) {
  button {
    min-height: 44px;
  }
}
</style>
