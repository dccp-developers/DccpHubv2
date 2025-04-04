<script setup>
import { ref, computed, onMounted } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from "@/Components/shadcn/ui/tabs";

// Import our custom components
import ScheduleHeader from "./Components/ScheduleHeader.vue";
import ScheduleStats from "./Components/ScheduleStats.vue";
import ScheduleList from "./Components/ScheduleList.vue";
import ScheduleTimeline from "./Components/ScheduleTimeline.vue";
import ScheduleGrid from "./Components/ScheduleGrid.vue";

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

// Set default view based on screen size
onMounted(() => {
  if (window.innerWidth >= 768) {
    viewMode.value = "timeline";
  }
});

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
      <ScheduleHeader
        v-model:selected-day="selectedDay"
        :days-of-week="daysOfWeek"
        :current-semester="currentSemester"
        :current-school-year="currentSchoolYear"
      />

      <!-- Schedule info cards -->
      <ScheduleStats
        :total-classes="totalClasses"
        :unique-subjects="uniqueSubjects"
        :busiest-day="busiestDay"
        :class-count-by-day="classCountByDay"
        :days-of-week="daysOfWeek"
      />

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
          <ScheduleList
            :schedules-by-subject="schedulesBySubject"
            :is-schedule-now="isScheduleNow"
          />
        </TabsContent>

        <!-- Timeline View -->
        <TabsContent value="timeline" class="mt-4">
          <ScheduleTimeline
            :filtered-schedules="filteredSchedules"
            :days-of-week="daysOfWeek"
            :class-count-by-day="classCountByDay"
            :is-schedule-now="isScheduleNow"
            :time-slots="timeSlots"
          />
        </TabsContent>

        <!-- Grid View -->
        <TabsContent value="grid" class="mt-4">
          <ScheduleGrid
            :filtered-schedules="filteredSchedules"
            :days-of-week="daysOfWeek"
            :class-count-by-day="classCountByDay"
            :is-schedule-now="isScheduleNow"
          />
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Global styles for the schedule page */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
