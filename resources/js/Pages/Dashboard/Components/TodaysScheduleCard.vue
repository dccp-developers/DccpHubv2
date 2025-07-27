<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card';
import { Button } from '@/Components/shadcn/ui/button';
import { Badge } from '@/Components/shadcn/ui/badge';
import { Icon } from '@iconify/vue';

const props = defineProps({
  todaysClasses: {
    type: Array,
    default: () => [],
  },
});

// Get current time for status calculation
const currentTime = new Date();

// Calculate time status for each class
const getTimeStatus = (classItem) => {
  if (!classItem.start_time || !classItem.end_time) return 'scheduled';
  
  const now = currentTime;
  const startTime = new Date();
  const endTime = new Date();
  
  // Parse time strings (e.g., "8:00 AM")
  const parseTime = (timeStr) => {
    const [time, period] = timeStr.split(' ');
    const [hours, minutes] = time.split(':').map(Number);
    let adjustedHours = hours;
    
    if (period === 'PM' && hours !== 12) adjustedHours += 12;
    if (period === 'AM' && hours === 12) adjustedHours = 0;
    
    const date = new Date();
    date.setHours(adjustedHours, minutes, 0, 0);
    return date;
  };
  
  const start = parseTime(classItem.start_time);
  const end = parseTime(classItem.end_time);
  
  if (now < start) return 'upcoming';
  if (now >= start && now <= end) return 'ongoing';
  return 'completed';
};

// Get status badge variant
const getStatusVariant = (status) => {
  switch (status) {
    case 'upcoming': return 'secondary';
    case 'ongoing': return 'default';
    case 'completed': return 'outline';
    default: return 'secondary';
  }
};

// Get status text
const getStatusText = (status) => {
  switch (status) {
    case 'upcoming': return 'Upcoming';
    case 'ongoing': return 'Ongoing';
    case 'completed': return 'Completed';
    default: return 'Scheduled';
  }
};

// Navigate to full schedule
const viewAllSchedule = () => {
  router.visit('/schedule');
};

// View class details (placeholder)
const viewClassDetails = (classItem) => {
  // TODO: Implement class details view
  console.log('View class details:', classItem);
};

// Get current date short format
const getCurrentDateShort = () => {
  return new Date().toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric'
  });
};
</script>

<template>
  <Card class="border-0 shadow-sm">
    <CardHeader class="pb-2">
      <div class="flex items-center justify-between">
        <div>
          <CardTitle class="text-sm font-semibold">Today's Classes</CardTitle>
          <CardDescription class="text-xs">{{ getCurrentDateShort() }}</CardDescription>
        </div>
        <Button size="sm" variant="ghost" @click="viewAllSchedule" class="text-xs">
          View All
        </Button>
      </div>
    </CardHeader>
    <CardContent class="pt-0 p-3">
      <div v-if="!Array.isArray(props.todaysClasses) || props.todaysClasses.length === 0" class="text-center py-4">
        <Icon icon="lucide:calendar" class="mx-auto h-6 w-6 text-muted-foreground" />
        <h3 class="mt-2 text-xs font-medium">No classes today</h3>
        <p class="mt-1 text-xs text-muted-foreground">Enjoy your free day!</p>
      </div>
      <div v-else class="space-y-2">
        <div
          v-for="classItem in props.todaysClasses"
          :key="classItem.id"
          class="flex items-center p-2 rounded-lg border border-border/50 hover:bg-accent/50 transition-colors cursor-pointer"
          @click="viewClassDetails(classItem)"
        >
          <div class="flex-shrink-0">
            <div :class="`w-2 h-2 rounded-full ${classItem.color || 'bg-blue-500'}`"></div>
          </div>
          <div class="ml-2 flex-1 min-w-0">
            <div class="flex items-center justify-between">
              <p class="text-xs font-medium truncate">
                {{ classItem.subject_code }}
              </p>
              <Badge :variant="getStatusVariant(getTimeStatus(classItem))" class="text-xs px-1.5 py-0.5">
                {{ getStatusText(getTimeStatus(classItem)) }}
              </Badge>
            </div>
            <p class="text-xs text-muted-foreground mt-1">
              {{ classItem.start_time }} - {{ classItem.end_time }}
            </p>
            <p class="text-xs text-muted-foreground">
              Room {{ classItem.room }}
            </p>
          </div>
        </div>
      </div>
      
      <!-- Mobile View All Button -->
      <div class="mt-4 md:hidden">
        <Button size="sm" variant="outline" class="w-full" @click="viewAllSchedule">
          <Icon icon="lucide:calendar" class="w-4 h-4 mr-2" />
          View Full Schedule
        </Button>
      </div>
    </CardContent>
  </Card>
</template>
