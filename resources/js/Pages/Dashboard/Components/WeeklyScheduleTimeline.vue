<script setup>
import { computed, ref } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/shadcn/ui/card';
import { Button } from '@/Components/shadcn/ui/button';
import { Badge } from '@/Components/shadcn/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/shadcn/ui/tabs';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/shadcn/ui/tooltip';
import { Icon } from '@iconify/vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const props = defineProps({
  weeklySchedule: {
    type: Array,
    default: () => []
  },
  currentDay: {
    type: String,
    default: () => new Date().toLocaleDateString('en-US', { weekday: 'long' })
  }
});

// Days of the week
const daysOfWeek = [
  { key: 'Monday', short: 'Mon', icon: 'lucide:calendar' },
  { key: 'Tuesday', short: 'Tue', icon: 'lucide:calendar' },
  { key: 'Wednesday', short: 'Wed', icon: 'lucide:calendar' },
  { key: 'Thursday', short: 'Thu', icon: 'lucide:calendar' },
  { key: 'Friday', short: 'Fri', icon: 'lucide:calendar' },
  { key: 'Saturday', short: 'Sat', icon: 'lucide:calendar' }
];

// Time slots (7 AM to 6 PM)
const timeSlots = [
  '7:00 AM', '8:00 AM', '9:00 AM', '10:00 AM', '11:00 AM', '12:00 PM',
  '1:00 PM', '2:00 PM', '3:00 PM', '4:00 PM', '5:00 PM', '6:00 PM'
];

// Enhanced color palette with proper dark mode support
const subjectColorPalette = [
  {
    bg: 'bg-blue-500',
    light: 'bg-blue-50 dark:bg-blue-950/50',
    text: 'text-blue-700 dark:text-blue-300',
    border: 'border-blue-200 dark:border-blue-800',
    ring: 'ring-blue-500 dark:ring-blue-400',
    accent: 'bg-blue-500 dark:bg-blue-400'
  },
  {
    bg: 'bg-emerald-500',
    light: 'bg-emerald-50 dark:bg-emerald-950/50',
    text: 'text-emerald-700 dark:text-emerald-300',
    border: 'border-emerald-200 dark:border-emerald-800',
    ring: 'ring-emerald-500 dark:ring-emerald-400',
    accent: 'bg-emerald-500 dark:bg-emerald-400'
  },
  {
    bg: 'bg-purple-500',
    light: 'bg-purple-50 dark:bg-purple-950/50',
    text: 'text-purple-700 dark:text-purple-300',
    border: 'border-purple-200 dark:border-purple-800',
    ring: 'ring-purple-500 dark:ring-purple-400',
    accent: 'bg-purple-500 dark:bg-purple-400'
  },
  {
    bg: 'bg-orange-500',
    light: 'bg-orange-50 dark:bg-orange-950/50',
    text: 'text-orange-700 dark:text-orange-300',
    border: 'border-orange-200 dark:border-orange-800',
    ring: 'ring-orange-500 dark:ring-orange-400',
    accent: 'bg-orange-500 dark:bg-orange-400'
  },
  {
    bg: 'bg-pink-500',
    light: 'bg-pink-50 dark:bg-pink-950/50',
    text: 'text-pink-700 dark:text-pink-300',
    border: 'border-pink-200 dark:border-pink-800',
    ring: 'ring-pink-500 dark:ring-pink-400',
    accent: 'bg-pink-500 dark:bg-pink-400'
  },
  {
    bg: 'bg-indigo-500',
    light: 'bg-indigo-50 dark:bg-indigo-950/50',
    text: 'text-indigo-700 dark:text-indigo-300',
    border: 'border-indigo-200 dark:border-indigo-800',
    ring: 'ring-indigo-500 dark:ring-indigo-400',
    accent: 'bg-indigo-500 dark:bg-indigo-400'
  },
  {
    bg: 'bg-teal-500',
    light: 'bg-teal-50 dark:bg-teal-950/50',
    text: 'text-teal-700 dark:text-teal-300',
    border: 'border-teal-200 dark:border-teal-800',
    ring: 'ring-teal-500 dark:ring-teal-400',
    accent: 'bg-teal-500 dark:bg-teal-400'
  },
  {
    bg: 'bg-red-500',
    light: 'bg-red-50 dark:bg-red-950/50',
    text: 'text-red-700 dark:text-red-300',
    border: 'border-red-200 dark:border-red-800',
    ring: 'ring-red-500 dark:ring-red-400',
    accent: 'bg-red-500 dark:bg-red-400'
  },
  {
    bg: 'bg-amber-500',
    light: 'bg-amber-50 dark:bg-amber-950/50',
    text: 'text-amber-700 dark:text-amber-300',
    border: 'border-amber-200 dark:border-amber-800',
    ring: 'ring-amber-500 dark:ring-amber-400',
    accent: 'bg-amber-500 dark:bg-amber-400'
  },
  {
    bg: 'bg-cyan-500',
    light: 'bg-cyan-50 dark:bg-cyan-950/50',
    text: 'text-cyan-700 dark:text-cyan-300',
    border: 'border-cyan-200 dark:border-cyan-800',
    ring: 'ring-cyan-500 dark:ring-cyan-400',
    accent: 'bg-cyan-500 dark:bg-cyan-400'
  }
];

// Get consistent color for a subject
const getSubjectColor = (subject, subjectCode) => {
  // Create a hash from subject code or subject name for consistent colors
  const text = subjectCode || subject || 'default';
  let hash = 0;
  for (let i = 0; i < text.length; i++) {
    const char = text.charCodeAt(i);
    hash = ((hash << 5) - hash) + char;
    hash = hash & hash; // Convert to 32-bit integer
  }

  const colorIndex = Math.abs(hash) % subjectColorPalette.length;
  return subjectColorPalette[colorIndex];
};

// Mobile view - current day selector
const selectedDay = ref(props.currentDay);

// Get formatted time for display
const formatTime = (time) => {
  return time.replace(':00', '');
};

// Get schedule for a specific day
const getScheduleForDay = (day) => {
  const dayKey = typeof day === 'string' ? day : day.key;
  return props.weeklySchedule.filter(item =>
    item.day === dayKey || item.day_of_week === dayKey
  ) || [];
};

// Get current time indicator position
const getCurrentTimePosition = () => {
  const now = new Date();
  const currentHour = now.getHours();
  const currentMinute = now.getMinutes();
  
  // Only show during school hours (7 AM to 6 PM)
  if (currentHour < 7 || currentHour >= 18) return null;
  
  const totalMinutes = (currentHour - 7) * 60 + currentMinute;
  const totalSchoolMinutes = 11 * 60; // 11 hours from 7 AM to 6 PM
  
  return (totalMinutes / totalSchoolMinutes) * 100;
};

// Check if a class is currently active
const isClassActive = (classItem) => {
  const now = new Date();
  const today = now.toLocaleDateString('en-US', { weekday: 'long' });

  const classDay = classItem.day || classItem.day_of_week;
  if (classDay !== today) return false;

  // Parse start and end times
  const startTime = new Date(`1970-01-01 ${classItem.start_time}`);
  const endTime = new Date(`1970-01-01 ${classItem.end_time}`);
  const currentTime = new Date(`1970-01-01 ${now.toTimeString().split(' ')[0]}`);

  return currentTime >= startTime && currentTime <= endTime;
};

// Navigate to full schedule
const viewFullSchedule = () => {
  router.visit(route('schedule.index'));
};

// Handle class click
const handleClassClick = (classItem) => {
  // Could navigate to class details or show more info
  console.log('Class clicked:', classItem);
};
</script>

<template>
  <Card class="border border-border/50 shadow-sm bg-card dark:bg-card">
    <CardHeader class="pb-3">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="p-2 rounded-xl bg-primary/10 dark:bg-primary/20 border border-primary/20 dark:border-primary/30">
            <Icon icon="lucide:calendar-days" class="h-5 w-5 text-primary dark:text-primary" />
          </div>
          <div>
            <CardTitle class="text-base font-bold text-foreground dark:text-foreground">
              Weekly Schedule
            </CardTitle>
            <p class="text-xs text-muted-foreground dark:text-muted-foreground">Your complete class timeline</p>
          </div>
        </div>
        <Button variant="outline" size="sm" @click="viewFullSchedule" class="text-xs hover:bg-muted/50 dark:hover:bg-muted/50">
          <Icon icon="lucide:external-link" class="h-3 w-3 mr-1" />
          View All
        </Button>
      </div>
    </CardHeader>

    <CardContent class="p-4">
      <!-- Desktop View with Enhanced Grid -->
      <div class="hidden lg:block">
        <!-- Enhanced header with dark mode support -->
        <div class="grid grid-cols-7 gap-2 mb-4 p-3 bg-muted/30 dark:bg-muted/20 rounded-lg border border-border/50 dark:border-border/30">
          <!-- Time column header -->
          <div class="text-xs font-semibold text-muted-foreground dark:text-muted-foreground p-2 flex items-center">
            <Icon icon="lucide:clock" class="h-3 w-3 mr-1" />
            Time
          </div>
          <!-- Day headers -->
          <div v-for="day in daysOfWeek" :key="day.key"
               class="text-xs font-semibold text-center p-2 rounded-md transition-colors"
               :class="day.key === currentDay ? 'bg-primary text-primary-foreground dark:bg-primary dark:text-primary-foreground' : 'text-muted-foreground dark:text-muted-foreground hover:bg-muted/50 dark:hover:bg-muted/30'">
            <div class="flex flex-col items-center">
              <span>{{ day.short }}</span>
              <span class="text-xs opacity-75 mt-0.5">{{ getScheduleForDay(day.key).length }}</span>
            </div>
          </div>
        </div>

        <!-- Enhanced time slots grid with dark mode support -->
        <div class="relative bg-background dark:bg-background rounded-lg border border-border dark:border-border">
          <div v-for="(time, timeIndex) in timeSlots.slice(0, 8)" :key="time"
               class="grid grid-cols-7 gap-2 border-b border-border/30 dark:border-border/20 min-h-[60px] hover:bg-muted/20 dark:hover:bg-muted/10 transition-colors">
            <!-- Time label with dark mode support -->
            <div class="text-xs font-medium text-muted-foreground dark:text-muted-foreground p-3 bg-muted/20 dark:bg-muted/10 flex items-center justify-center border-r border-border/30 dark:border-border/20">
              <div class="text-center">
                <div class="font-semibold">{{ formatTime(time) }}</div>
                <div class="text-xs opacity-60">{{ time.includes('AM') ? 'AM' : 'PM' }}</div>
              </div>
            </div>

            <!-- Day columns -->
            <div v-for="day in daysOfWeek" :key="`${day.key}-${time}`" class="relative p-2">
              <TooltipProvider v-for="classItem in getScheduleForDay(day.key).filter(c => {
                     const classHour = parseInt(c.start_time.split(':')[0]);
                     const timeHour = parseInt(time.split(':')[0]);
                     const isPM = c.start_time.includes('PM');
                     const timeIsPM = time.includes('PM');

                     // Convert to 24-hour format for comparison
                     const classHour24 = isPM && classHour !== 12 ? classHour + 12 : (classHour === 12 && !isPM ? 0 : classHour);
                     const timeHour24 = timeIsPM && timeHour !== 12 ? timeHour + 12 : (timeHour === 12 && !timeIsPM ? 0 : timeHour);

                     return classHour24 === timeHour24;
                   })" :key="classItem.id">
                <Tooltip>
                  <TooltipTrigger asChild>
                    <div @click="handleClassClick(classItem)"
                         class="group relative overflow-hidden rounded-lg p-3 cursor-pointer transition-all duration-300 hover:shadow-lg dark:hover:shadow-xl hover:scale-105 border-2 mb-1"
                         :class="[
                           getSubjectColor(classItem.subject, classItem.subject_code).light,
                           getSubjectColor(classItem.subject, classItem.subject_code).border,
                           isClassActive(classItem) ? 'ring-2 ring-offset-1 dark:ring-offset-0' : '',
                           isClassActive(classItem) ? getSubjectColor(classItem.subject, classItem.subject_code).ring : ''
                         ]">

                      <!-- Gradient overlay with dark mode support -->
                      <div class="absolute inset-0 bg-gradient-to-br from-white/20 dark:from-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>

                      <!-- Content -->
                      <div class="relative z-10">
                        <!-- Subject Code -->
                        <div class="text-xs font-bold truncate mb-1"
                             :class="getSubjectColor(classItem.subject, classItem.subject_code).text">
                          {{ classItem.subject_code }}
                        </div>

                        <!-- Subject Name (truncated) -->
                        <div class="text-xs font-medium truncate mb-1 opacity-90"
                             :class="getSubjectColor(classItem.subject, classItem.subject_code).text">
                          {{ classItem.subject }}
                        </div>

                        <!-- Time with dark mode support -->
                        <div class="text-xs opacity-75 dark:opacity-80 mb-1 text-foreground dark:text-foreground">
                          {{ formatTime(classItem.start_time) }} - {{ formatTime(classItem.end_time) }}
                        </div>

                        <!-- Room with dark mode support -->
                        <div class="text-xs opacity-75 dark:opacity-80 truncate text-foreground dark:text-foreground">
                          <Icon icon="lucide:map-pin" class="h-3 w-3 inline mr-1" />
                          {{ classItem.room }}
                        </div>

                        <!-- Teacher (if available) with dark mode support -->
                        <div v-if="classItem.teacher && classItem.teacher !== 'TBA'" class="text-xs opacity-75 dark:opacity-80 truncate mt-1 text-foreground dark:text-foreground">
                          <Icon icon="lucide:user" class="h-3 w-3 inline mr-1" />
                          {{ classItem.teacher }}
                        </div>

                        <!-- Enhanced Active indicator with dark mode support -->
                        <div v-if="isClassActive(classItem)"
                             class="absolute top-2 right-2 flex items-center gap-1">
                          <div class="w-2 h-2 bg-green-500 dark:bg-green-400 rounded-full animate-pulse"></div>
                          <span class="text-xs font-bold text-green-600 dark:text-green-400">LIVE</span>
                        </div>
                      </div>
                    </div>
                  </TooltipTrigger>

                  <TooltipContent class="max-w-xs p-3">
                    <div class="space-y-2">
                      <div class="font-semibold text-sm">{{ classItem.subject }}</div>
                      <div class="text-xs space-y-1">
                        <div><strong>Code:</strong> {{ classItem.subject_code }}</div>
                        <div><strong>Time:</strong> {{ classItem.start_time }} - {{ classItem.end_time }}</div>
                        <div><strong>Room:</strong> {{ classItem.room }}</div>
                        <div v-if="classItem.teacher && classItem.teacher !== 'TBA'">
                          <strong>Teacher:</strong> {{ classItem.teacher }}
                        </div>
                        <div v-if="classItem.section">
                          <strong>Section:</strong> {{ classItem.section }}
                        </div>
                        <div v-if="isClassActive(classItem)" class="text-green-600 font-semibold">
                          🔴 Currently Active
                        </div>
                      </div>
                    </div>
                  </TooltipContent>
                </Tooltip>
              </TooltipProvider>
            </div>
          </div>
        </div>
      </div>

      <!-- Mobile/Tablet View with Enhanced UX -->
      <div class="lg:hidden">
        <Tabs :default-value="selectedDay" class="w-full">
          <TabsList class="grid w-full grid-cols-6 mb-4">
            <TabsTrigger
              v-for="day in daysOfWeek"
              :key="day.key"
              :value="day.key"
              @click="selectedDay = day.key"
              class="text-xs font-medium data-[state=active]:bg-primary data-[state=active]:text-primary-foreground"
            >
              <div class="flex flex-col items-center">
                <span class="hidden sm:inline">{{ day.short }}</span>
                <span class="sm:hidden">{{ day.short.substring(0, 1) }}</span>
              </div>
            </TabsTrigger>
          </TabsList>

          <TabsContent v-for="day in daysOfWeek" :key="day.key" :value="day.key" class="mt-0">
            <div class="space-y-3">
              <!-- Day header -->
              <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-sm">{{ day.key }}</h3>
                <Badge variant="outline" class="text-xs">
                  {{ getScheduleForDay(day.key).length }} classes
                </Badge>
              </div>

              <!-- Classes for the day -->
              <div v-for="classItem in getScheduleForDay(day.key)" :key="classItem.id"
                   @click="handleClassClick(classItem)"
                   class="group relative overflow-hidden rounded-xl border transition-all duration-300 hover:shadow-md dark:hover:shadow-lg cursor-pointer"
                   :class="[
                     getSubjectColor(classItem.subject, classItem.subject_code).light,
                     getSubjectColor(classItem.subject, classItem.subject_code).border,
                     isClassActive(classItem) ? 'ring-2 ring-offset-2 dark:ring-offset-1' : '',
                     isClassActive(classItem) ? getSubjectColor(classItem.subject, classItem.subject_code).ring : ''
                   ]">

                <!-- Color accent bar with dark mode support -->
                <div class="absolute left-0 top-0 bottom-0 w-1 transition-all duration-300 group-hover:w-2"
                     :class="getSubjectColor(classItem.subject, classItem.subject_code).accent">
                </div>

                <div class="p-4 pl-6">
                  <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                      <div class="flex items-start gap-2 mb-2">
                        <div class="flex-1 min-w-0">
                          <h4 class="font-semibold text-sm leading-tight line-clamp-2"
                              :class="getSubjectColor(classItem.subject, classItem.subject_code).text"
                              :title="classItem.subject">
                            {{ classItem.subject }}
                          </h4>
                        </div>
                        <Badge variant="secondary" class="text-xs px-2 py-0.5 flex-shrink-0">
                          {{ classItem.subject_code }}
                        </Badge>
                      </div>

                      <div class="flex items-center gap-4 text-xs text-muted-foreground dark:text-muted-foreground mt-2">
                        <div class="flex items-center gap-1">
                          <Icon icon="lucide:clock" class="h-3 w-3" />
                          <span>{{ formatTime(classItem.start_time) }} - {{ formatTime(classItem.end_time) }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                          <Icon icon="lucide:map-pin" class="h-3 w-3" />
                          <span>{{ classItem.room }}</span>
                        </div>
                      </div>

                      <div v-if="classItem.teacher && classItem.teacher !== 'TBA'" class="flex items-center gap-1 text-xs text-muted-foreground dark:text-muted-foreground mt-1">
                        <Icon icon="lucide:user" class="h-3 w-3" />
                        <span>{{ classItem.teacher }}</span>
                      </div>
                    </div>

                    <!-- Status indicator with dark mode support -->
                    <div class="flex flex-col items-end gap-2">
                      <Badge v-if="isClassActive(classItem)" variant="default" class="text-xs bg-green-500 dark:bg-green-600 text-white dark:text-white">
                        <Icon icon="lucide:play" class="h-3 w-3 mr-1" />
                        Live
                      </Badge>
                      <Icon icon="lucide:chevron-right" class="h-4 w-4 text-muted-foreground dark:text-muted-foreground group-hover:translate-x-1 transition-transform" />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Empty state with dark mode support -->
              <div v-if="getScheduleForDay(day.key).length === 0" class="text-center py-8">
                <div class="bg-muted/50 dark:bg-muted/30 rounded-full p-4 w-fit mx-auto mb-3">
                  <Icon icon="lucide:calendar-x" class="h-8 w-8 text-muted-foreground dark:text-muted-foreground" />
                </div>
                <h4 class="font-medium text-sm mb-1 text-foreground dark:text-foreground">No classes today</h4>
                <p class="text-xs text-muted-foreground dark:text-muted-foreground">Enjoy your free day!</p>
              </div>
            </div>
          </TabsContent>
        </Tabs>
      </div>
    </CardContent>
  </Card>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
