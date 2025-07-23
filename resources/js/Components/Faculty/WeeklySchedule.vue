<template>
  <div class="weekly-schedule">
    <!-- Schedule Header -->
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-lg font-semibold text-foreground">Weekly Schedule</h3>
        <p class="text-sm text-muted-foreground">{{ totalHours }}h total • {{ daysWithClasses }} days</p>
      </div>
      <div class="flex items-center space-x-2">
        <Badge variant="outline" class="text-xs">
          {{ currentSemester }} {{ schoolYear }}
        </Badge>
        <Button size="sm" variant="outline" @click="$emit('viewFullCalendar')">
          <CalendarIcon class="w-4 h-4 mr-2" />
          Full Calendar
        </Button>
      </div>
    </div>

    <!-- Time Grid Schedule -->
    <div class="border border-border rounded-lg overflow-hidden">
      <!-- Header Row -->
      <div class="grid grid-cols-8 bg-muted/50">
        <div class="p-3 text-sm font-medium text-muted-foreground border-r border-border">Time</div>
        <div
          v-for="day in daysOfWeek"
          :key="day"
          class="p-3 text-sm font-medium text-center border-r border-border last:border-r-0"
          :class="day === getCurrentDay() ? 'bg-primary/10 text-primary' : 'text-foreground'"
        >
          {{ day.substring(0, 3) }}
          <div v-if="day === getCurrentDay()" class="text-xs text-primary mt-1">Today</div>
        </div>
      </div>

      <!-- Time Slots -->
      <div class="max-h-96 overflow-y-auto">
        <div
          v-for="timeSlot in timeSlots"
          :key="timeSlot"
          class="grid grid-cols-8 border-b border-border last:border-b-0"
        >
          <!-- Time Column -->
          <div class="p-2 text-xs text-muted-foreground border-r border-border bg-muted/20">
            {{ formatTimeSlot(timeSlot) }}
          </div>
          
          <!-- Day Columns -->
          <div
            v-for="day in daysOfWeek"
            :key="`${day}-${timeSlot}`"
            class="p-1 border-r border-border last:border-r-0 min-h-[60px] relative"
            :class="day === getCurrentDay() ? 'bg-primary/5' : ''"
          >
            <div
              v-for="schedule in getScheduleForTimeSlot(day, timeSlot)"
              :key="schedule.id"
              class="absolute inset-1 p-1 rounded text-xs cursor-pointer transition-all hover:shadow-md"
              :class="getScheduleClasses(schedule)"
              :style="getScheduleStyle(schedule, timeSlot)"
              @click="$emit('scheduleClick', schedule)"
            >
              <div class="font-medium truncate">{{ schedule.subject_code }}</div>
              <div class="text-xs opacity-75 truncate">{{ schedule.room }}</div>
              <div class="text-xs opacity-75">Sec {{ schedule.section }}</div>
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
import { Badge } from '@/Components/ui/badge.js'
import { Button } from '@/Components/ui/button.js'
import { CalendarIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  weeklySchedule: {
    type: Object,
    default: () => ({})
  },
  scheduleOverview: {
    type: Object,
    default: () => ({})
  },
  currentSemester: {
    type: String,
    default: '1st'
  },
  schoolYear: {
    type: String,
    default: '2024-2025'
  }
})

const emit = defineEmits(['scheduleClick', 'viewFullCalendar'])

// Constants
const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']
const timeSlots = [
  '07:00', '08:00', '09:00', '10:00', '11:00', '12:00',
  '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'
]

// Computed properties
const totalHours = computed(() => props.scheduleOverview.total_weekly_hours || 0)
const daysWithClasses = computed(() => props.scheduleOverview.days_with_classes || 0)
const averageDailyHours = computed(() => props.scheduleOverview.average_daily_hours || 0)
const totalClasses = computed(() => {
  let count = 0
  Object.values(props.weeklySchedule).forEach(daySchedules => {
    count += daySchedules.length
  })
  return count
})

// Methods
const getCurrentDay = () => {
  return new Date().toLocaleDateString('en-US', { weekday: 'long' })
}

const formatTimeSlot = (timeSlot) => {
  const [hour] = timeSlot.split(':')
  const hourNum = parseInt(hour)
  if (hourNum === 12) return '12:00 PM'
  if (hourNum > 12) return `${hourNum - 12}:00 PM`
  return `${hourNum}:00 AM`
}

const getScheduleForTimeSlot = (day, timeSlot) => {
  const daySchedules = props.weeklySchedule[day] || []
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

const getScheduleClasses = (schedule) => {
  const baseClasses = 'border-l-4'
  const colorClass = `border-l-${schedule.color || 'blue-500'} bg-${schedule.color || 'blue-500'}/10`
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
.weekly-schedule {
  @apply w-full;
}
</style>
