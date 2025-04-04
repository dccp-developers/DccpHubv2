<script setup>
import { Badge } from '@/Components/shadcn/ui/badge';
import { Button } from '@/Components/shadcn/ui/button';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription,
} from '@/Components/shadcn/ui/card';
import { Progress } from '@/Components/shadcn/ui/progress';
import { Tabs, TabsList, TabsTrigger } from '@/Components/shadcn/ui/tabs';
import { Icon } from '@iconify/vue';
import { Link } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';

const props = defineProps({
  todaysClasses: {
    type: Array,
    required: true,
  }
});

// Current time for highlighting current class
const currentTime = ref(new Date());
const scrollContainer = ref(null);
const viewMode = ref('list'); // 'list' or 'timeline'

const showDetails = ref(null); // ID of class to show details for

// Update current time every minute
setInterval(() => {
  currentTime.value = new Date();
}, 60000);

onMounted(() => {
  // Set default view based on screen size
  if (window.innerWidth >= 768) {
    viewMode.value = 'timeline';
  }

  // Scroll to current time on initial load
  setTimeout(scrollToCurrentTime, 500);
});

// Function to scroll to current time in timeline view
function scrollToCurrentTime() {
  if (!scrollContainer.value) return;

  const now = new Date();
  const hours = now.getHours();
  const minutes = now.getMinutes();

  // Calculate position (6AM is our starting point)
  const hoursSince6Am = Math.max(0, hours - 6 + minutes / 60);
  const scrollPosition = hoursSince6Am * 60; // 60px per hour

  scrollContainer.value.scrollTop = scrollPosition - 100; // Offset to show a bit above current time
}

// Get current day name
const currentDayName = computed(() => {
  const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  return days[currentTime.value.getDay()];
});

// Format date for display
const formattedDate = computed(() => {
  return currentTime.value.toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric'
  });
});

// Calculate position for schedule blocks in timeline view
function getSchedulePosition(classItem) {
  if (!classItem.start_time) return { top: 0, height: 60 };

  // Parse time (e.g., "8:00 AM" to hours and minutes)
  const startParts = classItem.start_time.split(' ');
  const startTime = startParts[0].split(':');
  const startHour = parseInt(startTime[0]);
  const startMin = parseInt(startTime[1]);
  const startAmPm = startParts[1];

  const endParts = classItem.end_time.split(' ');
  const endTime = endParts[0].split(':');
  const endHour = parseInt(endTime[0]);
  const endMin = parseInt(endTime[1]);
  const endAmPm = endParts[1];

  // Convert to 24-hour format
  let adjustedStartHour = startHour;
  if (startAmPm === 'PM' && startHour !== 12) adjustedStartHour += 12;
  if (startAmPm === 'AM' && startHour === 12) adjustedStartHour = 0;

  let adjustedEndHour = endHour;
  if (endAmPm === 'PM' && endHour !== 12) adjustedEndHour += 12;
  if (endAmPm === 'AM' && endHour === 12) adjustedEndHour = 0;

  // Calculate position and height
  const hoursSince6Am = Math.max(0, adjustedStartHour - 6 + startMin / 60);
  const durationHours = (adjustedEndHour + endMin / 60) - (adjustedStartHour + startMin / 60);

  const topPosition = hoursSince6Am * 60; // 60px per hour
  const height = Math.max(30, durationHours * 60); // Minimum height of 30px

  return {
    top: topPosition,
    height: height
  };
}

// Toggle class details
function toggleDetails(classId) {
  if (showDetails.value === classId) {
    showDetails.value = null;
  } else {
    showDetails.value = classId;
  }
}

// Determine if a class is current, past, or upcoming
const getClassStatus = (classItem) => {
  if (!classItem.start_time || !classItem.end_time) return 'upcoming';

  const now = currentTime.value;
  const currentHour = now.getHours();
  const currentMinute = now.getMinutes();

  // Parse start and end times
  const startTimeParts = classItem.start_time.match(/^(\d+)(?::(\d+))\s*([ap]m)$/i);
  const endTimeParts = classItem.end_time.match(/^(\d+)(?::(\d+))\s*([ap]m)$/i);

  if (!startTimeParts || !endTimeParts) return 'upcoming';

  let startHour = parseInt(startTimeParts[1]);
  const startMinute = parseInt(startTimeParts[2] || 0);
  const startPeriod = startTimeParts[3].toLowerCase();

  let endHour = parseInt(endTimeParts[1]);
  const endMinute = parseInt(endTimeParts[2] || 0);
  const endPeriod = endTimeParts[3].toLowerCase();

  // Convert to 24-hour format
  if (startPeriod === 'pm' && startHour < 12) startHour += 12;
  if (startPeriod === 'am' && startHour === 12) startHour = 0;
  if (endPeriod === 'pm' && endHour < 12) endHour += 12;
  if (endPeriod === 'am' && endHour === 12) endHour = 0;

  // Convert current time, start time, and end time to minutes for easier comparison
  const currentTimeInMinutes = currentHour * 60 + currentMinute;
  const startTimeInMinutes = startHour * 60 + startMinute;
  const endTimeInMinutes = endHour * 60 + endMinute;

  if (currentTimeInMinutes < startTimeInMinutes) {
    return 'upcoming';
  } else if (currentTimeInMinutes >= startTimeInMinutes && currentTimeInMinutes < endTimeInMinutes) {
    return 'current';
  } else {
    return 'past';
  }
};

// Debug function to log class data
const logClassData = (classItem) => {
  console.log('Class data:', classItem);
  return true;
};

// Get appropriate color for class status
const getClassColor = (status) => {
  switch (status) {
    case 'current':
      return 'border-primary bg-primary/5';
    case 'past':
      return 'border-muted-foreground/30 bg-muted/30';
    case 'upcoming':
      return 'border-muted-foreground/20';
    default:
      return 'border-muted-foreground/20';
  }
};

// Get appropriate icon for class status
const getStatusIcon = (status) => {
  switch (status) {
    case 'current':
      return 'lucide:play';
    case 'past':
      return 'lucide:check';
    case 'upcoming':
      return 'lucide:clock';
    default:
      return 'lucide:clock';
  }
};

// Get appropriate text color for class status
const getTextColor = (status) => {
  switch (status) {
    case 'current':
      return 'text-primary';
    case 'past':
      return 'text-muted-foreground/70';
    case 'upcoming':
      return '';
    default:
      return '';
  }
};

// Get badge color for grade status
const getGradeStatusColor = (status) => {
  if (!status) return '';

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

// Sort classes by start time
const sortedClasses = computed(() => {
  if (!props.todaysClasses || !props.todaysClasses.length) return [];

  return [...props.todaysClasses].sort((a, b) => {
    // Convert times to comparable format
    const timeA = getTimeInMinutes(a.start_time);
    const timeB = getTimeInMinutes(b.start_time);
    return timeA - timeB;
  });
});

// Helper function to convert time string to minutes since midnight
function getTimeInMinutes(timeStr) {
  if (!timeStr) return 0;

  const startTimeParts = timeStr.match(/^(\d+)(?::(\d+))\s*([ap]m)$/i);
  if (!startTimeParts) return 0;

  let hours = parseInt(startTimeParts[1]);
  const minutes = parseInt(startTimeParts[2] || 0);
  const period = startTimeParts[3].toLowerCase();

  // Convert to 24-hour format
  if (period === 'pm' && hours < 12) hours += 12;
  if (period === 'am' && hours === 12) hours = 0;

  return hours * 60 + minutes;
}
</script>

<template>
  <Card class="overflow-hidden">
    <CardHeader class="pb-0">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
          <CardTitle class="flex items-center">
            <Icon icon="lucide:calendar" class="h-5 w-5 mr-2 text-primary" />
            Today's Schedule
          </CardTitle>
          <CardDescription class="mt-1">
            {{ currentDayName }}, {{ formattedDate }}
          </CardDescription>
        </div>
        <div class="flex items-center gap-2">
          <Tabs v-model="viewMode" class="hidden md:block">
            <TabsList class="h-8">
              <TabsTrigger value="list" class="px-3 h-7">
                <Icon icon="lucide:list" class="h-4 w-4 mr-1" />
                List
              </TabsTrigger>
              <TabsTrigger value="timeline" class="px-3 h-7">
                <Icon icon="lucide:clock" class="h-4 w-4 mr-1" />
                Timeline
              </TabsTrigger>
            </TabsList>
          </Tabs>
          <Link :href="route('schedule.index')">
            <Button variant="outline" size="sm" class="flex items-center">
              <Icon icon="lucide:calendar-range" class="h-4 w-4 mr-2" />
              Full Schedule
            </Button>
          </Link>
        </div>
      </div>
    </CardHeader>

    <CardContent class="pt-4">

      <!-- Empty state -->
      <div v-if="todaysClasses.length === 0" class="flex flex-col items-center justify-center py-8 text-center">
        <div class="bg-muted rounded-full p-4 mb-4">
          <Icon icon="lucide:calendar-off" class="h-8 w-8 text-muted-foreground" />
        </div>
        <h3 class="font-medium mb-1">No Classes Today</h3>
        <p class="text-sm text-muted-foreground max-w-xs">
          You don't have any classes scheduled for today. Enjoy your free time!
        </p>
      </div>

      <!-- List view -->
      <div v-else-if="viewMode === 'list'" class="space-y-3 max-h-[350px] overflow-y-auto pr-1 pt-1">
        <div
          v-for="class_item in sortedClasses"
          :key="class_item.id"
          class="flex items-start p-3 border rounded-lg transition-all duration-200 hover:shadow-sm cursor-pointer"
          :class="[
            getClassStatus(class_item) === 'current' ? 'border-primary bg-primary/5' : 'border-muted',
            getClassStatus(class_item) === 'past' ? 'opacity-60' : 'opacity-100'
          ]"
          @click="toggleDetails(class_item.id)"
        >
          <!-- Time column -->
          <div class="w-20 flex-shrink-0 flex flex-col items-center justify-center mr-3">
            <div
              class="flex items-center justify-center rounded-full w-10 h-10 mb-1"
              :class="getClassStatus(class_item) === 'current' ? 'bg-primary/20' : 'bg-muted'"
            >
              <Icon
                :icon="getStatusIcon(getClassStatus(class_item))"
                class="h-4 w-4"
                :class="getClassStatus(class_item) === 'current' ? 'text-primary' : 'text-muted-foreground'"
              />
            </div>
            <span
              class="text-sm font-medium text-center"
              :class="getClassStatus(class_item) === 'current' ? 'text-primary' : ''"
            >
              {{ class_item.start_time }}
            </span>
            <span class="text-xs text-muted-foreground">
              {{ class_item.end_time }}
            </span>
          </div>

          <!-- Class details -->
          <div class="flex-grow">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2">
              <div>
                <div class="flex items-center gap-1.5">
                  <h4
                    class="font-bold"
                    :class="getClassStatus(class_item) === 'current' ? 'text-primary' : ''"
                  >
                    {{ class_item.subject }}
                  </h4>
                  <Badge
                    v-if="class_item.subject_code"
                    class="text-xs"
                    :class="getClassStatus(class_item) === 'current' ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'"
                  >
                    {{ class_item.subject_code }}
                  </Badge>
                </div>
                <p
                  v-if="class_item.teacher"
                  class="text-sm flex items-center mt-1 text-muted-foreground"
                >
                  <Icon icon="lucide:user" class="h-3.5 w-3.5 mr-1" />
                  {{ class_item.teacher }}
                </p>
                <div v-if="class_item.section" class="text-xs text-muted-foreground mt-1">
                  <span class="inline-flex items-center">
                    <Icon icon="lucide:users" class="h-3 w-3 mr-1" />
                    Section {{ class_item.section }}
                  </span>
                </div>
              </div>

              <div class="flex flex-col items-end gap-2">
                <Badge
                  variant="outline"
                  class="text-xs"
                  :class="getClassStatus(class_item) === 'current' ? 'border-primary/50 bg-primary/5' : ''"
                >
                  <Icon icon="lucide:map-pin" class="h-3 w-3 mr-1" />
                  Room {{ class_item.room }}
                </Badge>

                <Badge
                  v-if="class_item.grade_status"
                  variant="secondary"
                  class="text-xs"
                  :class="getGradeStatusColor(class_item.grade_status)"
                >
                  {{ class_item.grade_status }}
                </Badge>
              </div>
            </div>

            <!-- Current class indicator -->
            <div v-if="getClassStatus(class_item) === 'current'" class="mt-3 flex items-center justify-between">
              <div class="flex items-center">
                <div class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse mr-1.5"></div>
                <span class="text-xs font-medium text-primary">In Progress</span>
              </div>

              <!-- Progress bar for current class -->
              <div class="flex items-center gap-2 w-1/2">
                <Progress :model-value="class_item.progress || 0" class="h-1.5 flex-grow" />
                <span class="text-xs text-muted-foreground">{{ class_item.progress || 0 }}%</span>
              </div>
            </div>

            <!-- Expanded details -->
            <div v-if="showDetails === class_item.id" class="mt-3 pt-3 border-t border-dashed border-muted-foreground/20">
              <div class="grid grid-cols-2 gap-2 text-xs">
                <div>
                  <p class="text-muted-foreground mb-1">Duration</p>
                  <p>{{ class_item.duration || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-muted-foreground mb-1">Room</p>
                  <p>{{ class_item.room }}</p>
                </div>
                <div class="col-span-2 mt-2">
                  <Link
                    :href="route('schedule.index', { class: class_item.id })"
                    class="w-full"
                  >
                    <Button
                      variant="outline"
                      size="sm"
                      class="w-full"
                    >
                      <Icon icon="lucide:external-link" class="h-3.5 w-3.5 mr-1.5" />
                      View Class Details
                    </Button>
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Timeline view -->
      <div v-else-if="viewMode === 'timeline'" class="relative">
        <div ref="scrollContainer" class="max-h-[350px] overflow-y-auto pr-1">
          <!-- Time markers -->
          <div class="relative border-l border-muted-foreground/20 ml-4 pl-4">
            <!-- Current time indicator -->
            <div
              class="absolute left-0 flex items-center z-10 transform -translate-y-1/2"
              :style="{
                top: ((currentTime.value.getHours() - 6) * 60 + currentTime.value.getMinutes()) + 'px'
              }"
            >
              <div class="h-2 w-2 rounded-full bg-red-500 animate-pulse mr-1"></div>
              <div class="text-xs font-medium text-red-500 bg-background px-1">
                {{ currentTime.value.getHours() }}:{{ currentTime.value.getMinutes().toString().padStart(2, '0') }}
              </div>
              <div class="h-px w-full bg-red-500/50"></div>
            </div>

            <!-- Hour markers -->
            <div v-for="hour in 14" :key="hour" class="relative" :style="{ height: '60px' }">
              <div class="absolute left-0 transform -translate-x-4 -translate-y-1/2 flex items-center">
                <div class="h-1.5 w-1.5 rounded-full bg-muted-foreground/40"></div>
                <div class="text-xs text-muted-foreground ml-1.5">{{ (hour + 6) % 12 || 12 }}{{ (hour + 6) >= 12 ? 'pm' : 'am' }}</div>
              </div>
            </div>

            <!-- Class blocks -->
            <div
              v-for="class_item in sortedClasses"
              :key="class_item.id"
              class="absolute left-8 right-2 rounded-md border p-2 transition-all duration-200 hover:shadow-md cursor-pointer"
              :class="[
                getClassStatus(class_item) === 'current' ? 'border-primary bg-primary/5' : 'border-muted',
                getClassStatus(class_item) === 'past' ? 'opacity-60' : 'opacity-100'
              ]"
              :style="{
                top: getSchedulePosition(class_item).top + 'px',
                height: getSchedulePosition(class_item).height + 'px',
                minHeight: '30px'
              }"
              @click="toggleDetails(class_item.id)"
            >
              <div class="flex flex-col h-full overflow-hidden">
                <div class="flex justify-between items-start">
                  <div class="flex-1 min-w-0">
                    <h4 class="font-medium text-sm truncate" :class="getClassStatus(class_item) === 'current' ? 'text-primary' : ''">
                      {{ class_item.subject }}
                    </h4>
                    <p class="text-xs truncate text-muted-foreground">
                      {{ class_item.start_time }} - {{ class_item.end_time }}
                    </p>
                  </div>
                  <Badge
                    variant="outline"
                    class="text-xs shrink-0 ml-1"
                    :class="getClassStatus(class_item) === 'current' ? 'border-primary/50 bg-primary/5' : ''"
                  >
                    {{ class_item.room }}
                  </Badge>
                </div>

                <!-- Only show if there's enough height -->
                <div v-if="getSchedulePosition(class_item).height > 50" class="mt-1 text-xs text-muted-foreground truncate">
                  {{ class_item.teacher }}
                </div>

                <!-- Current class indicator -->
                <div v-if="getClassStatus(class_item) === 'current'" class="mt-auto pt-1">
                  <Progress :model-value="class_item.progress || 0" class="h-1" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </CardContent>
  </Card>
</template>
