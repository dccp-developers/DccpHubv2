<script setup>
import { ref, onMounted, computed, watch } from "vue";
import { Card, CardContent } from "@/Components/shadcn/ui/card";
import { Button } from "@/Components/shadcn/ui/button";
import { Badge } from "@/Components/shadcn/ui/badge";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/Components/shadcn/ui/tooltip";
import { Icon } from "@iconify/vue";

const props = defineProps({
  daysOfWeek: {
    type: Array,
    required: true,
  },
  filteredSchedules: {
    type: Array,
    required: true,
  },
  classCountByDay: {
    type: Object,
    required: true,
  },
  isScheduleNow: {
    type: Function,
    required: true,
  },
  timeSlots: {
    type: Array,
    required: true,
  },
});

const scrollContainer = ref(null);
const currentTimeRef = ref(null);
const currentTime = ref(new Date());
const selectedSchedule = ref(null);
const showTooltip = ref(false);
const tooltipPosition = ref({ x: 0, y: 0 });

// Update current time every minute
onMounted(() => {
  updateCurrentTime();
  setInterval(updateCurrentTime, 60000);

  // Scroll to current time on initial load (with delay to ensure DOM is ready)
  setTimeout(scrollToCurrentTime, 500);
});

function updateCurrentTime() {
  currentTime.value = new Date();
}

function scrollToCurrentTime() {
  if (!scrollContainer.value) return;

  const now = new Date();
  const hour = now.getHours();
  const minute = now.getMinutes();

  // Calculate position based on time (6AM to 9PM)
  const startHour = 6;
  const totalHours = 15;
  const hourOffset = hour - startHour + minute / 60;

  if (hourOffset >= 0 && hourOffset <= totalHours) {
    const position =
      (hourOffset / totalHours) * scrollContainer.value.scrollHeight;
    scrollContainer.value.scrollTop = position - 100;
  }
}

// Get current timeline position
const getCurrentTimePosition = computed(() => {
  const now = new Date();
  const hour = now.getHours();
  const minute = now.getMinutes();

  // If outside of our display hours (6am-9pm), return null
  if (hour < 6 || hour > 21) {
    return null;
  }

  const hoursSince6Am = hour - 6 + minute / 60;
  return hoursSince6Am * 60; // Increased from 50px for better visibility
});

// Calculate position for schedule blocks in timeline view
function getSchedulePosition(schedule) {
  // Parse time (e.g., "8:00 AM" to hours and minutes)
  const startParts = schedule.start_time.split(" ");
  const startTime = startParts[0].split(":");
  const startHour = parseInt(startTime[0]);
  const startMin = parseInt(startTime[1]);
  const startAmPm = startParts[1];

  // Calculate hours from 6 AM (our start time)
  let adjustedStartHour = startHour;
  if (startAmPm === "PM" && startHour !== 12) adjustedStartHour += 12;
  if (startAmPm === "AM" && startHour === 12) adjustedStartHour = 0;

  // Calculate position and height
  const hoursSince6Am = adjustedStartHour - 6 + startMin / 60;
  const topPosition = hoursSince6Am * 60; // Increased from 50px for better visibility

  // Calculate duration
  const endParts = schedule.end_time.split(" ");
  const endTime = endParts[0].split(":");
  const endHour = parseInt(endTime[0]);
  const endMin = parseInt(endTime[1]);
  const endAmPm = endParts[1];

  let adjustedEndHour = endHour;
  if (endAmPm === "PM" && endHour !== 12) adjustedEndHour += 12;
  if (endAmPm === "AM" && endHour === 12) adjustedEndHour = 0;

  const durationHours =
    adjustedEndHour + endMin / 60 - (adjustedStartHour + startMin / 60);
  const height = durationHours * 60; // Increased from 50px for better visibility

  return {
    top: `${topPosition}px`,
    height: `${height}px`,
  };
}

function formatDuration(startTime, endTime) {
  const startParts = startTime.split(" ");
  const startTimeParts = startParts[0].split(":");
  let startHour = parseInt(startTimeParts[0]);
  const startMin = parseInt(startTimeParts[1]);
  const startAmPm = startParts[1];

  if (startAmPm === "PM" && startHour !== 12) startHour += 12;
  if (startAmPm === "AM" && startHour === 12) startHour = 0;

  const endParts = endTime.split(" ");
  const endTimeParts = endParts[0].split(":");
  let endHour = parseInt(endTimeParts[0]);
  const endMin = parseInt(endTimeParts[1]);
  const endAmPm = endParts[1];

  if (endAmPm === "PM" && endHour !== 12) endHour += 12;
  if (endAmPm === "AM" && endHour === 12) endHour = 0;

  // Calculate duration in minutes
  const startTotalMinutes = startHour * 60 + startMin;
  const endTotalMinutes = endHour * 60 + endMin;
  const durationMinutes = endTotalMinutes - startTotalMinutes;

  // Format duration
  const hours = Math.floor(durationMinutes / 60);
  const minutes = durationMinutes % 60;

  if (hours > 0) {
    return `${hours}h${minutes > 0 ? ` ${minutes}m` : ""}`;
  }
  return `${minutes}m`;
}

function showScheduleDetails(schedule, event) {
  selectedSchedule.value = schedule;
  showTooltip.value = true;
  
  // Calculate tooltip position
  const rect = event.target.getBoundingClientRect();
  tooltipPosition.value = {
    x: rect.left + rect.width / 2,
    y: rect.top
  };
}

function hideScheduleDetails() {
  showTooltip.value = false;
}

// Watch for changes in filteredSchedules to reset tooltip
watch(() => props.filteredSchedules, () => {
  showTooltip.value = false;
}, { deep: true });
</script>

<template>
  <Card>
    <CardContent class="p-0">
      <!-- Button row for current time scrolling -->
      <div
        v-if="getCurrentTimePosition !== null"
        class="p-2 border-b bg-muted/10"
      >
        <Button
          variant="outline"
          size="sm"
          @click="scrollToCurrentTime"
          class="text-xs w-full transition-all duration-300 hover:bg-primary hover:text-primary-foreground"
        >
          <Icon icon="lucide:clock" class="h-3 w-3 mr-1" />
          Scroll to Current Time
        </Button>
      </div>

      <div
        ref="scrollContainer"
        class="timeline-container relative overflow-y-auto max-h-[60vh]"
      >
        <div class="overflow-x-auto">
          <div class="min-w-[760px]">
            <!-- Minimum width to ensure proper layout -->
            <div class="flex">
              <!-- Time column -->
              <div
                class="sticky left-0 z-20 bg-background/95 backdrop-blur-sm w-[60px] border-r"
              >
                <div
                  class="h-14 border-b flex items-end justify-center"
                >
                  <span class="text-xs font-medium mb-1">Time</span>
                </div>

                <!-- Time slots -->
                <div class="relative">
                  <div
                    v-for="(time, index) in timeSlots"
                    :key="time"
                    class="h-14 flex items-center justify-center text-xs text-muted-foreground"
                  >
                    {{ time }}
                  </div>

                  <!-- Current time indicator on time column -->
                  <div
                    v-if="getCurrentTimePosition !== null"
                    class="absolute left-0 right-0 h-0.5 bg-primary z-30"
                    :style="{ top: `${getCurrentTimePosition}px` }"
                  ></div>
                </div>
              </div>

              <!-- Days columns -->
              <div class="flex-1 flex">
                <div
                  v-for="day in daysOfWeek"
                  :key="day"
                  class="flex-1 min-w-[100px]"
                >
                  <div
                    class="h-14 border-b flex items-end justify-center sticky top-0 z-10 bg-background/90 backdrop-blur-sm"
                  >
                    <div class="flex flex-col items-center pb-1">
                      <span class="text-xs font-medium capitalize">{{
                        day.slice(0, 3)
                      }}</span>
                      <Badge
                        variant="outline"
                        class="text-[10px] px-1 py-0 h-4 transition-all duration-300"
                        :class="day === daysOfWeek[new Date().getDay() - 1] ? 'bg-primary/10' : ''"
                      >{{ classCountByDay[day] }}</Badge>
                    </div>
                  </div>

                  <!-- Day content -->
                  <div class="relative">
                    <!-- Time slot lines -->
                    <div
                      v-for="(slot, index) in timeSlots"
                      :key="slot"
                      class="absolute w-full h-14 border-b border-gray-100"
                      :style="{ top: `${index * 60}px` }"
                      :class="{ 'bg-background': index % 2 === 0 }"
                    ></div>

                    <!-- Current time indicator line -->
                    <div
                      v-if="
                        getCurrentTimePosition !== null &&
                        day === daysOfWeek[new Date().getDay() - 1]
                      "
                      class="absolute left-0 right-0 h-px bg-primary z-30 pointer-events-none"
                      :style="{ top: `${getCurrentTimePosition}px` }"
                      ref="currentTimeRef"
                    >
                      <div
                        class="absolute -left-1 -top-1.5 flex items-center"
                      >
                        <div
                          class="w-2 h-2 rounded-full bg-primary animate-pulse"
                        ></div>
                      </div>
                    </div>

                    <!-- Schedule blocks -->
                    <div
                      v-for="schedule in filteredSchedules.filter(
                        (s) => s.day_of_week === day,
                      )"
                      :key="schedule.id"
                      class="absolute w-[90%] left-[5%] rounded-md p-2 shadow-sm z-10 text-xs cursor-pointer transform transition-all duration-300 hover:scale-[1.02] hover:shadow-md"
                      :class="[
                        schedule.color,
                        isScheduleNow(schedule)
                          ? 'ring-1 ring-primary animate-pulse-subtle'
                          : '',
                      ]"
                      :style="getSchedulePosition(schedule)"
                      @mouseenter="showScheduleDetails(schedule, $event)"
                      @mouseleave="hideScheduleDetails"
                      @touchstart="showScheduleDetails(schedule, $event)"
                      @touchend="hideScheduleDetails"
                    >
                      <div class="text-[10px] leading-tight font-medium">
                        {{ schedule.time }}
                        <span class="ml-1 text-[9px] opacity-75">
                          ({{ formatDuration(schedule.start_time, schedule.end_time) }})
                        </span>
                      </div>
                      <div class="font-bold truncate">
                        {{ schedule.subject }}
                      </div>
                      <div
                        class="text-[10px] leading-tight mt-0.5 truncate"
                      >
                        {{ schedule.room }}
                      </div>
                      <div v-if="isScheduleNow(schedule)" class="absolute top-1 right-1">
                        <div class="w-2 h-2 rounded-full bg-primary animate-pulse"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Schedule details tooltip -->
      <div 
        v-if="showTooltip && selectedSchedule" 
        class="fixed z-50 bg-popover text-popover-foreground shadow-lg rounded-md p-3 w-64 transform -translate-x-1/2 -translate-y-full transition-opacity duration-200"
        :style="{
          left: `${tooltipPosition.x}px`,
          top: `${tooltipPosition.y - 10}px`,
          opacity: showTooltip ? 1 : 0
        }"
      >
        <div class="flex justify-between items-start mb-2">
          <div class="font-bold">{{ selectedSchedule.subject }}</div>
          <Badge :class="selectedSchedule.color.replace('bg-', 'bg-opacity-20 ').replace('text-', '')">
            {{ formatDuration(selectedSchedule.start_time, selectedSchedule.end_time) }}
          </Badge>
        </div>
        <div class="text-xs space-y-1">
          <div class="flex justify-between">
            <span class="text-muted-foreground">Time:</span>
            <span class="font-medium">{{ selectedSchedule.time }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">Room:</span>
            <span class="font-medium">{{ selectedSchedule.room }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">Teacher:</span>
            <span class="font-medium">{{ selectedSchedule.teacher }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">Section:</span>
            <span class="font-medium">{{ selectedSchedule.section }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">Subject Code:</span>
            <span class="font-medium">{{ selectedSchedule.subject_code }}</span>
          </div>
        </div>
        <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 translate-y-1/2 rotate-45 w-3 h-3 bg-popover"></div>
      </div>

      <!-- Mobile note about scrolling -->
      <div
        class="text-xs text-center text-muted-foreground p-2 border-t"
      >
        <span class="hidden md:inline">Swipe to navigate the schedule</span>
        <span class="md:hidden">Tap on a class for details</span>
      </div>
    </CardContent>
  </Card>
</template>

<style scoped>
.timeline-container {
  scrollbar-width: thin;
  scrollbar-color: #ddd #f1f1f1;
}

.timeline-container::-webkit-scrollbar {
  height: 6px;
  width: 6px;
}

.timeline-container::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.timeline-container::-webkit-scrollbar-thumb {
  background: #ddd;
  border-radius: 3px;
}

.timeline-container::-webkit-scrollbar-thumb:hover {
  background: #ccc;
}

@keyframes pulse {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse-subtle {
  animation: pulse-subtle 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse-subtle {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.85;
  }
}
</style>
