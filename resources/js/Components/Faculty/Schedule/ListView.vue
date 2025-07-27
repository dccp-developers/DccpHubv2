<template>
  <div class="list-view">
    <!-- Mobile View -->
    <div class="block md:hidden">
      <div class="space-y-3">
        <div v-for="day in daysOfWeek" :key="day" class="border border-border rounded-lg overflow-hidden">
          <div class="bg-muted p-3 border-b border-border">
            <div class="flex items-center justify-between">
              <h3 class="font-semibold text-foreground">{{ day }}</h3>
              <div class="flex items-center space-x-2">
                <span class="text-sm text-muted-foreground">{{ getDayDate(day) }}</span>
                <Badge v-if="getDaySchedules(day).length > 0" variant="secondary" class="text-xs">
                  {{ getDaySchedules(day).length }}
                </Badge>
              </div>
            </div>
          </div>
          <div class="divide-y divide-border">
            <div v-if="getDaySchedules(day).length === 0" class="p-4 text-center text-muted-foreground text-sm">
              No classes scheduled
            </div>
            <div
              v-for="schedule in getDaySchedules(day)"
              :key="schedule.id"
              class="p-3 hover:bg-accent transition-colors cursor-pointer"
              @click="$emit('scheduleClick', schedule)"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center space-x-2 mb-1">
                    <div :class="`w-3 h-3 rounded-full bg-${schedule.color}`"></div>
                    <h4 class="font-medium text-sm truncate">{{ schedule.subject_code }}</h4>
                    <Badge :variant="getStatusVariant(schedule.status)" class="text-xs">
                      {{ getStatusLabel(schedule.status) }}
                    </Badge>
                  </div>
                  <p class="text-xs text-muted-foreground mb-2 line-clamp-1">
                    {{ schedule.subject_title }}
                  </p>
                  <div class="flex items-center space-x-4 text-xs text-muted-foreground">
                    <div class="flex items-center space-x-1">
                      <ClockIcon class="w-3 h-3" />
                      <span>{{ schedule.start_time }} - {{ schedule.end_time }}</span>
                    </div>
                    <div class="flex items-center space-x-1">
                      <MapPinIcon class="w-3 h-3" />
                      <span>{{ schedule.room }}</span>
                    </div>
                    <div class="flex items-center space-x-1">
                      <AcademicCapIcon class="w-3 h-3" />
                      <span>Sec {{ schedule.section }}</span>
                    </div>
                  </div>
                </div>
                <div class="text-right ml-3">
                  <div class="text-xs text-muted-foreground">{{ schedule.duration }}</div>
                  <div v-if="schedule.student_count" class="text-xs text-muted-foreground mt-1">
                    {{ schedule.student_count }} students
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Desktop View -->
    <div class="hidden md:block">
      <div class="space-y-4">
        <div v-for="day in daysOfWeek" :key="day" class="border border-border rounded-lg overflow-hidden">
          <!-- Day Header -->
          <div class="bg-muted p-4 border-b border-border">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <h3 class="text-lg font-semibold text-foreground">{{ day }}</h3>
                <span class="text-sm text-muted-foreground">{{ getFullDayDate(day) }}</span>
                <Badge v-if="isToday(day)" variant="default" class="text-xs">Today</Badge>
              </div>
              <div class="flex items-center space-x-2">
                <Badge variant="secondary" class="text-xs">
                  {{ getDaySchedules(day).length }} {{ getDaySchedules(day).length === 1 ? 'class' : 'classes' }}
                </Badge>
                <span class="text-sm text-muted-foreground">
                  {{ getTotalHoursForDay(day) }}h total
                </span>
              </div>
            </div>
          </div>

          <!-- Schedule List -->
          <div class="divide-y divide-border">
            <div v-if="getDaySchedules(day).length === 0" class="p-8 text-center">
              <CalendarIcon class="mx-auto h-12 w-12 text-muted-foreground mb-3" />
              <h4 class="text-sm font-medium text-foreground mb-1">No classes scheduled</h4>
              <p class="text-xs text-muted-foreground">Enjoy your free day!</p>
            </div>
            
            <div
              v-for="(schedule, index) in getDaySchedules(day)"
              :key="schedule.id"
              class="p-4 hover:bg-accent transition-colors cursor-pointer group"
              @click="$emit('scheduleClick', schedule)"
            >
              <div class="flex items-center justify-between">
                <!-- Left Section: Time and Status -->
                <div class="flex items-center space-x-4">
                  <div class="text-center min-w-[80px]">
                    <div class="text-sm font-medium text-foreground">{{ schedule.start_time }}</div>
                    <div class="text-xs text-muted-foreground">{{ schedule.end_time }}</div>
                    <div class="text-xs text-muted-foreground mt-1">{{ schedule.duration }}</div>
                  </div>
                  
                  <div class="flex items-center space-x-2">
                    <div :class="`w-4 h-4 rounded-full bg-${schedule.color}`"></div>
                    <Badge :variant="getStatusVariant(schedule.status)" class="text-xs">
                      {{ getStatusLabel(schedule.status) }}
                    </Badge>
                  </div>
                </div>

                <!-- Center Section: Class Details -->
                <div class="flex-1 mx-6">
                  <div class="flex items-center justify-between mb-2">
                    <h4 class="text-lg font-semibold text-foreground">{{ schedule.subject_code }}</h4>
                    <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                      <MapPinIcon class="w-4 h-4" />
                      <span>Room {{ schedule.room }}</span>
                    </div>
                  </div>
                  
                  <p class="text-sm text-muted-foreground mb-2 line-clamp-1">
                    {{ schedule.subject_title }}
                  </p>
                  
                  <div class="flex items-center space-x-6 text-sm text-muted-foreground">
                    <div class="flex items-center space-x-1">
                      <AcademicCapIcon class="w-4 h-4" />
                      <span>Section {{ schedule.section }}</span>
                    </div>
                    <div v-if="schedule.student_count" class="flex items-center space-x-1">
                      <UsersIcon class="w-4 h-4" />
                      <span>{{ schedule.student_count }} students</span>
                    </div>
                    <div v-if="schedule.class_id" class="flex items-center space-x-1">
                      <HashtagIcon class="w-4 h-4" />
                      <span>ID: {{ schedule.class_id }}</span>
                    </div>
                  </div>
                </div>

                <!-- Right Section: Actions -->
                <div class="flex items-center space-x-2">
                  <Button variant="ghost" size="sm" class="opacity-0 group-hover:opacity-100 transition-opacity">
                    <EyeIcon class="w-4 h-4" />
                  </Button>
                  <ChevronRightIcon class="w-5 h-5 text-muted-foreground" />
                </div>
              </div>

              <!-- Progress Bar for Ongoing Classes -->
              <div v-if="schedule.status === 'ongoing'" class="mt-3 pt-3 border-t border-border">
                <div class="flex items-center justify-between text-xs text-muted-foreground mb-2">
                  <span>Class Progress</span>
                  <span>{{ getClassProgress(schedule) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                  <div 
                    class="bg-green-600 h-2 rounded-full transition-all duration-300"
                    :style="{ width: getClassProgress(schedule) + '%' }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Badge } from '@/Components/shadcn/ui/badge'
import { Button } from '@/Components/shadcn/ui/button'
import {
  CalendarIcon,
  ClockIcon,
  MapPinIcon,
  AcademicCapIcon,
  UsersIcon,
  EyeIcon,
  ChevronRightIcon,
  HashtagIcon
} from '@heroicons/vue/24/outline'

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

const getCurrentWeekDates = computed(() => {
  const currentDate = new Date(props.currentDate)
  const startOfWeek = new Date(currentDate)
  const day = startOfWeek.getDay()
  const diff = startOfWeek.getDate() - day + (day === 0 ? -6 : 1)
  startOfWeek.setDate(diff)

  const weekDates = {}
  daysOfWeek.forEach((dayName, index) => {
    const date = new Date(startOfWeek)
    date.setDate(startOfWeek.getDate() + index)
    weekDates[dayName] = date
  })

  return weekDates
})

const getDayDate = (day) => {
  const date = getCurrentWeekDates.value[day]
  return date ? date.getDate() : ''
}

const getFullDayDate = (day) => {
  const date = getCurrentWeekDates.value[day]
  return date ? date.toLocaleDateString('en-US', { 
    month: 'long', 
    day: 'numeric',
    year: 'numeric'
  }) : ''
}

const isToday = (day) => {
  const today = new Date()
  const dayDate = getCurrentWeekDates.value[day]
  return dayDate && 
         dayDate.getDate() === today.getDate() &&
         dayDate.getMonth() === today.getMonth() &&
         dayDate.getFullYear() === today.getFullYear()
}

const getDaySchedules = (day) => {
  return props.weeklySchedule[day] || []
}

const getTotalHoursForDay = (day) => {
  const schedules = getDaySchedules(day)
  const totalMinutes = schedules.reduce((total, schedule) => {
    const duration = schedule.duration || '1h'
    const hours = parseFloat(duration.replace(/[^\d.]/g, ''))
    return total + (hours * 60)
  }, 0)
  return Math.round(totalMinutes / 60 * 10) / 10
}

const getStatusVariant = (status) => {
  switch (status) {
    case 'ongoing': return 'default'
    case 'upcoming': return 'secondary'
    case 'completed': return 'outline'
    default: return 'secondary'
  }
}

const getStatusLabel = (status) => {
  switch (status) {
    case 'ongoing': return 'In Progress'
    case 'upcoming': return 'Upcoming'
    case 'completed': return 'Completed'
    default: return 'Scheduled'
  }
}

const getClassProgress = (schedule) => {
  if (schedule.status !== 'ongoing') return 0
  
  const now = new Date()
  const currentMinutes = now.getHours() * 60 + now.getMinutes()
  const startMinutes = timeToMinutes(schedule.raw_start_time || schedule.start_time)
  const endMinutes = timeToMinutes(schedule.raw_end_time || schedule.end_time)
  
  const totalDuration = endMinutes - startMinutes
  const elapsed = currentMinutes - startMinutes
  
  return Math.min(Math.max(Math.round((elapsed / totalDuration) * 100), 0), 100)
}

const timeToMinutes = (timeString) => {
  if (!timeString) return 0
  const [hours, minutes] = timeString.split(':').map(Number)
  return hours * 60 + minutes
}
</script>

<style scoped>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
