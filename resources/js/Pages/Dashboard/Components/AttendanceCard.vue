<template>
  <Card class="hover:shadow-md transition-shadow duration-200">
    <CardHeader class="pb-3">
      <div class="flex items-center justify-between">
        <div>
          <CardTitle class="text-base">My Attendance</CardTitle>
          <CardDescription class="text-xs">Overall attendance rate</CardDescription>
        </div>
        <div class="h-8 w-8 bg-green-100 rounded-lg flex items-center justify-center">
          <Icon icon="heroicons:check-circle" class="h-4 w-4 text-green-600" />
        </div>
      </div>
    </CardHeader>
    <CardContent class="pt-0">
      <div class="space-y-3">
        <!-- Overall Rate -->
        <div class="text-center">
          <div class="text-2xl font-bold" :class="getAttendanceRateColor(attendanceStats.attendance_rate)">
            {{ attendanceStats.attendance_rate }}%
          </div>
          <p class="text-xs text-muted-foreground">
            {{ attendanceStats.present_count }}/{{ attendanceStats.total }} sessions
          </p>
        </div>

        <!-- Progress Ring or Bar -->
        <div class="flex justify-center">
          <div class="relative w-16 h-16">
            <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 64 64">
              <!-- Background circle -->
              <circle
                cx="32"
                cy="32"
                r="28"
                stroke="currentColor"
                stroke-width="4"
                fill="none"
                class="text-gray-200"
              />
              <!-- Progress circle -->
              <circle
                cx="32"
                cy="32"
                r="28"
                stroke="currentColor"
                stroke-width="4"
                fill="none"
                :class="getProgressColor(attendanceStats.attendance_rate)"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="strokeDashoffset"
                class="transition-all duration-300"
              />
            </svg>
            <div class="absolute inset-0 flex items-center justify-center">
              <span class="text-xs font-medium" :class="getAttendanceRateColor(attendanceStats.attendance_rate)">
                {{ attendanceStats.attendance_rate }}%
              </span>
            </div>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-3 gap-2 text-center text-xs">
          <div>
            <div class="font-medium text-green-600">{{ attendanceStats.present }}</div>
            <div class="text-muted-foreground">Present</div>
          </div>
          <div>
            <div class="font-medium text-yellow-600">{{ attendanceStats.late }}</div>
            <div class="text-muted-foreground">Late</div>
          </div>
          <div>
            <div class="font-medium text-red-600">{{ attendanceStats.absent }}</div>
            <div class="text-muted-foreground">Absent</div>
          </div>
        </div>

        <!-- Alerts -->
        <div v-if="attendanceAlerts.length > 0" class="space-y-1">
          <div v-for="alert in attendanceAlerts.slice(0, 2)" :key="alert.type + alert.class.id"
               class="text-xs p-2 rounded-lg"
               :class="alert.severity === 'high' ? 'bg-red-50 text-red-700' : 'bg-yellow-50 text-yellow-700'">
            <div class="flex items-center space-x-1">
              <Icon icon="heroicons:exclamation-triangle" class="h-3 w-3" />
              <span class="font-medium">{{ alert.class.subject_code }}</span>
            </div>
            <div class="mt-1">{{ alert.message }}</div>
          </div>
        </div>

        <!-- Recent Classes -->
        <div v-if="recentClasses.length > 0" class="space-y-1">
          <div class="text-xs font-medium text-muted-foreground">Recent Classes</div>
          <div class="space-y-1">
            <div v-for="classData in recentClasses.slice(0, 3)" :key="classData.class.id"
                 class="flex items-center justify-between text-xs">
              <span class="truncate">{{ classData.class.subject_code }}</span>
              <Badge :variant="getStatusBadgeVariant(classData.last_attendance?.status || 'absent')" class="text-xs">
                {{ (classData.last_attendance?.status || 'absent').charAt(0).toUpperCase() + (classData.last_attendance?.status || 'absent').slice(1) }}
              </Badge>
            </div>
          </div>
        </div>

        <!-- Action Button -->
        <Button @click="viewFullAttendance" size="sm" class="w-full text-xs">
          <Icon icon="heroicons:eye" class="w-3 h-3 mr-1" />
          View Details
        </Button>
      </div>
    </CardContent>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Button } from '@/Components/shadcn/ui/button'
import { Badge } from '@/Components/shadcn/ui/badge'
import { Icon } from '@iconify/vue'

const props = defineProps({
  attendanceStats: {
    type: Object,
    default: () => ({
      total: 0,
      present: 0,
      absent: 0,
      late: 0,
      excused: 0,
      partial: 0,
      present_count: 0,
      attendance_rate: 0,
    })
  },
  attendanceAlerts: {
    type: Array,
    default: () => []
  },
  recentClasses: {
    type: Array,
    default: () => []
  }
})

// Circle progress calculations
const circumference = 2 * Math.PI * 28 // radius = 28
const strokeDashoffset = computed(() => {
  const progress = props.attendanceStats.attendance_rate / 100
  return circumference - (progress * circumference)
})

const getAttendanceRateColor = (rate) => {
  if (rate >= 90) return 'text-green-600'
  if (rate >= 75) return 'text-yellow-600'
  return 'text-red-600'
}

const getProgressColor = (rate) => {
  if (rate >= 90) return 'text-green-500'
  if (rate >= 75) return 'text-yellow-500'
  return 'text-red-500'
}

const getStatusBadgeVariant = (status) => {
  switch (status) {
    case 'present': return 'success'
    case 'late': return 'warning'
    case 'partial': return 'secondary'
    case 'excused': return 'outline'
    case 'absent': return 'destructive'
    default: return 'outline'
  }
}

const viewFullAttendance = () => {
  router.visit(route('student.attendance.index'))
}
</script>
