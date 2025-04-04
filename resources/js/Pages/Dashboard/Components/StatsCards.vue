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
    required: true,
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
    return (value / 4.0) * 100;
  } else if (lowerLabel.includes('attendance')) {
    // Attendance is already a percentage
    const value = parseFloat(stat.value);
    if (isNaN(value)) return null;
    return value;
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

    <!-- Stats cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <TooltipProvider>
        <Tooltip v-for="(stat, index) in stats" :key="index">
          <TooltipTrigger asChild>
            <Card
              class="overflow-hidden transition-all duration-300 hover:shadow-md hover:border-primary/50 group cursor-pointer"
            >
              <CardContent class="p-6">
                <div class="flex items-start justify-between mb-2">
                  <div>
                    <p class="text-sm font-medium text-muted-foreground mb-1">{{ stat.label }}</p>
                    <div class="text-2xl font-bold" :class="getStatConfig(stat.label).color">{{ stat.value }}</div>
                  </div>
                  <div
                    class="p-2 rounded-full transition-colors duration-300 group-hover:bg-primary/10"
                    :class="getStatConfig(stat.label).bgColor"
                  >
                    <Icon
                      :icon="getStatConfig(stat.label).icon"
                      class="w-5 h-5 transition-transform duration-300 group-hover:scale-110"
                      :class="getStatConfig(stat.label).color"
                    />
                  </div>
                </div>

                <!-- Progress bar for applicable stats -->
                <div v-if="getProgressValue(stat) !== null" class="mt-4">
                  <Progress
                    :model-value="getProgressValue(stat)"
                    class="h-1.5"
                  />
                  <p class="text-xs text-muted-foreground mt-1 text-right">{{ Math.round(getProgressValue(stat)) }}%</p>
                </div>
              </CardContent>
            </Card>
          </TooltipTrigger>
          <TooltipContent v-if="stat.description">
            <p>{{ stat.description }}</p>
          </TooltipContent>
        </Tooltip>
      </TooltipProvider>
    </div>
  </div>
</template>
