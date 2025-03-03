<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/Components/shadcn/ui/button'
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription
} from '@/Components/shadcn/ui/card'
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger
} from '@/Components/shadcn/ui/tabs'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/shadcn/ui/select'
import { Badge } from '@/Components/shadcn/ui/badge'
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from '@/Components/shadcn/ui/tooltip'

const props = defineProps({
  schedules: {
    type: Array,
    required: true
  },
  currentSemester: {
    type: String,
    required: true
  },
  currentSchoolYear: {
    type: String,
    required: true
  }
})

const daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']
const viewMode = ref('timeline') // Default is timeline view
const selectedDay = ref('all')
const currentTimeRef = ref(null)
const currentTime = ref(new Date())
const scrollContainer = ref(null)

// Update current time every minute
onMounted(() => {
  updateCurrentTime()
  setInterval(updateCurrentTime, 60000)

  // Scroll to current time on initial load
  setTimeout(scrollToCurrentTime, 500)
})

function updateCurrentTime() {
  currentTime.value = new Date()
}

function scrollToCurrentTime() {
  if (!scrollContainer.value) return

  const now = new Date()
  const hour = now.getHours()
  const minute = now.getMinutes()

  // Only scroll if within schedule range (7am - 9pm)
  if (hour >= 7 && hour < 21) {
    const topPos = ((hour - 7) * 60 + minute) * (1/1.5) // Scale factor to match our UI
    scrollContainer.value.scrollTo({
      top: topPos,
      behavior: 'smooth'
    })
  }
}

// Group schedules by subject for better organization
const schedulesBySubject = computed(() => {
  const grouped = {}

  props.schedules.forEach(schedule => {
    if (!grouped[schedule.subject_code]) {
      grouped[schedule.subject_code] = {
        subject: schedule.subject,
        subject_code: schedule.subject_code,
        color: schedule.color,
        schedules: []
      }
    }
    grouped[schedule.subject_code].schedules.push(schedule)
  })

  return Object.values(grouped)
})

// Filter schedules based on selected day
const filteredSchedules = computed(() => {
  if (selectedDay.value === 'all') {
    return props.schedules
  }
  return props.schedules.filter(schedule => schedule.day_of_week === selectedDay.value)
})

// Get unique subjects for the filter
const uniqueSubjects = computed(() => {
  const subjects = new Set()
  props.schedules.forEach(schedule => {
    subjects.add(schedule.subject)
  })
  return [...subjects]
})

// Get count of classes by day
const classCountByDay = computed(() => {
  const counts = {}
  daysOfWeek.forEach(day => {
    counts[day] = props.schedules.filter(s => s.day_of_week === day).length
  })
  return counts
})

// Calculate the busiest day
const busiestDay = computed(() => {
  let maxDay = daysOfWeek[0]
  let maxCount = classCountByDay.value[maxDay]

  daysOfWeek.forEach(day => {
    if (classCountByDay.value[day] > maxCount) {
      maxDay = day
      maxCount = classCountByDay.value[day]
    }
  })

  return { day: maxDay, count: maxCount }
})

// Time slots for timeline view
const timeSlots = computed(() => {
  const slots = []
  for (let hour = 7; hour <= 21; hour++) {
    const ampm = hour >= 12 ? 'PM' : 'AM'
    const hour12 = hour > 12 ? hour - 12 : (hour === 0 ? 12 : hour)
    slots.push({
      time: `${hour12}:00 ${ampm}`,
      hour: hour
    })
  }
  return slots
})

// Current time position in timeline
const currentTimePosition = computed(() => {
  const now = new Date()
  const hour = now.getHours()
  const minute = now.getMinutes()

  // Only show between 7am and 9pm
  if (hour < 7 || hour >= 21) return null

  const topPos = (hour - 7) * 60 + minute
  return `${topPos}px`
})

// Check if we should show the current time indicator
const showCurrentTimeIndicator = computed(() => {
  const now = new Date()
  const hour = now.getHours()
  return hour >= 7 && hour < 21
})

// Check if current day has classes
const currentDayHasClasses = computed(() => {
  const now = new Date()
  const dayIndex = now.getDay() // 0 = Sunday, 1 = Monday, ...

  // Convert to our day format
  const day = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'][dayIndex]

  return props.schedules.some(s => s.day_of_week === day)
})

// Get current day name
const currentDayName = computed(() => {
  const now = new Date()
  const dayIndex = now.getDay() // 0 = Sunday, 1 = Monday, ...
  return ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'][dayIndex]
})

function capitalizeFirstLetter(string) {
  return string.charAt(0).toUpperCase() + string.slice(1)
}

function getSchedulePosition(schedule) {
  // Parse the schedule time to determine position in timeline
  const startTime = schedule.start_time
  const endTime = schedule.end_time

  // Extract hours and minutes
  const startHourMatch = startTime.match(/(\d+):(\d+)\s+(AM|PM)/)
  const endHourMatch = endTime.match(/(\d+):(\d+)\s+(AM|PM)/)

  if (!startHourMatch || !endHourMatch) return { top: '0px', height: '60px' }

  let startHour = parseInt(startHourMatch[1])
  const startMin = parseInt(startHourMatch[2])
  const startAmPm = startHourMatch[3]

  let endHour = parseInt(endHourMatch[1])
  const endMin = parseInt(endHourMatch[2])
  const endAmPm = endHourMatch[3]

  // Convert to 24-hour format
  if (startAmPm === 'PM' && startHour !== 12) startHour += 12
  if (startAmPm === 'AM' && startHour === 12) startHour = 0

  if (endAmPm === 'PM' && endHour !== 12) endHour += 12
  if (endAmPm === 'AM' && endHour === 12) endHour = 0

  // Calculate top position (7:00 AM = 0)
  const topPos = (startHour - 7) * 60 + startMin

  // Calculate duration in minutes
  const durationMins = (endHour * 60 + endMin) - (startHour * 60 + startMin)

  return {
    top: `${topPos}px`,
    height: `${durationMins}px`
  }
}

// Detect overlapping schedules and adjust positioning
function getScheduleWidth(schedule, day) {
  // Find all schedules for this day that overlap with this one
  const daySchedules = props.schedules.filter(s => s.day_of_week === day)
  const overlapping = daySchedules.filter(s => {
    if (s.id === schedule.id) return false

    // Check time overlap
    const pos1 = getSchedulePosition(schedule)
    const pos2 = getSchedulePosition(s)

    const top1 = parseInt(pos1.top)
    const bottom1 = top1 + parseInt(pos1.height)
    const top2 = parseInt(pos2.top)
    const bottom2 = top2 + parseInt(pos2.height)

    return (top1 < bottom2 && bottom1 > top2)
  })

  // If overlapping, make it narrower
  if (overlapping.length > 0) {
    const index = overlapping.findIndex(s => s.id === schedule.id) || 0
    const leftPos = 5 + (index * 45) // Stagger horizontally
    return { width: '50%', left: `${leftPos}%` }
  }

  return { width: '90%', left: '5%' }
}

// Format duration nicely
function formatDuration(startTime, endTime) {
  // Extract hours and minutes
  const startMatch = startTime.match(/(\d+):(\d+)\s+(AM|PM)/)
  const endMatch = endTime.match(/(\d+):(\d+)\s+(AM|PM)/)

  if (!startMatch || !endMatch) return "Unknown duration"

  let startHour = parseInt(startMatch[1])
  const startMin = parseInt(startMatch[2])
  const startAmPm = startMatch[3]

  let endHour = parseInt(endMatch[1])
  const endMin = parseInt(endMatch[2])
  const endAmPm = endMatch[3]

  // Convert to 24-hour format
  if (startAmPm === 'PM' && startHour !== 12) startHour += 12
  if (startAmPm === 'AM' && startHour === 12) startHour = 0

  if (endAmPm === 'PM' && endHour !== 12) endHour += 12
  if (endAmPm === 'AM' && endHour === 12) endHour = 0

  // Calculate duration in minutes
  const durationMins = (endHour * 60 + endMin) - (startHour * 60 + startMin)
  const hours = Math.floor(durationMins / 60)
  const minutes = durationMins % 60

  if (hours > 0) {
    return `${hours}h${minutes > 0 ? ` ${minutes}m` : ''}`
  }

  return `${minutes}m`
}

// Is the schedule happening now?
function isScheduleNow(schedule) {
  const now = new Date()
  const currentDay = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'][now.getDay()]

  if (schedule.day_of_week !== currentDay) return false

  // Parse schedule times
  const startMatch = schedule.start_time.match(/(\d+):(\d+)\s+(AM|PM)/)
  const endMatch = schedule.end_time.match(/(\d+):(\d+)\s+(AM|PM)/)

  if (!startMatch || !endMatch) return false

  let startHour = parseInt(startMatch[1])
  const startMin = parseInt(startMatch[2])
  const startAmPm = startMatch[3]

  let endHour = parseInt(endMatch[1])
  const endMin = parseInt(endMatch[2])
  const endAmPm = endMatch[3]

  // Convert to 24-hour format
  if (startAmPm === 'PM' && startHour !== 12) startHour += 12
  if (startAmPm === 'AM' && startHour === 12) startHour = 0

  if (endAmPm === 'PM' && endHour !== 12) endHour += 12
  if (endAmPm === 'AM' && endHour === 12) endHour = 0

  const startDate = new Date()
  startDate.setHours(startHour, startMin, 0)

  const endDate = new Date()
  endDate.setHours(endHour, endMin, 0)

  return now >= startDate && now <= endDate
}
</script>

<template>
  <AppLayout title="My Schedule">
    <div class="md:container mx-auto px-4 py-6">
      <div class="flex flex-col sm:flex-row justify-between mb-6 gap-4">
        <div>
          <h1 class="text-2xl font-bold">Academic Schedule</h1>
          <p class="text-gray-500">{{ currentSemester }} Semester, School Year {{ currentSchoolYear }}</p>
        </div>

        <div class="flex items-center gap-2">
          <Select v-model="selectedDay" class="w-36">
            <SelectTrigger>
              <SelectValue placeholder="Filter by day" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Days</SelectItem>
              <SelectItem v-for="day in daysOfWeek" :key="day" :value="day">
                {{ capitalizeFirstLetter(day) }} ({{ classCountByDay[day] }})
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <!-- Schedule info cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-lg">Total Classes</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold">{{ props.schedules.length }}</div>
            <p class="text-sm text-gray-500">Scheduled this semester</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-lg">Unique Subjects</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold">{{ uniqueSubjects.length }}</div>
            <p class="text-sm text-gray-500">Enrolled this semester</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-lg">Busiest Day</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold capitalize">{{ busiestDay.day }}</div>
            <p class="text-sm text-gray-500">{{ busiestDay.count }} classes scheduled</p>
          </CardContent>
        </Card>
      </div>

      <!-- Main schedule view -->
      <Tabs :default-value="viewMode" @update-value="viewMode = $event" class="w-full">
        <div class="flex justify-between items-center mb-6">
          <TabsList>
            <TabsTrigger value="timeline">Timeline View</TabsTrigger>
            <TabsTrigger value="list">List View</TabsTrigger>
            <TabsTrigger value="grid">Grid View</TabsTrigger>
          </TabsList>

          <Button
            v-if="viewMode === 'timeline' && showCurrentTimeIndicator"
            variant="outline"
            size="sm"
            @click="scrollToCurrentTime"
            class="text-xs"
          >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 mr-1"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            Scroll to now
          </Button>
        </div>

        <!-- Enhanced Timeline View -->
        <TabsContent value="timeline" class="mt-0">
          <Card>
            <CardHeader class="pb-2">
              <div class="flex justify-between items-start">
                <div>
                  <CardTitle>Schedule Timeline</CardTitle>
                  <CardDescription>
                    View your classes across time slots
                  </CardDescription>
                </div>

                <div v-if="showCurrentTimeIndicator" class="hidden sm:flex items-center text-sm">
                  <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse mr-2"></div>
                  <div>
                    <span class="font-medium">{{ currentTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</span>
                    <span class="ml-1 text-gray-500 capitalize">{{ currentDayName }}</span>
                  </div>
                </div>
              </div>

              <div v-if="currentDayHasClasses && showCurrentTimeIndicator" class="mt-2 px-3 py-1.5 bg-amber-50 dark:bg-amber-950 border border-amber-200 dark:border-amber-800 rounded-md text-amber-800 dark:text-amber-200 text-xs md:text-sm">
                You have {{ props.schedules.filter(s => s.day_of_week === currentDayName).length }} classes scheduled today.
                <span v-if="props.schedules.some(s => isScheduleNow(s))">
                  You have a class ongoing right now.
                </span>
              </div>
            </CardHeader>

            <CardContent>
              <div class="flex overflow-x-auto pb-4 timeline-container" ref="scrollContainer">
                <!-- Time column -->
                <div class="w-[70px] md:w-[90px] flex-shrink-0 bg-muted/50 backdrop-blur-sm sticky left-0 z-20 shadow-md border-r">
                  <div class="h-16 border-b flex items-center justify-center">
                    <span class="text-xs md:text-sm font-semibold text-gray-600 dark:text-gray-300">Time</span>
                  </div>
                  <div v-for="slot in timeSlots" :key="slot.time" class="h-16 flex items-center justify-center border-b border-gray-100 dark:border-gray-800">
                    <span class="text-xs md:text-sm text-gray-500 dark:text-gray-400 font-medium">{{ slot.time }}</span>
                  </div>
                </div>

                <!-- Day columns -->
                <div v-for="day in daysOfWeek" :key="day" class="min-w-[160px] md:min-w-[200px] flex-1 flex-shrink-0">
                  <div class="h-16 border-b pb-2 flex items-end justify-center sticky top-0 z-10 bg-background/90 backdrop-blur-sm">
                    <div class="flex flex-col items-center">
                      <span class="text-sm md:text-base font-medium capitalize">{{ day }}</span>
                      <Badge variant="outline" class="mt-1">{{ classCountByDay[day] }}</Badge>
                    </div>
                  </div>

                  <div class="relative" style="height: 840px;"> <!-- 15 hours * 56px height -->
                    <!-- Time slot grid lines -->
                    <div
                      v-for="(slot, index) in timeSlots"
                      :key="slot.time"
                      class="absolute w-full h-16 border-b border-gray-100 "
                      :style="{ top: `${index * 56}px` }"
                      :class="{'bg-background': index % 2 === 0}"
                    ></div>

                    <!-- Current time indicator -->
                    <div
                      v-if="day === currentDayName && showCurrentTimeIndicator"
                      class="absolute w-full h-0.5 bg-red-500 z-10"
                      :style="{ top: currentTimePosition }"
                    >
                      <div class="absolute -left-1 -top-1.5 w-4 h-4 rounded-full bg-red-500 flex items-center justify-center">
                        <div class="w-2 h-2 bg-muted rounded-full"></div>
                      </div>
                    </div>

                    <!-- Schedule blocks -->
                    <Tooltip v-for="schedule in filteredSchedules.filter(s => s.day_of_week === day)" :key="schedule.id">
                      <TooltipTrigger as-child>
                        <div
                          class="absolute rounded-md p-2 shadow-sm transition-all hover:shadow-md z-10 border border-transparent hover:border-primary"
                          :class="[
                            schedule.color,
                            isScheduleNow(schedule) ? 'ring-2 ring-primary animate-pulse' : '',
                          ]"
                          :style="{
                            ...getSchedulePosition(schedule),
                            ...getScheduleWidth(schedule, day)
                          }"
                        >
                          <div class="flex flex-col h-full overflow-hidden">
                            <div class="font-medium text-xs md:text-sm line-clamp-2">{{ schedule.subject }}</div>
                            <div class="text-xs opacity-90 mt-1 flex justify-between">
                              <div>{{ schedule.time }}</div>
                              <div class="hidden sm:block font-bold">{{ formatDuration(schedule.start_time, schedule.end_time) }}</div>
                            </div>
                            <div class="flex items-center mt-auto space-x-1 pt-1">
                              <div class="inline-flex text-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3 mr-1"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                                <span class="truncate">{{ schedule.room }}</span>
                              </div>
                            </div>
                          </div>
                        </div>
                      </TooltipTrigger>
                      <TooltipContent class="w-72 p-0 overflow-hidden">
                        <div class="p-3" :class="schedule.color">
                          <h4 class="font-bold">{{ schedule.subject }}</h4>
                          <div class="text-xs">{{ schedule.subject_code }}</div>
                        </div>
                        <div class="p-3 space-y-2">
                          <div class="grid grid-cols-2 gap-1 text-sm">
                            <div class="font-semibold">Time:</div>
                            <div>{{ schedule.time }}</div>

                            <div class="font-semibold">Room:</div>
                            <div>{{ schedule.room }}</div>

                            <div class="font-semibold">Teacher:</div>
                            <div>{{ schedule.teacher }}</div>

                            <div class="font-semibold">Section:</div>
                            <div>{{ schedule.section }}</div>

                            <div class="font-semibold">Duration:</div>
                            <div>{{ formatDuration(schedule.start_time, schedule.end_time) }}</div>
                          </div>
                        </div>
                      </TooltipContent>
                    </Tooltip>
                  </div>
                </div>
              </div>

              <!-- Mobile note about scrolling -->
              <div class="md:hidden text-xs text-gray-500 text-center mt-2">
                Swipe left/right to view all days
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- List View (Kept the original) -->
        <TabsContent value="list" class="mt-0">
          <Card>
            <CardHeader>
              <CardTitle>Schedule List</CardTitle>
              <CardDescription>
                Organized view of your weekly schedule
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-6">
                <div v-for="subject in schedulesBySubject" :key="subject.subject_code" class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-all">
                  <div :class="subject.color" class="p-4 flex justify-between items-center">
                    <div>
                      <h3 class="font-bold text-lg">{{ subject.subject }}</h3>
                      <div class="text-sm opacity-90">{{ subject.subject_code }}</div>
                    </div>
                    <Badge variant="outline" class="text-xs">
                      {{ subject.schedules.length }} session{{ subject.schedules.length !== 1 ? 's' : '' }}
                    </Badge>
                  </div>

                  <div class="p-4 space-y-3">
                    <div
                      v-for="schedule in subject.schedules.filter(s => selectedDay.value === 'all' || s.day_of_week === selectedDay.value)"
                      :key="schedule.id"
                      class="flex flex-col sm:flex-row sm:justify-between p-4 bg-muted rounded-lg hover:bg-gray-100 transition-colors border-l-4"
                      :style="`border-color: ${schedule.color ? schedule.color.split(' ')[0].replace('bg-', '--').replace('-100', '-500') : 'var(--primary)'}`"
                    >
                      <div class="flex items-start mb-2 sm:mb-0">
                        <div class="w-24 font-medium capitalize flex items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 mr-2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                          {{ schedule.day_of_week }}
                        </div>
                        <div>
                          <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 mr-2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            <span class="font-medium">{{ schedule.time }}</span>
                          </div>
                          <div class="text-sm text-gray-500 flex items-center mt-1">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 mr-2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                            Room: {{ schedule.room }}
                          </div>
                        </div>
                      </div>
                      <div class="flex flex-col">
                        <div class="text-sm flex items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 mr-2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                          <span>{{ schedule.teacher }}</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1 flex items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3 mr-1"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                          <span>{{ formatDuration(schedule.start_time, schedule.end_time) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Grid View (Kept the original) -->
        <TabsContent value="grid" class="mt-0">
          <Card>
            <CardHeader>
              <CardTitle>Weekly Calendar</CardTitle>
              <CardDescription>
                View your schedule in a weekly grid format
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div class="grid grid-cols-1 md:grid-cols-7 gap-4">
                <div v-for="day in daysOfWeek" :key="day" class="p-4 border rounded-lg">
                  <div class="flex justify-between items-center mb-3">
                    <h2 class="font-semibold text-lg capitalize">{{ day }}</h2>
                    <Badge variant="outline">{{ classCountByDay[day] }}</Badge>
                  </div>

                  <div v-if="filteredSchedules.some(s => s.day_of_week === day)" class="space-y-3">
                    <div
                      v-for="schedule in filteredSchedules.filter(s => s.day_of_week === day)"
                      :key="schedule.id"
                      class="p-3 rounded-md transition-all hover:shadow-md"
                      :class="schedule.color"
                    >
                      <div class="text-xs mb-1">{{ schedule.time }}</div>
                      <div class="font-bold text-sm">{{ schedule.subject }}</div>
                      <div class="text-xs mt-2 flex justify-between">
                        <span>Room: {{ schedule.room }}</span>
                        <span class="truncate ml-2">{{ schedule.teacher }}</span>
                      </div>
                    </div>
                  </div>

                  <div v-else class="h-24 flex items-center justify-center border border-dashed rounded-md text-gray-400 text-sm">
                    No classes
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
  height: 8px;
}

.timeline-container::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.timeline-container::-webkit-scrollbar-thumb {
  background: #ddd;
  border-radius: 4px;
}

.timeline-container::-webkit-scrollbar-thumb:hover {
  background: #ccc;
}

@media (max-width: 640px) {
  .timeline-container::-webkit-scrollbar {
    height: 4px;
  }
}

@keyframes pulse {
  0%, 100% {
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
