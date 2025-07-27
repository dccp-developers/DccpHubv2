<template>
  <div class="daily-schedule-view">
    <!-- Date Header -->
    <div class="mb-6 text-center">
      <h3 class="text-lg font-semibold text-foreground">{{ formatDate(currentDate) }}</h3>
      <p class="text-sm text-muted-foreground">{{ getDayOfWeek(currentDate) }}</p>
    </div>

    <!-- Schedule Content -->
    <div v-if="dailySchedule.length === 0" class="text-center py-12">
      <CalendarIcon class="mx-auto h-16 w-16 text-muted-foreground" />
      <h3 class="mt-4 text-lg font-medium text-foreground">No classes scheduled</h3>
      <p class="mt-2 text-sm text-muted-foreground">
        You have no classes scheduled for this day. Enjoy your free time!
      </p>
    </div>

    <div v-else class="space-y-4">
      <!-- Timeline View -->
      <div class="relative">
        <!-- Current Time Indicator -->
        <div
          v-if="isToday && currentTimePosition"
          class="absolute left-0 right-0 z-10 flex items-center"
          :style="{ top: currentTimePosition + 'px' }"
        >
          <div class="w-3 h-3 bg-red-500 rounded-full"></div>
          <div class="flex-1 h-0.5 bg-red-500"></div>
          <span class="ml-2 text-xs text-red-500 font-medium">{{ getCurrentTime() }}</span>
        </div>

        <!-- Schedule Items -->
        <div class="space-y-2">
          <div
            v-for="(schedule, index) in sortedSchedule"
            :key="schedule.id"
            class="relative flex items-start space-x-4 p-4 rounded-lg border border-border hover:shadow-md transition-all cursor-pointer"
            :class="getScheduleClasses(schedule)"
            @click="$emit('scheduleClick', schedule)"
          >
            <!-- Time Column -->
            <div class="flex-shrink-0 w-20 text-center">
              <div class="text-sm font-medium text-foreground">{{ schedule.start_time }}</div>
              <div class="text-xs text-muted-foreground">{{ schedule.end_time }}</div>
              <div class="text-xs text-muted-foreground mt-1">{{ schedule.duration }}</div>
            </div>

            <!-- Schedule Details -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between mb-2">
                <h4 class="text-lg font-semibold text-foreground truncate">
                  {{ schedule.subject_code }}
                </h4>
                <Badge :variant="getStatusVariant(schedule.status)" class="ml-2">
                  {{ getStatusLabel(schedule.status) }}
                </Badge>
              </div>
              
              <p class="text-sm text-muted-foreground mb-3 line-clamp-2">
                {{ schedule.subject_title }}
              </p>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                <div class="flex items-center space-x-2">
                  <MapPinIcon class="w-4 h-4 text-muted-foreground" />
                  <span class="text-muted-foreground">Room {{ schedule.room }}</span>
                </div>
                
                <div class="flex items-center space-x-2">
                  <AcademicCapIcon class="w-4 h-4 text-muted-foreground" />
                  <span class="text-muted-foreground">Section {{ schedule.section }}</span>
                </div>
                
                <div class="flex items-center space-x-2">
                  <UsersIcon class="w-4 h-4 text-muted-foreground" />
                  <span class="text-muted-foreground">{{ schedule.student_count || 0 }} students</span>
                </div>
              </div>

              <!-- Progress Bar for Ongoing Classes -->
              <div v-if="schedule.status === 'ongoing'" class="mt-3">
                <div class="flex items-center justify-between text-xs text-muted-foreground mb-1">
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

            <!-- Color Indicator -->
            <div class="flex-shrink-0">
              <div :class="`w-4 h-4 rounded-full bg-${schedule.color}`"></div>
            </div>

            <!-- Action Menu -->
            <div class="flex-shrink-0">
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                    <EllipsisVerticalIcon class="h-4 w-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem @click="viewDetails(schedule)">
                    <EyeIcon class="mr-2 h-4 w-4" />
                    View Details
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="takeAttendance(schedule)">
                    <ClipboardDocumentListIcon class="mr-2 h-4 w-4" />
                    Take Attendance
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="viewStudents(schedule)">
                    <UsersIcon class="mr-2 h-4 w-4" />
                    View Students
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <Card class="mt-6">
        <CardHeader>
          <CardTitle class="text-lg">Quick Actions</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <Button variant="outline" class="h-auto p-4 justify-start">
              <ClipboardDocumentListIcon class="w-5 h-5 mr-3 text-blue-600" />
              <div class="text-left">
                <div class="font-medium text-sm">Attendance</div>
                <div class="text-xs text-muted-foreground">View attendance</div>
              </div>
            </Button>

            <Button variant="outline" class="h-auto p-4 justify-start">
              <ChartBarIcon class="w-5 h-5 mr-3 text-purple-600" />
              <div class="text-left">
                <div class="font-medium text-sm">Grades</div>
                <div class="text-xs text-muted-foreground">View grades</div>
              </div>
            </Button>

            <Button variant="outline" class="h-auto p-4 justify-start">
              <UsersIcon class="w-5 h-5 mr-3 text-green-600" />
              <div class="text-left">
                <div class="font-medium text-sm">Students</div>
                <div class="text-xs text-muted-foreground">View students</div>
              </div>
            </Button>

            <Button variant="outline" class="h-auto p-4 justify-start">
              <CalendarIcon class="w-5 h-5 mr-3 text-orange-600" />
              <div class="text-left">
                <div class="font-medium text-sm">Schedule</div>
                <div class="text-xs text-muted-foreground">View full schedule</div>
              </div>
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Button } from '@/Components/shadcn/ui/button'
import { Badge } from '@/Components/shadcn/ui/badge'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/shadcn/ui/dropdown-menu'
import {
  CalendarIcon,
  MapPinIcon,
  AcademicCapIcon,
  UsersIcon,
  EllipsisVerticalIcon,
  EyeIcon,
  ClipboardDocumentListIcon,
  DocumentTextIcon,
  ChartBarIcon,
  BellIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  dailySchedule: {
    type: Array,
    default: () => []
  },
  currentDate: {
    type: String,
    default: () => new Date().toISOString().split('T')[0]
  }
})

const currentTime = ref(new Date())

// Update current time every minute
let timeInterval = null

onMounted(() => {
  timeInterval = setInterval(() => {
    currentTime.value = new Date()
  }, 60000)
})

onUnmounted(() => {
  if (timeInterval) {
    clearInterval(timeInterval)
  }
})

const isToday = computed(() => {
  const today = new Date().toISOString().split('T')[0]
  return props.currentDate === today
})

const sortedSchedule = computed(() => {
  return [...props.dailySchedule].sort((a, b) => {
    return a.raw_start_time.localeCompare(b.raw_start_time)
  })
})

const currentTimePosition = computed(() => {
  if (!isToday.value || sortedSchedule.value.length === 0) return null
  
  const now = currentTime.value
  const currentMinutes = now.getHours() * 60 + now.getMinutes()
  
  // Find the position relative to the schedule items
  const firstClassMinutes = timeToMinutes(sortedSchedule.value[0].raw_start_time)
  const lastClassMinutes = timeToMinutes(sortedSchedule.value[sortedSchedule.value.length - 1].raw_end_time)
  
  if (currentMinutes < firstClassMinutes || currentMinutes > lastClassMinutes) {
    return null
  }
  
  // Calculate position (approximate)
  const totalScheduleMinutes = lastClassMinutes - firstClassMinutes
  const relativeMinutes = currentMinutes - firstClassMinutes
  const totalHeight = sortedSchedule.value.length * 120 // Approximate height per item
  
  return (relativeMinutes / totalScheduleMinutes) * totalHeight
})

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const getDayOfWeek = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { weekday: 'long' })
}

const getCurrentTime = () => {
  return currentTime.value.toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  })
}

const getScheduleClasses = (schedule) => {
  const baseClasses = 'border-l-4'
  const statusClasses = {
    'ongoing': 'bg-green-50 border-l-green-500 dark:bg-green-900/20',
    'upcoming': 'bg-blue-50 border-l-blue-500 dark:bg-blue-900/20',
    'completed': 'bg-gray-50 border-l-gray-500 dark:bg-gray-900/20',
    'scheduled': 'bg-purple-50 border-l-purple-500 dark:bg-purple-900/20'
  }
  
  return `${baseClasses} ${statusClasses[schedule.status] || statusClasses.scheduled}`
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
  const startMinutes = timeToMinutes(schedule.raw_start_time)
  const endMinutes = timeToMinutes(schedule.raw_end_time)
  
  const totalDuration = endMinutes - startMinutes
  const elapsed = currentMinutes - startMinutes
  
  return Math.min(Math.max(Math.round((elapsed / totalDuration) * 100), 0), 100)
}

const timeToMinutes = (timeString) => {
  const [hours, minutes] = timeString.split(':').map(Number)
  return hours * 60 + minutes
}

const emit = defineEmits(['scheduleClick'])

const viewDetails = (schedule) => {
  // Emit schedule click event
  emit('scheduleClick', schedule)
}

const takeAttendance = (schedule) => {
  // Navigate to attendance page
  console.log('Take attendance for:', schedule)
}

const viewStudents = (schedule) => {
  // Navigate to students page
  console.log('View students for:', schedule)
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
