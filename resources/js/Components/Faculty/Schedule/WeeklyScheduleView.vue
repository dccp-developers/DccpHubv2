<template>
  <div class="weekly-schedule-view">
    <!-- Mobile View -->
    <div class="block lg:hidden">
      <div class="space-y-4">
        <div v-for="day in daysOfWeek" :key="day" class="border border-border rounded-lg overflow-hidden">
          <div class="bg-muted p-3 border-b border-border">
            <div class="flex items-center justify-between">
              <h3 class="font-semibold text-foreground">{{ day }}</h3>
              <div class="flex items-center space-x-2">
                <span class="text-sm text-muted-foreground">{{ getDayDate(day) }}</span>
                <span v-if="getDaySchedules(day).length > 0" class="bg-primary text-primary-foreground text-xs px-2 py-1 rounded-full">
                  {{ getDaySchedules(day).length }}
                </span>
              </div>
            </div>
          </div>
          <div class="p-3">
            <div v-if="getDaySchedules(day).length === 0" class="text-center py-6 text-muted-foreground text-sm">
              <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-muted flex items-center justify-center">
                <CalendarIcon class="w-6 h-6" />
              </div>
              No classes scheduled
            </div>
            <div v-else class="space-y-3">
              <div
                v-for="schedule in getDaySchedules(day)"
                :key="schedule.id"
                class="p-3 rounded-lg border border-border cursor-pointer transition-all hover:shadow-md hover:border-primary/50"
                :class="getScheduleClasses(schedule)"
                @click="$emit('scheduleClick', schedule)"
              >
                <div class="flex items-start justify-between mb-2">
                  <div class="flex-1">
                    <h4 class="font-semibold text-sm text-foreground">{{ schedule.subject_code }}</h4>
                    <p class="text-xs text-muted-foreground mt-1">{{ schedule.subject_title || 'Subject Title' }}</p>
                  </div>
                  <div class="text-right">
                    <span class="text-xs font-medium text-foreground">{{ schedule.start_time }}</span>
                    <span class="text-xs text-muted-foreground"> - {{ schedule.end_time }}</span>
                  </div>
                </div>
                <div class="flex items-center justify-between text-xs text-muted-foreground">
                  <span>Room {{ schedule.room }} • Section {{ schedule.section }}</span>
                  <span>{{ schedule.duration }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Desktop Timeline View -->
    <div class="hidden lg:block">
      <div class="overflow-x-auto">
        <div class="min-w-[900px] border border-border rounded-lg overflow-hidden">
          <!-- Header Row -->
          <div class="grid grid-cols-8 bg-muted/50">
            <!-- Time Column Header -->
            <div class="border-r border-border p-3 text-center">
              <span class="text-sm font-medium text-muted-foreground">Time</span>
            </div>

            <!-- Day Headers -->
            <div
              v-for="day in daysOfWeek"
              :key="day"
              class="border-r border-border last:border-r-0 p-3 text-center"
              :class="isToday(day) ? 'bg-primary/10 text-primary' : 'text-foreground'"
            >
              <div class="text-sm font-medium">{{ day.substring(0, 3) }}</div>
              <div class="text-xs text-muted-foreground mt-1">{{ getDayDate(day) }}</div>
              <div v-if="isToday(day)" class="text-xs text-primary mt-1">Today</div>
            </div>
          </div>

          <!-- Time Grid Body -->
          <div class="max-h-96 overflow-y-auto">
            <div
              v-for="timeSlot in timeSlots"
              :key="timeSlot"
              class="grid grid-cols-8 border-b border-border last:border-b-0"
            >
              <!-- Time Label Column -->
              <div class="border-r border-border p-2 text-xs text-muted-foreground bg-muted/20">
                {{ formatTimeSlot(timeSlot) }}
              </div>

              <!-- Day Columns -->
              <div
                v-for="day in daysOfWeek"
                :key="`${day}-${timeSlot}`"
                class="border-r border-border last:border-r-0 min-h-[60px] relative p-1"
                :class="isToday(day) ? 'bg-primary/5' : ''"
              >
                <!-- Schedule Items for this time slot and day -->
                <div
                  v-for="schedule in getScheduleForTimeSlot(day, timeSlot)"
                  :key="schedule.id"
                  class="absolute inset-1 p-1 rounded text-xs cursor-pointer transition-all hover:shadow-md border-l-4"
                  :class="getScheduleClasses(schedule)"
                  :style="getScheduleStyle(schedule, timeSlot)"
                  @click="$emit('scheduleClick', schedule)"
                >
                  <div class="font-medium truncate">{{ schedule.subject_code }}</div>
                  <div class="text-xs opacity-75 truncate">Room {{ schedule.room }}</div>
                  <div class="text-xs opacity-75">Sec {{ schedule.section }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Schedule Summary -->
    <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="text-center p-3 bg-muted/50 rounded-lg">
        <div class="text-lg font-semibold text-foreground">{{ daysWithClasses }}</div>
        <div class="text-xs text-muted-foreground">Teaching Days</div>
      </div>
      <div class="text-center p-3 bg-muted/50 rounded-lg">
        <div class="text-lg font-semibold text-foreground">{{ totalHours }}h</div>
        <div class="text-xs text-muted-foreground">Weekly Hours</div>
      </div>
      <div class="text-center p-3 bg-muted/50 rounded-lg">
        <div class="text-lg font-semibold text-foreground">{{ averageDailyHours }}h</div>
        <div class="text-xs text-muted-foreground">Avg. Daily</div>
      </div>
      <div class="text-center p-3 bg-muted/50 rounded-lg">
        <div class="text-lg font-semibold text-foreground">{{ totalClasses }}</div>
        <div class="text-xs text-muted-foreground">Total Classes</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { CalendarIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  weeklySchedule: {
    type: Object,
    default: () => ({})
  },
  currentDate: {
    type: String,
    default: () => new Date().toISOString().split('T')[0]
  }
})

defineEmits(['scheduleClick'])

const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']

// Time slots from 7 AM to 7 PM (matching dashboard component)
const timeSlots = [
  '07:00', '08:00', '09:00', '10:00', '11:00', '12:00',
  '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'
]

const getCurrentWeekDates = computed(() => {
  const currentDate = new Date(props.currentDate)
  const startOfWeek = new Date(currentDate)
  const day = startOfWeek.getDay()
  const diff = startOfWeek.getDate() - day + (day === 0 ? -6 : 1) // Adjust for Monday start
  startOfWeek.setDate(diff)

  const weekDates = {}
  daysOfWeek.forEach((dayName, index) => {
    const date = new Date(startOfWeek)
    date.setDate(startOfWeek.getDate() + index)
    weekDates[dayName] = date
  })

  return weekDates
})

// Computed properties for schedule summary
const totalClasses = computed(() => {
  let count = 0
  Object.values(props.weeklySchedule).forEach(daySchedules => {
    count += daySchedules.length
  })
  return count
})

const daysWithClasses = computed(() => {
  let count = 0
  Object.values(props.weeklySchedule).forEach(daySchedules => {
    if (daySchedules.length > 0) count++
  })
  return count
})

const totalHours = computed(() => {
  let hours = 0
  Object.values(props.weeklySchedule).forEach(daySchedules => {
    daySchedules.forEach(schedule => {
      const duration = schedule.duration || '1h'
      const hourValue = parseFloat(duration.replace(/[^\d.]/g, ''))
      hours += hourValue
    })
  })
  return Math.round(hours * 10) / 10
})

const averageDailyHours = computed(() => {
  return daysWithClasses.value > 0 ? Math.round((totalHours.value / daysWithClasses.value) * 10) / 10 : 0
})

const getDayDate = (day) => {
  const date = getCurrentWeekDates.value[day]
  return date ? date.getDate() : ''
}

const isToday = (day) => {
  const today = new Date()
  const dayDate = getCurrentWeekDates.value[day]
  return dayDate && 
         dayDate.getDate() === today.getDate() &&
         dayDate.getMonth() === today.getMonth() &&
         dayDate.getFullYear() === today.getFullYear()
}

const formatTimeSlot = (timeSlot) => {
  const [hour] = timeSlot.split(':')
  const hourNum = parseInt(hour)
  if (hourNum === 12) return '12:00 PM'
  if (hourNum > 12) return `${hourNum - 12}:00 PM`
  return `${hourNum}:00 AM`
}

const getDaySchedules = (day) => {
  return props.weeklySchedule[day] || []
}

const getScheduleForTimeSlot = (day, timeSlot) => {
  const daySchedules = getDaySchedules(day)
  return daySchedules.filter(schedule => {
    // Parse the start time from formatted time (avoid timezone issues with raw_start_time)
    const timeStr = schedule.start_time
    let startHour

    if (timeStr.includes('PM') && !timeStr.startsWith('12:')) {
      startHour = parseInt(timeStr.split(':')[0]) + 12
    } else if (timeStr.includes('AM') && timeStr.startsWith('12:')) {
      startHour = 0
    } else {
      startHour = parseInt(timeStr.split(':')[0])
    }

    const slotHour = parseInt(timeSlot.split(':')[0])
    return startHour === slotHour
  })
}

const getScheduleColor = (schedule) => {
  const colors = {
    'CS': '#3b82f6', // blue
    'IT': '#10b981', // green
    'MATH': '#8b5cf6', // purple
    'ENG': '#ef4444', // red
    'SCI': '#f59e0b', // amber
    'default': '#6b7280' // gray
  }

  const subjectCode = schedule.subject_code || ''
  const key = Object.keys(colors).find(k => subjectCode.includes(k)) || 'default'
  return colors[key]
}

const getScheduleClasses = (schedule) => {
  const baseClasses = 'border-l-4'

  // Map colors to Tailwind classes
  const colorMap = {
    '#3b82f6': 'border-l-blue-500 bg-blue-500/10',
    '#10b981': 'border-l-green-500 bg-green-500/10',
    '#8b5cf6': 'border-l-purple-500 bg-purple-500/10',
    '#ef4444': 'border-l-red-500 bg-red-500/10',
    '#f59e0b': 'border-l-amber-500 bg-amber-500/10',
    '#6b7280': 'border-l-gray-500 bg-gray-500/10'
  }

  const color = getScheduleColor(schedule)
  const colorClass = colorMap[color] || 'border-l-blue-500 bg-blue-500/10'

  return `${baseClasses} ${colorClass}`
}

const getScheduleStyle = (schedule, timeSlot) => {
  // Calculate height based on duration using formatted times (avoid timezone issues)
  const startTime = schedule.start_time
  const endTime = schedule.end_time

  // Convert formatted times to 24-hour format for calculation
  const parseTime = (timeStr) => {
    const [time, period] = timeStr.split(' ')
    const [hours, minutes] = time.split(':')
    let hour = parseInt(hours)

    if (period === 'PM' && hour !== 12) hour += 12
    if (period === 'AM' && hour === 12) hour = 0

    return hour + (parseInt(minutes || 0) / 60)
  }

  const startHours = parseTime(startTime)
  const endHours = parseTime(endTime)
  const durationHours = endHours - startHours

  return {
    height: `${Math.max(durationHours * 60 - 8, 50)}px`,
    zIndex: 10
  }
}
</script>

<style scoped>
.weekly-schedule-view {
  @apply w-full;
}

/* Ensure schedule items don't overflow */
.weekly-schedule-view .absolute {
  overflow: hidden;
}

/* Smooth transitions for hover effects */
.weekly-schedule-view .cursor-pointer {
  transition: all 0.2s ease-in-out;
}

.weekly-schedule-view .cursor-pointer:hover {
  transform: translateY(-1px);
}
</style>
