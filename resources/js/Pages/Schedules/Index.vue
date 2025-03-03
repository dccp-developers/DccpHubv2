<script setup>
import { ref, computed, onMounted } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Button } from "@/Components/shadcn/ui/button";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/Components/shadcn/ui/card";
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from "@/Components/shadcn/ui/tabs";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/shadcn/ui/select";
import { Badge } from "@/Components/shadcn/ui/badge";
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/Components/shadcn/ui/tooltip";
import { Icon } from "@iconify/vue";

const props = defineProps({
  schedules: {
    type: Array,
    required: true,
  },
  currentSemester: {
    type: String,
    required: true,
  },
  currentSchoolYear: {
    type: String,
    required: true,
  },
});

const daysOfWeek = [
  "monday",
  "tuesday",
  "wednesday",
  "thursday",
  "friday",
  "saturday",
  "sunday",
];
const viewMode = ref("list"); // Default to list view for better mobile experience
const selectedDay = ref("all");
const currentTimeRef = ref(null);
const currentTime = ref(new Date());
const scrollContainer = ref(null);

// Update current time every minute
onMounted(() => {
  // Set default view based on screen size
  if (window.innerWidth >= 768) {
    viewMode.value = "timeline";
  }

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

// Filter schedules by selected day
const filteredSchedules = computed(() => {
  if (selectedDay.value === "all") {
    return props.schedules;
  }
  return props.schedules.filter(
    (schedule) => schedule.day_of_week === selectedDay.value,
  );
});

// Calculate schedule statistics
const totalClasses = computed(() => filteredSchedules.value.length);

const uniqueSubjects = computed(() => {
  const subjects = new Set(filteredSchedules.value.map((s) => s.subject));
  return subjects.size;
});

const classCountByDay = computed(() => {
  const counts = {};
  daysOfWeek.forEach((day) => {
    counts[day] = filteredSchedules.value.filter(
      (s) => s.day_of_week === day,
    ).length;
  });
  return counts;
});

const busiestDay = computed(() => {
  const dayWithMostClasses = daysOfWeek.reduce((busiest, day) => {
    const count = classCountByDay.value[day];
    if (!busiest || count > busiest.count) {
      return { day, count };
    }
    return busiest;
  }, null);

  return dayWithMostClasses || { day: "None", count: 0 };
});

// Generate time slots from 6 AM to 9 PM
const timeSlots = computed(() => {
  const slots = [];
  for (let hour = 6; hour <= 21; hour++) {
    const formattedHour = hour > 12 ? hour - 12 : hour;
    const period = hour >= 12 ? "PM" : "AM";
    slots.push(`${formattedHour}:00 ${period}`);
  }
  return slots;
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
  const topPosition = hoursSince6Am * 50; // Reduced from 64px for mobile compatibility

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
  const height = durationHours * 50; // Reduced from 64px for mobile compatibility

  return {
    top: `${topPosition}px`,
    height: `${height}px`,
  };
}

// Check if a schedule is currently happening
function isScheduleNow(schedule) {
  const now = new Date();
  const currentDay = daysOfWeek[now.getDay() - 1]; // Convert JS day (1-7) to our format

  if (schedule.day_of_week !== currentDay) return false;

  const currentHour = now.getHours();
  const currentMinute = now.getMinutes();

  // Parse start time
  const startParts = schedule.start_time.split(" ");
  const startTime = startParts[0].split(":");
  let startHour = parseInt(startTime[0]);
  const startMin = parseInt(startTime[1]);
  const startAmPm = startParts[1];

  if (startAmPm === "PM" && startHour !== 12) startHour += 12;
  if (startAmPm === "AM" && startHour === 12) startHour = 0;

  // Parse end time
  const endParts = schedule.end_time.split(" ");
  const endTime = endParts[0].split(":");
  let endHour = parseInt(endTime[0]);
  const endMin = parseInt(endTime[1]);
  const endAmPm = endParts[1];

  if (endAmPm === "PM" && endHour !== 12) endHour += 12;
  if (endAmPm === "AM" && endHour === 12) endHour = 0;

  // Convert all to minutes for easier comparison
  const currentTimeInMinutes = currentHour * 60 + currentMinute;
  const startTimeInMinutes = startHour * 60 + startMin;
  const endTimeInMinutes = endHour * 60 + endMin;

  return (
    currentTimeInMinutes >= startTimeInMinutes &&
    currentTimeInMinutes <= endTimeInMinutes
  );
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
  return hoursSince6Am * 50; // Reduced from 64px for mobile compatibility
});

// Format duration text
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

// Group schedules by subject for list view
const schedulesBySubject = computed(() => {
  const grouped = {};

  filteredSchedules.value.forEach((schedule) => {
    if (!grouped[schedule.subject]) {
      grouped[schedule.subject] = {
        subject: schedule.subject,
        code: schedule.subject_code,
        color: schedule.color,
        instances: [],
      };
    }

    grouped[schedule.subject].instances.push(schedule);
  });

  return Object.values(grouped);
});
</script>

<template>
  <AppLayout :title="`My Schedule`">
    <div class="md:container mx-auto px-4 py-6 space-y-6">
      <!-- Header section with controls -->
      <header class="flex flex-col sm:flex-row justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold">My Class Schedule</h1>
          <p class="text-sm text-muted-foreground">
            {{ currentSemester }} Semester, School Year {{ currentSchoolYear }}
          </p>
        </div>

        <div class="flex items-center gap-2">
          <Select v-model="selectedDay" class="w-full sm:w-40">
            <SelectTrigger>
              <SelectValue placeholder="Filter by day" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Days</SelectItem>
              <SelectItem v-for="day in daysOfWeek" :key="day" :value="day">
                {{ day.charAt(0).toUpperCase() + day.slice(1) }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </header>

      <!-- Schedule info cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Total Classes</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ totalClasses }}</div>
            <p class="text-xs text-muted-foreground">Classes this semester</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Subjects</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ uniqueSubjects }}</div>
            <p class="text-xs text-muted-foreground">Enrolled subjects</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Busiest Day</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold capitalize md:text-2xl text-xl">
              {{ busiestDay.day }}
            </div>
            <p class="text-xs text-muted-foreground">
              {{ busiestDay.count }} classes
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Today</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ classCountByDay[daysOfWeek[new Date().getDay() - 1]] || 0 }}
            </div>
            <p class="text-xs text-muted-foreground">Classes today</p>
          </CardContent>
        </Card>
      </div>

      <!-- Main schedule view -->
      <Tabs v-model="viewMode" class="w-full">
        <TabsList class="grid w-full grid-cols-2 md:grid-cols-3">
          <TabsTrigger value="list">List</TabsTrigger>
          <TabsTrigger class="hidden md:block" value="timeline"
            >Timeline</TabsTrigger
          >
          <TabsTrigger value="grid">Grid</TabsTrigger>
        </TabsList>

        <!-- List View -->
        <TabsContent value="list" class="mt-4">
          <Card>
            <CardContent class="p-0">
              <div class="max-h-[60vh] overflow-y-auto">
                <div
                  v-if="schedulesBySubject.length === 0"
                  class="py-8 text-center text-muted-foreground"
                >
                  No classes scheduled
                </div>

                <div v-else>
                  <div
                    v-for="group in schedulesBySubject"
                    :key="group.subject"
                    class="border-b last:border-0"
                  >
                    <div
                      class="p-3 cursor-pointer hover:bg-muted/50 flex justify-between items-center"
                    >
                      <div>
                        <h3 class="font-bold text-sm">{{ group.subject }}</h3>
                        <p class="text-xs text-muted-foreground">
                          {{ group.code }} •
                          {{ group.instances.length }} sessions
                        </p>
                      </div>
                      <Badge
                        :class="
                          group.color
                            .replace('bg-', 'bg-opacity-20 ')
                            .replace('text-', '')
                        "
                      >
                        {{ group.instances.length }}
                      </Badge>
                    </div>

                    <div class="px-3 pb-3 grid gap-2">
                      <div
                        v-for="schedule in group.instances"
                        :key="schedule.id"
                        class="p-2 rounded-md border flex flex-col sm:flex-row sm:items-center gap-2"
                        :class="
                          isScheduleNow(schedule)
                            ? 'border-primary ring-1 ring-primary'
                            : ''
                        "
                      >
                        <div class="flex gap-2 items-center sm:w-1/4">
                          <div
                            class="w-7 h-7 rounded-full flex items-center justify-center"
                            :class="schedule.color"
                          >
                            <span class="uppercase font-bold text-xs">{{
                              schedule.day_of_week.slice(0, 2)
                            }}</span>
                          </div>
                          <div>
                            <div class="capitalize text-sm font-medium">
                              {{ schedule.day_of_week }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                              {{ schedule.time }}
                            </div>
                          </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 sm:w-3/4">
                          <div class="text-xs">
                            <div class="font-medium">Room</div>
                            <div>{{ schedule.room }}</div>
                          </div>

                          <div class="text-xs">
                            <div class="font-medium">Teacher</div>
                            <div class="truncate">{{ schedule.teacher }}</div>
                          </div>

                          <div class="text-xs">
                            <div class="font-medium">Section</div>
                            <div>{{ schedule.section }}</div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Timeline View -->
        <TabsContent value="timeline" class="mt-4">
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
                  class="text-xs w-full"
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
                        class="sticky left-0 z-20 bg-background/95 backdrop-blur-sm w-[50px] border-r"
                      >
                        <div
                          class="h-12 border-b flex items-end justify-center"
                        >
                          <span class="text-xs font-medium mb-1">Time</span>
                        </div>

                        <!-- Time slots -->
                        <div class="relative">
                          <div
                            v-for="(time, index) in timeSlots"
                            :key="time"
                            class="h-12 flex items-center justify-center text-xs text-muted-foreground"
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
                            class="h-12 border-b flex items-end justify-center sticky top-0 z-10 bg-background/90 backdrop-blur-sm"
                          >
                            <div class="flex flex-col items-center pb-1">
                              <span class="text-xs font-medium capitalize">{{
                                day.slice(0, 3)
                              }}</span>
                              <Badge
                                variant="outline"
                                class="text-[10px] px-1 py-0 h-4"
                                >{{ classCountByDay[day] }}</Badge
                              >
                            </div>
                          </div>

                          <!-- Day content -->
                          <div class="relative">
                            <!-- Time slot lines -->
                            <div
                              v-for="(slot, index) in timeSlots"
                              :key="slot"
                              class="absolute w-full h-12 border-b border-gray-100"
                              :style="{ top: `${index * 50}px` }"
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
                                  class="w-2 h-2 rounded-full bg-primary"
                                ></div>
                              </div>
                            </div>

                            <!-- Schedule blocks -->
                            <div
                              v-for="schedule in filteredSchedules.filter(
                                (s) => s.day_of_week === day,
                              )"
                              :key="schedule.id"
                              class="absolute w-[90%] left-[5%] rounded-md p-1 shadow-sm transition-all hover:shadow-md z-10 text-xs"
                              :class="[
                                schedule.color,
                                isScheduleNow(schedule)
                                  ? 'ring-1 ring-primary'
                                  : '',
                              ]"
                              :style="getSchedulePosition(schedule)"
                            >
                              <div class="text-[10px] leading-tight">
                                {{ schedule.time }}
                              </div>
                              <div class="font-bold truncate">
                                {{ schedule.subject }}
                              </div>
                              <div
                                class="text-[10px] leading-tight mt-0.5 truncate"
                              >
                                {{ schedule.room }}
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Mobile note about scrolling -->
              <div
                class="text-xs text-center text-muted-foreground p-2 border-t"
              >
                Swipe to navigate the schedule
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Grid View -->
        <TabsContent value="grid" class="mt-4">
          <Card>
            <CardContent class="p-0">
              <div class="max-h-[60vh] overflow-y-auto p-3">
                <div
                  class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3"
                >
                  <div
                    v-for="day in daysOfWeek"
                    :key="day"
                    class="border rounded-lg overflow-hidden"
                  >
                    <div
                      class="bg-muted/30 p-2 border-b flex justify-between items-center"
                    >
                      <div class="capitalize font-bold text-sm">{{ day }}</div>
                      <Badge>{{ classCountByDay[day] }}</Badge>
                    </div>

                    <div class="p-2 space-y-2">
                      <div
                        v-if="
                          filteredSchedules.filter((s) => s.day_of_week === day)
                            .length > 0
                        "
                        class="space-y-2"
                      >
                        <div
                          v-for="schedule in filteredSchedules.filter(
                            (s) => s.day_of_week === day,
                          )"
                          :key="schedule.id"
                          class="p-2 rounded-md transition-all hover:shadow-md text-xs"
                          :class="[
                            schedule.color,
                            isScheduleNow(schedule)
                              ? 'ring-1 ring-primary'
                              : '',
                          ]"
                        >
                          <div class="text-[10px]">{{ schedule.time }}</div>
                          <div class="font-bold truncate">
                            {{ schedule.subject }}
                          </div>
                          <div class="text-[10px] mt-1 flex justify-between">
                            <span class="truncate">{{ schedule.room }}</span>
                            <span class="truncate ml-1">{{
                              schedule.teacher.split(" ")[0]
                            }}</span>
                          </div>
                        </div>
                      </div>

                      <div
                        v-else
                        class="h-16 flex items-center justify-center border border-dashed rounded-md text-gray-400 text-xs"
                      >
                        No classes
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
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

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
