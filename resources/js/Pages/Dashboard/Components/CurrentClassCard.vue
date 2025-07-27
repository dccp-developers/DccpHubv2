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
  const progress = parseFloat(props.currentClass.progress);
  if (isNaN(progress)) return 0;
  // Ensure progress is between 0 and 100
  return Math.min(Math.max(progress, 0), 100);
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
  <Card v-if="currentClass" class="border-0 shadow-sm">
    <!-- Simplified class in progress indicator -->
    <div class="bg-green-50 dark:bg-green-900/20 px-3 py-2 flex items-center justify-between border-b">
      <div class="flex items-center">
        <div class="h-2 w-2 rounded-full bg-green-500 animate-pulse mr-2"></div>
        <span class="text-xs font-medium text-green-700 dark:text-green-400">In Progress</span>
      </div>
      <span class="text-xs text-muted-foreground">{{ formattedTimeRemaining }}</span>
    </div>

    <CardContent class="p-3">
      <div class="space-y-3">
        <!-- Class info -->
        <div class="flex items-start justify-between">
          <div class="min-w-0 flex-1">
            <h3 class="text-sm font-semibold truncate">{{ currentClass.subject }}</h3>
            <p class="text-xs text-muted-foreground mt-1">{{ currentClass.teacher }}</p>
            <div class="flex items-center gap-2 mt-1">
              <Badge variant="secondary" class="text-xs px-2 py-0.5">
                Room {{ currentClass.room }}
              </Badge>
              <span class="text-xs text-muted-foreground">{{ currentClass.time }}</span>
            </div>
          </div>
        </div>

        <!-- Progress -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs text-muted-foreground">Progress</span>
            <span class="text-xs font-medium">{{ classProgress }}%</span>
          </div>
          <Progress :model-value="classProgress" class="h-1.5" />
        </div>

        <!-- Quick actions -->
        <div class="flex gap-2">
          <Link :href="route('schedule.index')" class="flex-1">
            <Button size="sm" class="w-full text-xs">
              <Icon icon="lucide:book-open" class="h-3 w-3 mr-1" />
              Materials
            </Button>
          </Link>
          <Button variant="outline" size="sm" class="flex-1 text-xs">
            <Icon icon="lucide:message-square" class="h-3 w-3 mr-1" />
            Chat
          </Button>
        </div>
      </div>
    </CardContent>

    <!-- Next class info -->
    <div v-if="nextClass" class="border-t bg-muted/30 px-3 py-2">
      <div class="flex items-center justify-between">
        <span class="text-xs text-muted-foreground">Next: {{ nextClass.subject }}</span>
        <span class="text-xs text-muted-foreground">{{ nextClass.time_until }}</span>
      </div>
    </div>
  </Card>

  <!-- No current class state -->
  <Card v-else class="border-dashed border-muted-foreground/20">
    <CardContent class="p-3">
      <div class="text-center py-4">
        <div class="bg-amber-100 dark:bg-amber-900/20 rounded-full p-3 w-fit mx-auto mb-3">
          <Icon icon="lucide:coffee" class="h-6 w-6 text-amber-600 dark:text-amber-400" />
        </div>
        <h3 class="text-sm font-medium mb-1">Break Time</h3>
        <p class="text-xs text-muted-foreground mb-3">
          No classes right now. Perfect time to study!
        </p>
        <Link :href="route('schedule.index')">
          <Button variant="outline" size="sm" class="text-xs">
            <Icon icon="lucide:calendar" class="h-3 w-3 mr-1" />
            View Schedule
          </Button>
        </Link>
      </div>
    </CardContent>
  </Card>
</template>
