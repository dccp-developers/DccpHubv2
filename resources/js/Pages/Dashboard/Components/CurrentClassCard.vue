<script setup>
import { Badge } from '@/Components/shadcn/ui/badge';
import { Button } from '@/Components/shadcn/ui/button';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription,
  CardFooter,
} from '@/Components/shadcn/ui/card';
import { Progress } from '@/Components/shadcn/ui/progress';
import { Icon } from '@iconify/vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  currentClass: {
    type: Object,
    default: null,
  }
});

// Calculate time remaining in class
const timeRemaining = computed(() => {
  if (!props.currentClass || !props.currentClass.time_remaining) return null;
  return props.currentClass.time_remaining;
});

// Format time remaining in a readable format
const formattedTimeRemaining = computed(() => {
  if (!timeRemaining.value) return '';

  const hours = Math.floor(timeRemaining.value / 60);
  const minutes = timeRemaining.value % 60;

  if (hours > 0) {
    return `${hours}h ${minutes}m remaining`;
  }
  return `${minutes} min remaining`;
});

// Calculate progress percentage of class completion
const classProgress = computed(() => {
  if (!props.currentClass || !props.currentClass.progress) return 0;
  return props.currentClass.progress;
});

// Get the next class
const nextClass = computed(() => {
  if (!props.currentClass || !props.currentClass.next_class) return null;
  return props.currentClass.next_class;
});

// Get grade status badge color
const getGradeStatusColor = (status) => {
  if (!status) return 'bg-gray-100 text-gray-500';

  switch (status.toLowerCase()) {
    case 'passing':
      return 'bg-green-100 text-green-600';
    case 'failing':
      return 'bg-red-100 text-red-600';
    case 'pending':
    default:
      return 'bg-yellow-100 text-yellow-600';
  }
};
</script>

<template>
  <Card v-if="currentClass" class="overflow-hidden border-primary/20 hover:border-primary/50 transition-all duration-300">
    <!-- Class in progress indicator -->
    <div class="bg-primary/10 px-4 py-1.5 flex items-center justify-between">
      <div class="flex items-center">
        <div class="h-2 w-2 rounded-full bg-green-500 animate-pulse mr-2"></div>
        <span class="text-xs font-medium text-primary">In Progress</span>
      </div>
      <span class="text-xs text-muted-foreground">{{ formattedTimeRemaining }}</span>
    </div>

    <CardHeader class="pb-2">
      <div class="flex justify-between items-start">
        <div>
          <div class="flex items-center">
            <CardTitle class="text-xl font-bold">{{ currentClass.subject }}</CardTitle>
            <Badge
              v-if="currentClass.subject_code"
              class="ml-2 text-xs bg-primary/5 text-primary"
            >
              {{ currentClass.subject_code }}
            </Badge>
          </div>
          <CardDescription class="flex items-center mt-1">
            <Icon icon="lucide:user" class="h-3.5 w-3.5 mr-1 text-muted-foreground" />
            {{ currentClass.teacher }}
          </CardDescription>
        </div>
        <div class="flex flex-col items-end gap-1">
          <Badge class="bg-primary/10 text-primary hover:bg-primary/20 transition-colors">
            <Icon icon="lucide:map-pin" class="h-3.5 w-3.5 mr-1" />
            Room {{ currentClass.room }}
          </Badge>
          <Badge
            v-if="currentClass.section"
            class="text-xs"
            variant="outline"
          >
            Section {{ currentClass.section }}
          </Badge>
        </div>
      </div>
    </CardHeader>

    <CardContent class="pb-3">
      <div class="space-y-4">
        <!-- Time info -->
        <div class="flex items-center justify-between text-sm">
          <div class="flex items-center text-muted-foreground">
            <Icon icon="lucide:clock" class="h-4 w-4 mr-2" />
            {{ currentClass.time }}
            <span v-if="currentClass.duration" class="ml-2 text-xs opacity-70">({{ currentClass.duration }})</span>
          </div>
          <span class="text-xs font-medium bg-primary/5 text-primary px-2 py-1 rounded-full">
            {{ classProgress }}% Complete
          </span>
        </div>

        <!-- Grade status -->
        <div v-if="currentClass.grade_status" class="flex items-center justify-between text-xs">
          <span class="text-muted-foreground">Grade Status:</span>
          <Badge
            :class="getGradeStatusColor(currentClass.grade_status)"
            variant="secondary"
          >
            {{ currentClass.grade_status }}
          </Badge>
        </div>

        <!-- Progress bar -->
        <div>
          <Progress :model-value="classProgress" class="h-1.5" />
        </div>

        <!-- Action buttons -->
        <div class="flex gap-2">
          <Link :href="route('schedule.index')" class="flex-1">
            <Button class="w-full" size="sm">
              <Icon icon="lucide:book-open" class="h-4 w-4 mr-2" />
              Materials
            </Button>
          </Link>
          <Button variant="outline" size="sm" class="flex-1">
            <Icon icon="lucide:message-square" class="h-4 w-4 mr-2" />
            Discussion
          </Button>
        </div>
      </div>
    </CardContent>

    <!-- Next class info -->
    <CardFooter v-if="nextClass" class="border-t bg-muted/50 px-6 py-3">
      <div class="flex items-center justify-between w-full">
        <div class="flex items-center text-sm">
          <Icon icon="lucide:arrow-right" class="h-4 w-4 mr-2 text-muted-foreground" />
          <span class="text-muted-foreground">Next: {{ nextClass.subject }}</span>
        </div>
        <span class="text-xs text-muted-foreground">in {{ nextClass.time_until }}</span>
      </div>
    </CardFooter>
  </Card>

  <!-- No current class state -->
  <Card v-else class="border-dashed border-muted-foreground/20">
    <CardHeader>
      <CardTitle class="flex items-center">
        <Icon icon="lucide:coffee" class="h-5 w-5 mr-2 text-muted-foreground" />
        No Current Class
      </CardTitle>
      <CardDescription>
        You're currently on a break. Your next class will be shown here when it's time.
      </CardDescription>
    </CardHeader>
    <CardContent>
      <div class="flex flex-col items-center justify-center py-6 text-center">
        <div class="bg-muted rounded-full p-4 mb-4">
          <Icon icon="lucide:calendar-clock" class="h-8 w-8 text-muted-foreground" />
        </div>
        <h3 class="font-medium mb-1">Free Time</h3>
        <p class="text-sm text-muted-foreground max-w-xs">
          Use this time to review your notes, complete assignments, or take a well-deserved break.
        </p>
        <Link :href="route('schedule.index')">
          <Button variant="outline" class="mt-4">
            <Icon icon="lucide:calendar" class="h-4 w-4 mr-2" />
            View Full Schedule
          </Button>
        </Link>
      </div>
    </CardContent>
  </Card>
</template>
