<script setup>
import { router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/shadcn/ui/card';
import { Button } from '@/Components/shadcn/ui/button';
import { Badge } from '@/Components/shadcn/ui/badge';
import { Icon } from '@iconify/vue';
import { ref, computed } from 'vue';

// Quick action items with enhanced design
const quickActions = [
  {
    id: 'schedule',
    title: 'My Schedule',
    description: 'View your class timetable',
    icon: 'lucide:calendar-days',
    gradient: 'from-blue-500 to-blue-600',
    bgColor: 'bg-gradient-to-br from-blue-50 to-blue-100',
    hoverColor: 'hover:from-blue-100 hover:to-blue-200',
    textColor: 'text-blue-700',
    route: '/schedule',
    priority: 'high'
  },
  {
    id: 'grades',
    title: 'Grades',
    description: 'Check your academic performance',
    icon: 'lucide:graduation-cap',
    gradient: 'from-emerald-500 to-emerald-600',
    bgColor: 'bg-gradient-to-br from-emerald-50 to-emerald-100',
    hoverColor: 'hover:from-emerald-100 hover:to-emerald-200',
    textColor: 'text-emerald-700',
    route: '/grades',
    priority: 'high'
  },
  {
    id: 'subjects',
    title: 'Subjects',
    description: 'View enrolled courses',
    icon: 'lucide:book-open',
    gradient: 'from-orange-500 to-orange-600',
    bgColor: 'bg-gradient-to-br from-orange-50 to-orange-100',
    hoverColor: 'hover:from-orange-100 hover:to-orange-200',
    textColor: 'text-orange-700',
    route: '/subjects',
    priority: 'high'
  },
  {
    id: 'attendance',
    title: 'Attendance',
    description: 'Track your class attendance',
    icon: 'lucide:user-check',
    gradient: 'from-purple-500 to-purple-600',
    bgColor: 'bg-gradient-to-br from-purple-50 to-purple-100',
    hoverColor: 'hover:from-purple-100 hover:to-purple-200',
    textColor: 'text-purple-700',
    route: '/attendance',
    priority: 'medium'
  },
  {
    id: 'tuition',
    title: 'Tuition',
    description: 'View payment details',
    icon: 'lucide:credit-card',
    gradient: 'from-rose-500 to-rose-600',
    bgColor: 'bg-gradient-to-br from-rose-50 to-rose-100',
    hoverColor: 'hover:from-rose-100 hover:to-rose-200',
    textColor: 'text-rose-700',
    route: '/tuition',
    priority: 'medium'
  },
  {
    id: 'profile',
    title: 'Profile',
    description: 'Update personal information',
    icon: 'lucide:user-circle',
    gradient: 'from-slate-500 to-slate-600',
    bgColor: 'bg-gradient-to-br from-slate-50 to-slate-100',
    hoverColor: 'hover:from-slate-100 hover:to-slate-200',
    textColor: 'text-slate-700',
    route: '/profile',
    priority: 'low'
  }
];

// Responsive layout state
const isExpanded = ref(false);

// Computed properties for responsive design
const primaryActions = computed(() =>
  quickActions.filter(action => action.priority === 'high')
);

const secondaryActions = computed(() =>
  quickActions.filter(action => action.priority !== 'high')
);

const visibleActions = computed(() => {
  if (isExpanded.value) {
    return quickActions;
  }
  return primaryActions.value;
});

// Navigate to action with loading state
const navigatingTo = ref(null);
const navigateToAction = async (action) => {
  navigatingTo.value = action.id;
  try {
    router.visit(action.route);
  } finally {
    // Reset after a short delay to show loading state
    setTimeout(() => {
      navigatingTo.value = null;
    }, 500);
  }
};

// Toggle expanded view
const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value;
};
</script>

<template>
  <Card class="border-0 shadow-sm bg-gradient-to-br from-white to-gray-50/50 dark:from-gray-900 dark:to-gray-800/50">
    <CardHeader class="pb-3">
      <div class="flex items-center justify-between">
        <CardTitle class="text-lg font-semibold text-gray-900 dark:text-gray-100">
          Quick Actions
        </CardTitle>
        <Badge
          v-if="secondaryActions.length > 0"
          variant="secondary"
          class="text-xs"
        >
          {{ visibleActions.length }}/{{ quickActions.length }}
        </Badge>
      </div>
    </CardHeader>
    <CardContent class="p-4 pt-0">
      <!-- Primary Actions Grid - Always Visible -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
        <button
          v-for="action in primaryActions"
          :key="action.id"
          @click="navigateToAction(action)"
          :disabled="navigatingTo === action.id"
          :class="[
            'group relative overflow-hidden rounded-xl border border-gray-200/60 dark:border-gray-700/60',
            'transition-all duration-300 ease-out transform',
            'hover:scale-[1.02] hover:shadow-lg hover:border-gray-300/80 dark:hover:border-gray-600/80',
            'active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-blue-500/50',
            action.bgColor, action.hoverColor,
            navigatingTo === action.id ? 'opacity-75 cursor-wait' : 'cursor-pointer'
          ]"
        >
          <!-- Background Gradient Overlay -->
          <div :class="[
            'absolute inset-0 opacity-0 group-hover:opacity-10 transition-opacity duration-300',
            `bg-gradient-to-br ${action.gradient}`
          ]"></div>

          <!-- Content -->
          <div class="relative p-4 flex flex-col items-center text-center space-y-3">
            <!-- Icon Container -->
            <div :class="[
              'relative p-3 rounded-xl transition-all duration-300',
              'group-hover:scale-110 group-hover:rotate-3',
              `bg-gradient-to-br ${action.gradient}`,
              navigatingTo === action.id ? 'animate-pulse' : ''
            ]">
              <Icon
                :icon="action.icon"
                class="w-6 h-6 text-white drop-shadow-sm"
              />

              <!-- Loading Spinner Overlay -->
              <div
                v-if="navigatingTo === action.id"
                class="absolute inset-0 flex items-center justify-center bg-black/20 rounded-xl"
              >
                <Icon
                  icon="lucide:loader-2"
                  class="w-4 h-4 text-white animate-spin"
                />
              </div>
            </div>

            <!-- Text Content -->
            <div class="space-y-1">
              <h3 :class="[
                'font-semibold text-sm transition-colors duration-300',
                action.textColor,
                'group-hover:text-gray-900 dark:group-hover:text-gray-100'
              ]">
                {{ action.title }}
              </h3>
              <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed hidden sm:block">
                {{ action.description }}
              </p>
            </div>
          </div>

          <!-- Hover Effect Border -->
          <div class="absolute inset-0 rounded-xl border-2 border-transparent group-hover:border-white/20 transition-colors duration-300"></div>
        </button>
      </div>

      <!-- Secondary Actions - Expandable -->
      <div v-if="secondaryActions.length > 0" class="space-y-3">
        <!-- Expand/Collapse Button -->
        <Button
          @click="toggleExpanded"
          variant="ghost"
          size="sm"
          class="w-full text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100"
        >
          <Icon
            :icon="isExpanded ? 'lucide:chevron-up' : 'lucide:chevron-down'"
            class="w-4 h-4 mr-2 transition-transform duration-300"
            :class="{ 'rotate-180': isExpanded }"
          />
          {{ isExpanded ? 'Show Less' : `Show ${secondaryActions.length} More` }}
        </Button>

        <!-- Secondary Actions Grid -->
        <div
          v-if="isExpanded"
          class="grid grid-cols-2 sm:grid-cols-3 gap-2 animate-in slide-in-from-top-2 duration-300"
        >
          <button
            v-for="action in secondaryActions"
            :key="action.id"
            @click="navigateToAction(action)"
            :disabled="navigatingTo === action.id"
            :class="[
              'group relative overflow-hidden rounded-lg border border-gray-200/60 dark:border-gray-700/60',
              'transition-all duration-200 ease-out',
              'hover:scale-[1.02] hover:shadow-md hover:border-gray-300/80 dark:hover:border-gray-600/80',
              'active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-blue-500/50',
              action.bgColor, action.hoverColor,
              navigatingTo === action.id ? 'opacity-75 cursor-wait' : 'cursor-pointer'
            ]"
          >
            <div class="relative p-3 flex flex-col items-center text-center space-y-2">
              <!-- Icon -->
              <div :class="[
                'p-2 rounded-lg transition-all duration-200',
                'group-hover:scale-105',
                `bg-gradient-to-br ${action.gradient}`,
                navigatingTo === action.id ? 'animate-pulse' : ''
              ]">
                <Icon
                  :icon="navigatingTo === action.id ? 'lucide:loader-2' : action.icon"
                  :class="[
                    'w-4 h-4 text-white',
                    navigatingTo === action.id ? 'animate-spin' : ''
                  ]"
                />
              </div>

              <!-- Title -->
              <span :class="[
                'text-xs font-medium transition-colors duration-200',
                action.textColor,
                'group-hover:text-gray-900 dark:group-hover:text-gray-100'
              ]">
                {{ action.title }}
              </span>
            </div>
          </button>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<style scoped>
/* Custom animations for enhanced UX */
@keyframes slide-in-from-top {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-in {
  animation-fill-mode: both;
}

.slide-in-from-top-2 {
  animation: slide-in-from-top 0.3s ease-out;
}

/* Enhanced hover effects */
.group:hover .group-hover\:scale-110 {
  transform: scale(1.1);
}

.group:hover .group-hover\:rotate-3 {
  transform: rotate(3deg);
}

/* Ensure proper touch targets on mobile */
@media (max-width: 640px) {
  button {
    min-height: 48px;
  }
}

/* Dark mode enhancements */
@media (prefers-color-scheme: dark) {
  .bg-gradient-to-br {
    background-image: linear-gradient(to bottom right, var(--tw-gradient-stops));
  }
}

/* Focus states for accessibility */
button:focus-visible {
  outline: 2px solid rgb(59 130 246 / 0.5);
  outline-offset: 2px;
}

/* Loading state animations */
@keyframes gentle-pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

.animate-pulse {
  animation: gentle-pulse 1.5s ease-in-out infinite;
}

/* Smooth transitions for all interactive elements */
button, .transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Enhanced shadow effects */
.hover\:shadow-lg:hover {
  box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -2px rgb(0 0 0 / 0.05);
}

.hover\:shadow-md:hover {
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -1px rgb(0 0 0 / 0.06);
}
</style>
