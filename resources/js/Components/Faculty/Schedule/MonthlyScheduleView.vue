<template>
  <div class="monthly-schedule-view">
    <!-- Month Navigation -->
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-foreground">{{ monthYear }}</h3>
      <div class="flex items-center space-x-2">
        <Button variant="outline" size="sm" @click="navigateMonth(-1)">
          <ChevronLeftIcon class="w-4 h-4" />
        </Button>
        <Button variant="outline" size="sm" @click="goToCurrentMonth">
          Today
        </Button>
        <Button variant="outline" size="sm" @click="navigateMonth(1)">
          <ChevronRightIcon class="w-4 h-4" />
        </Button>
      </div>
    </div>

    <!-- Calendar Grid -->
    <div class="grid grid-cols-7 gap-1 mb-4">
      <!-- Day Headers -->
      <div
        v-for="day in dayHeaders"
        :key="day"
        class="p-2 text-center text-sm font-medium text-muted-foreground border-b border-border"
      >
        {{ day }}
      </div>

      <!-- Calendar Days -->
      <div
        v-for="date in calendarDays"
        :key="date.dateString"
        class="min-h-[120px] p-1 border border-border cursor-pointer hover:bg-accent transition-colors"
        :class="getDayClasses(date)"
        @click="selectDate(date.dateString)"
      >
        <!-- Date Number -->
        <div class="flex items-center justify-between mb-1">
          <span
            class="text-sm font-medium"
            :class="date.isCurrentMonth ? 'text-foreground' : 'text-muted-foreground'"
          >
            {{ date.day }}
          </span>
          <div v-if="date.isToday" class="w-2 h-2 bg-primary rounded-full"></div>
        </div>

        <!-- Schedule Items -->
        <div class="space-y-1">
          <div
            v-for="schedule in getSchedulesForDate(date.dateString)"
            :key="schedule.id"
            class="p-1 rounded text-xs cursor-pointer hover:shadow-sm transition-all"
            :class="getScheduleClasses(schedule)"
            @click.stop="$emit('scheduleClick', schedule)"
          >
            <div class="font-medium truncate">{{ schedule.subject_code }}</div>
            <div class="text-xs opacity-75 truncate">{{ schedule.start_time }}</div>
          </div>
          
          <!-- Show more indicator -->
          <div
            v-if="getSchedulesForDate(date.dateString).length > 3"
            class="text-xs text-muted-foreground text-center py-1"
          >
            +{{ getSchedulesForDate(date.dateString).length - 3 }} more
          </div>
        </div>
      </div>
    </div>

    <!-- Month Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
      <Card>
        <CardContent class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-2xl font-bold text-foreground">{{ monthStats.totalClasses }}</p>
              <p class="text-sm text-muted-foreground">Total Classes</p>
            </div>
            <AcademicCapIcon class="w-8 h-8 text-blue-600" />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-2xl font-bold text-foreground">{{ monthStats.teachingDays }}</p>
              <p class="text-sm text-muted-foreground">Teaching Days</p>
            </div>
            <CalendarIcon class="w-8 h-8 text-green-600" />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-2xl font-bold text-foreground">{{ monthStats.totalHours }}h</p>
              <p class="text-sm text-muted-foreground">Teaching Hours</p>
            </div>
            <ClockIcon class="w-8 h-8 text-purple-600" />
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Upcoming Classes -->
    <Card class="mt-6">
      <CardHeader>
        <CardTitle class="text-lg">Upcoming Classes This Month</CardTitle>
      </CardHeader>
      <CardContent>
        <div v-if="upcomingClasses.length === 0" class="text-center py-8">
          <CalendarIcon class="mx-auto h-12 w-12 text-muted-foreground" />
          <h3 class="mt-2 text-sm font-medium text-foreground">No upcoming classes</h3>
          <p class="mt-1 text-sm text-muted-foreground">All classes for this month are completed.</p>
        </div>
        <div v-else class="space-y-3">
          <div
            v-for="schedule in upcomingClasses.slice(0, 5)"
            :key="schedule.id"
            class="flex items-center justify-between p-3 rounded-lg border border-border hover:bg-accent transition-colors cursor-pointer"
            @click="$emit('scheduleClick', schedule)"
          >
            <div class="flex items-center space-x-3">
              <div :class="`w-3 h-3 rounded-full bg-${schedule.color}`"></div>
              <div>
                <h4 class="font-medium text-sm">{{ schedule.subject_code }}</h4>
                <p class="text-xs text-muted-foreground">Section {{ schedule.section }} • Room {{ schedule.room }}</p>
              </div>
            </div>
            <div class="text-right">
              <p class="text-sm font-medium">{{ formatUpcomingDate(schedule.date) }}</p>
              <p class="text-xs text-muted-foreground">{{ schedule.start_time }}</p>
            </div>
          </div>
          
          <div v-if="upcomingClasses.length > 5" class="text-center pt-3">
            <Button variant="outline" size="sm">
              View All {{ upcomingClasses.length }} Classes
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Button } from '@/Components/shadcn/ui/button'
import {
  CalendarIcon,
  AcademicCapIcon,
  ClockIcon,
  ChevronLeftIcon,
  ChevronRightIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  monthlySchedule: {
    type: Object,
    default: () => ({})
  },
  currentDate: {
    type: String,
    default: () => new Date().toISOString().split('T')[0]
  }
})

const emit = defineEmits(['scheduleClick', 'dateClick'])

const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const currentMonth = computed(() => new Date(props.currentDate))

const monthYear = computed(() => {
  return currentMonth.value.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long'
  })
})

const calendarDays = computed(() => {
  const year = currentMonth.value.getFullYear()
  const month = currentMonth.value.getMonth()
  
  // Get first day of month and calculate starting date
  const firstDay = new Date(year, month, 1)
  const startDate = new Date(firstDay)
  startDate.setDate(startDate.getDate() - firstDay.getDay())
  
  const days = []
  const today = new Date()
  
  // Generate 42 days (6 weeks)
  for (let i = 0; i < 42; i++) {
    const date = new Date(startDate)
    date.setDate(startDate.getDate() + i)
    
    days.push({
      day: date.getDate(),
      dateString: date.toISOString().split('T')[0],
      isCurrentMonth: date.getMonth() === month,
      isToday: date.toDateString() === today.toDateString(),
      date: date
    })
  }
  
  return days
})

const monthStats = computed(() => {
  const schedules = Object.values(props.monthlySchedule).flat()
  const uniqueDays = new Set(Object.keys(props.monthlySchedule)).size
  const totalHours = schedules.reduce((sum, schedule) => {
    return sum + (schedule.duration ? parseFloat(schedule.duration.replace(/[^\d.]/g, '')) : 1)
  }, 0)
  
  return {
    totalClasses: schedules.length,
    teachingDays: uniqueDays,
    totalHours: Math.round(totalHours)
  }
})

const upcomingClasses = computed(() => {
  const today = new Date()
  const schedules = []
  
  Object.entries(props.monthlySchedule).forEach(([date, daySchedules]) => {
    const scheduleDate = new Date(date)
    if (scheduleDate >= today) {
      daySchedules.forEach(schedule => {
        schedules.push({
          ...schedule,
          date: date
        })
      })
    }
  })
  
  return schedules.sort((a, b) => {
    const dateCompare = a.date.localeCompare(b.date)
    if (dateCompare !== 0) return dateCompare
    return a.raw_start_time.localeCompare(b.raw_start_time)
  })
})

const getDayClasses = (date) => {
  const classes = ['relative']
  
  if (date.isToday) {
    classes.push('bg-primary/10 border-primary')
  }
  
  if (!date.isCurrentMonth) {
    classes.push('opacity-50')
  }
  
  if (getSchedulesForDate(date.dateString).length > 0) {
    classes.push('bg-accent/50')
  }
  
  return classes.join(' ')
}

const getSchedulesForDate = (dateString) => {
  return props.monthlySchedule[dateString] || []
}

const getScheduleClasses = (schedule) => {
  const statusClasses = {
    'ongoing': 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200',
    'upcoming': 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200',
    'completed': 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-200',
    'scheduled': 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-200'
  }
  
  return statusClasses[schedule.status] || statusClasses.scheduled
}

const formatUpcomingDate = (dateString) => {
  const date = new Date(dateString)
  const today = new Date()
  const tomorrow = new Date(today)
  tomorrow.setDate(today.getDate() + 1)
  
  if (date.toDateString() === today.toDateString()) {
    return 'Today'
  } else if (date.toDateString() === tomorrow.toDateString()) {
    return 'Tomorrow'
  } else {
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
  }
}

const navigateMonth = (direction) => {
  const newDate = new Date(currentMonth.value)
  newDate.setMonth(newDate.getMonth() + direction)
  emit('dateClick', newDate.toISOString().split('T')[0])
}

const goToCurrentMonth = () => {
  emit('dateClick', new Date().toISOString().split('T')[0])
}

const selectDate = (dateString) => {
  emit('dateClick', dateString)
}
</script>

<style scoped>
.monthly-schedule-view {
  @apply w-full;
}

/* Ensure calendar grid is responsive */
@media (max-width: 768px) {
  .grid-cols-7 > div {
    min-height: 80px;
  }
}
</style>
