<script setup>
import {
  Card,
  CardContent,
  CardDescription,
} from '@/Components/shadcn/ui/card';
import { Progress } from '@/Components/shadcn/ui/progress';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/shadcn/ui/tooltip';
import { Icon } from '@iconify/vue';
import { computed } from 'vue';

const props = defineProps({
  stats: {
    type: Array,
    default: () => [],
  },
  courseInfo: {
    type: Object,
    default: null
  },
  semester: {
    type: Number,
    default: null
  },
  schoolYear: {
    type: String,
    default: null
  }
});

// Define icons and colors for each stat type
const getStatConfig = (label) => {
  const lowerLabel = label.toLowerCase();

  if (lowerLabel.includes('gpa')) {
    return {
      icon: 'lucide:award',
      color: 'text-blue-500',
      bgColor: 'bg-blue-100 dark:bg-blue-950/50',
      progressColor: 'bg-blue-500'
    };
  } else if (lowerLabel.includes('attendance')) {
    return {
      icon: 'lucide:calendar-check',
      color: 'text-green-500',
      bgColor: 'bg-green-100 dark:bg-green-950/50',
      progressColor: 'bg-green-500'
    };
  } else if (lowerLabel.includes('unit')) {
    return {
      icon: 'lucide:book-open',
      color: 'text-amber-500',
      bgColor: 'bg-amber-100 dark:bg-amber-950/50',
      progressColor: 'bg-amber-500'
    };
  } else if (lowerLabel.includes('class')) {
    return {
      icon: 'lucide:layout-grid',
      color: 'text-purple-500',
      bgColor: 'bg-purple-100 dark:bg-purple-950/50',
      progressColor: 'bg-purple-500'
    };
  }

  // Default
  return {
    icon: 'lucide:bar-chart',
    color: 'text-gray-500',
    bgColor: 'bg-gray-100 dark:bg-gray-800',
    progressColor: 'bg-gray-500'
  };
};

// Calculate progress value for each stat
const getProgressValue = (stat) => {
  const lowerLabel = stat.label.toLowerCase();

  if (lowerLabel.includes('gpa')) {
    // Assuming GPA is on a 4.0 scale
    const value = parseFloat(stat.value);
    if (isNaN(value)) return null;
    const percentage = (value / 4.0) * 100;
    return Math.min(Math.max(percentage, 0), 100); // Clamp between 0-100
  } else if (lowerLabel.includes('attendance')) {
    // Handle attendance - could be percentage or raw number
    const value = parseFloat(stat.value);
    if (isNaN(value)) return null;

    // If value is greater than 100, it's likely a raw number, not percentage
    if (value > 100) {
      // Assume it's out of some total - for now, don't show progress
      return null;
    }

    return Math.min(Math.max(value, 0), 100); // Clamp between 0-100
  } else {
    // For other stats, no progress bar
    return null;
  }
};

// Format semester name
const semesterName = computed(() => {
  if (props.semester === 1) return '1st Semester';
  if (props.semester === 2) return '2nd Semester';
  if (props.semester === 3) return 'Summer';
  return '';
});

// Format academic info
const academicInfo = computed(() => {
  if (!props.semester || !props.schoolYear) return '';
  return `${semesterName.value}, ${props.schoolYear}`;
});
</script>

<template>
  <div class="space-y-4">
    <!-- Course and semester info -->
    <div v-if="courseInfo" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-1">
      <div>
        <h3 class="text-sm font-medium flex items-center">
          <Icon icon="lucide:graduation-cap" class="h-4 w-4 mr-1.5 text-primary" />
          {{ courseInfo.title }}
          <span class="ml-2 text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-full">
            {{ courseInfo.code }}
          </span>
        </h3>
        <p v-if="academicInfo" class="text-xs text-muted-foreground mt-0.5">
          {{ academicInfo }}
        </p>
      </div>
    </div>

    <!-- Simplified Stats cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 md:gap-3">
      <div v-for="(stat, index) in (Array.isArray(props.stats) ? props.stats.slice(0, 4) : [])" :key="index">
        <Card class="border-0 shadow-sm hover:shadow-md transition-shadow duration-200">
          <CardContent class="p-3">
            <div class="flex items-center justify-between">
              <div class="min-w-0 flex-1">
                <p class="text-xs text-muted-foreground mb-1 truncate">{{ stat.label }}</p>
                <div class="text-lg md:text-xl font-bold" :class="getStatConfig(stat.label).color">
                  {{ stat.value }}
                </div>
              </div>
              <div
                class="p-2 rounded-lg flex-shrink-0"
                :class="getStatConfig(stat.label).bgColor"
              >
                <Icon
                  :icon="getStatConfig(stat.label).icon"
                  class="w-4 h-4"
                  :class="getStatConfig(stat.label).color"
                />
              </div>
            </div>

            <!-- Simplified progress bar for applicable stats -->
            <div v-if="getProgressValue(stat) !== null" class="mt-2">
              <Progress
                :model-value="getProgressValue(stat)"
                class="h-1.5"
              />
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </div>
</template>
