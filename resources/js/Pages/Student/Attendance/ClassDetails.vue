<template>
  <AppLayout :title="pageTitle">
    <div class="min-h-screen bg-background">
      <div class="container mx-auto px-3 py-3 space-y-4 max-w-6xl">
        <!-- Header -->
        <div class="flex flex-col space-y-2 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
          <div class="min-w-0 flex-1">
            <h1 class="text-2xl font-bold text-foreground">{{ props.class.subject_code }}</h1>
            <p class="text-sm text-muted-foreground mt-1">
              {{ props.class.Subject?.title || props.class.ShsSubject?.title }}
            </p>
          </div>
          <div class="flex items-center space-x-2 flex-shrink-0">
            <Button @click="exportClassData" variant="outline" size="sm">
              <Icon icon="heroicons:arrow-down-tray" class="w-4 h-4 mr-2" />
              Export
            </Button>
            <Button @click="goBack" variant="outline" size="sm">
              <Icon icon="heroicons:arrow-left" class="w-4 h-4 mr-2" />
              Back
            </Button>
          </div>
        </div>

        <!-- Class Information -->
        <Card>
          <CardHeader>
            <CardTitle>Class Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <p class="text-sm text-muted-foreground">Subject Code</p>
                <p class="font-medium">{{ props.class.subject_code }}</p>
              </div>
              <div>
                <p class="text-sm text-muted-foreground">Faculty</p>
                <p class="font-medium">{{ props.class.Faculty?.first_name }} {{ props.class.Faculty?.last_name }}</p>
              </div>
              <div>
                <p class="text-sm text-muted-foreground">Room</p>
                <p class="font-medium">{{ props.class.Room?.name || 'TBA' }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Attendance Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <Card>
            <CardContent class="p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Attendance Rate</p>
                  <p class="text-2xl font-bold" :class="getAttendanceRateColor(stats.attendance_rate)">
                    {{ stats.attendance_rate }}%
                  </p>
                </div>
                <div class="h-8 w-8 bg-green-100 rounded-lg flex items-center justify-center">
                  <Icon icon="heroicons:chart-bar" class="h-4 w-4 text-green-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent class="p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Present</p>
                  <p class="text-2xl font-bold text-green-600">{{ stats.present_count }}</p>
                </div>
                <div class="h-8 w-8 bg-green-100 rounded-lg flex items-center justify-center">
                  <Icon icon="heroicons:check-circle" class="h-4 w-4 text-green-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent class="p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Absent</p>
                  <p class="text-2xl font-bold text-red-600">{{ stats.absent }}</p>
                </div>
                <div class="h-8 w-8 bg-red-100 rounded-lg flex items-center justify-center">
                  <Icon icon="heroicons:x-circle" class="h-4 w-4 text-red-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent class="p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Total Sessions</p>
                  <p class="text-2xl font-bold">{{ stats.total }}</p>
                </div>
                <div class="h-8 w-8 bg-blue-100 rounded-lg flex items-center justify-center">
                  <Icon icon="heroicons:calendar" class="h-4 w-4 text-blue-600" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Monthly Breakdown -->
        <Card>
          <CardHeader>
            <CardTitle>Monthly Breakdown</CardTitle>
            <CardDescription>Attendance statistics by month</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div v-for="(monthStats, month) in monthly_data" :key="month" 
                   class="flex items-center justify-between p-4 border rounded-lg">
                <div class="flex items-center space-x-4">
                  <div class="h-10 w-10 bg-primary/10 rounded-lg flex items-center justify-center">
                    <Icon icon="heroicons:calendar" class="h-5 w-5 text-primary" />
                  </div>
                  <div>
                    <p class="font-medium">{{ formatMonth(month) }}</p>
                    <p class="text-sm text-muted-foreground">{{ monthStats.total }} sessions</p>
                  </div>
                </div>
                
                <div class="flex items-center space-x-4">
                  <div class="text-right text-sm">
                    <p class="font-medium" :class="getAttendanceRateColor(monthStats.attendance_rate)">
                      {{ monthStats.attendance_rate }}%
                    </p>
                    <p class="text-muted-foreground">
                      {{ monthStats.present_count }}/{{ monthStats.total }}
                    </p>
                  </div>
                  
                  <!-- Progress bar -->
                  <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div 
                      class="h-full transition-all duration-300"
                      :class="getProgressBarColor(monthStats.attendance_rate)"
                      :style="{ width: `${monthStats.attendance_rate}%` }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Attendance Pattern Analysis -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <Card>
            <CardHeader>
              <CardTitle>Day of Week Pattern</CardTitle>
              <CardDescription>Your attendance by day of the week</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-3">
                <div v-for="(dayStats, day) in attendance_pattern.by_day" :key="day" 
                     class="flex items-center justify-between">
                  <span class="text-sm font-medium">{{ day }}</span>
                  <div class="flex items-center space-x-2">
                    <div class="w-20 h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div 
                        class="h-full bg-primary transition-all duration-300"
                        :style="{ width: `${dayStats.attendance_rate}%` }"
                      ></div>
                    </div>
                    <span class="text-sm text-muted-foreground w-12 text-right">
                      {{ dayStats.attendance_rate }}%
                    </span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Recent Sessions</CardTitle>
              <CardDescription>Your latest attendance records</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-3">
                <div v-for="session in recent_sessions" :key="session.date"
                     class="flex items-center justify-between p-3 border rounded-lg">
                  <div class="flex items-center space-x-3">
                    <div class="h-8 w-8 rounded-lg flex items-center justify-center"
                         :class="getStatusIconBg(session.status)">
                      <Icon :icon="getStatusIcon(session.status)" class="h-4 w-4" 
                            :class="getStatusIconColor(session.status)" />
                    </div>
                    <div>
                      <p class="font-medium">{{ formatDate(session.date) }}</p>
                      <p class="text-sm text-muted-foreground" v-if="session.remarks">
                        {{ session.remarks }}
                      </p>
                    </div>
                  </div>
                  <Badge :variant="getStatusBadgeVariant(session.status)">
                    {{ session.status.charAt(0).toUpperCase() + session.status.slice(1) }}
                  </Badge>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Detailed Attendance History -->
        <Card>
          <CardHeader>
            <div class="flex items-center justify-between">
              <div>
                <CardTitle>Detailed Attendance History</CardTitle>
                <CardDescription>Complete record of your attendance for this class</CardDescription>
              </div>
              <Button @click="showAllRecords = !showAllRecords" variant="outline" size="sm">
                {{ showAllRecords ? 'Show Less' : 'Show All' }}
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            <div class="space-y-2">
              <div v-for="attendance in displayedAttendances" :key="attendance.id"
                   class="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/50">
                <div class="flex items-center space-x-3">
                  <div class="h-8 w-8 rounded-lg flex items-center justify-center"
                       :class="getStatusIconBg(attendance.status)">
                    <Icon :icon="getStatusIcon(attendance.status)" class="h-4 w-4" 
                          :class="getStatusIconColor(attendance.status)" />
                  </div>
                  <div>
                    <p class="font-medium">{{ formatDate(attendance.date) }}</p>
                    <p class="text-sm text-muted-foreground">
                      {{ formatDay(attendance.date) }}
                    </p>
                  </div>
                </div>
                
                <div class="flex items-center space-x-3">
                  <div v-if="attendance.remarks" class="text-right">
                    <p class="text-sm text-muted-foreground">{{ attendance.remarks }}</p>
                  </div>
                  <Badge :variant="getStatusBadgeVariant(attendance.status)">
                    {{ attendance.status.charAt(0).toUpperCase() + attendance.status.slice(1) }}
                  </Badge>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Button } from '@/Components/shadcn/ui/button'
import { Badge } from '@/Components/shadcn/ui/badge'
import { Icon } from '@iconify/vue'
import { toast } from 'vue-sonner'

const props = defineProps({
  student: Object,
  class: Object,
  enrollment: Object,
  stats: Object,
  monthly_data: Object,
  recent_sessions: Array,
  attendance_pattern: Object,
  attendances: Array,
})

const showAllRecords = ref(false)

const displayedAttendances = computed(() => {
  return showAllRecords.value ? props.attendances : props.attendances.slice(0, 10)
})

const pageTitle = computed(() => {
  return `Attendance - ${props.class.subject_code}`
})

const getAttendanceRateColor = (rate) => {
  if (rate >= 90) return 'text-green-600'
  if (rate >= 75) return 'text-yellow-600'
  return 'text-red-600'
}

const getProgressBarColor = (rate) => {
  if (rate >= 90) return 'bg-green-500'
  if (rate >= 75) return 'bg-yellow-500'
  return 'bg-red-500'
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

const getStatusIcon = (status) => {
  switch (status) {
    case 'present': return 'heroicons:check-circle'
    case 'late': return 'heroicons:clock'
    case 'partial': return 'heroicons:minus-circle'
    case 'excused': return 'heroicons:shield-check'
    case 'absent': return 'heroicons:x-circle'
    default: return 'heroicons:question-mark-circle'
  }
}

const getStatusIconBg = (status) => {
  switch (status) {
    case 'present': return 'bg-green-100'
    case 'late': return 'bg-yellow-100'
    case 'partial': return 'bg-blue-100'
    case 'excused': return 'bg-gray-100'
    case 'absent': return 'bg-red-100'
    default: return 'bg-gray-100'
  }
}

const getStatusIconColor = (status) => {
  switch (status) {
    case 'present': return 'text-green-600'
    case 'late': return 'text-yellow-600'
    case 'partial': return 'text-blue-600'
    case 'excused': return 'text-gray-600'
    case 'absent': return 'text-red-600'
    default: return 'text-gray-600'
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDay = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    weekday: 'long'
  })
}

const formatMonth = (monthString) => {
  const [year, month] = monthString.split('-')
  return new Date(year, month - 1).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long'
  })
}

const exportClassData = () => {
  router.visit(route('student.attendance.export'), {
    data: {
      class_id: props.class.id,
      format: 'csv'
    }
  })
}

const goBack = () => {
  router.visit(route('student.attendance.index'))
}
</script>
