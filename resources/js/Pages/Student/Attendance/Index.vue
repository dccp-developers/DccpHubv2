<template>
  <AppLayout title="My Attendance">
    <div class="min-h-screen bg-background">
      <div class="container mx-auto px-3 py-3 space-y-4 max-w-6xl">
        <!-- Header -->
        <div class="flex flex-col space-y-2 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
          <div class="min-w-0 flex-1">
            <h1 class="text-2xl font-bold text-foreground">My Attendance</h1>
            <p class="text-sm text-muted-foreground mt-1">
              Track your attendance across all enrolled classes
            </p>
          </div>
          <div class="flex items-center space-x-2 flex-shrink-0">
            <Button @click="showExportDialog = true" variant="outline" size="sm">
              <Icon icon="heroicons:arrow-down-tray" class="w-4 h-4 mr-2" />
              Export
            </Button>
            <Button @click="refreshData" :disabled="isLoading" variant="outline" size="sm">
              <Icon icon="heroicons:arrow-path" class="w-4 h-4 mr-2" :class="{ 'animate-spin': isLoading }" />
              Refresh
            </Button>
          </div>
        </div>

        <!-- Attendance Alerts -->
        <div v-if="attendance_alerts.length > 0" class="space-y-2">
          <Alert v-for="alert in attendance_alerts" :key="alert.type + alert.class.id" 
                 :variant="alert.severity === 'high' ? 'destructive' : 'default'">
            <Icon icon="heroicons:exclamation-triangle" class="h-4 w-4" />
            <AlertTitle>Attendance Alert</AlertTitle>
            <AlertDescription>{{ alert.message }}</AlertDescription>
          </Alert>
        </div>

        <!-- Overall Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <Card>
            <CardContent class="p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Overall Attendance</p>
                  <p class="text-2xl font-bold" :class="getAttendanceRateColor(overall_stats.attendance_rate)">
                    {{ overall_stats.attendance_rate }}%
                  </p>
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
                  <p class="text-sm font-medium text-muted-foreground">Total Classes</p>
                  <p class="text-2xl font-bold">{{ classes.length }}</p>
                </div>
                <div class="h-8 w-8 bg-blue-100 rounded-lg flex items-center justify-center">
                  <Icon icon="heroicons:academic-cap" class="h-4 w-4 text-blue-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent class="p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Present Sessions</p>
                  <p class="text-2xl font-bold text-green-600">{{ overall_stats.present_count }}</p>
                </div>
                <div class="h-8 w-8 bg-green-100 rounded-lg flex items-center justify-center">
                  <Icon icon="heroicons:check" class="h-4 w-4 text-green-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent class="p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Absent Sessions</p>
                  <p class="text-2xl font-bold text-red-600">{{ overall_stats.absent }}</p>
                </div>
                <div class="h-8 w-8 bg-red-100 rounded-lg flex items-center justify-center">
                  <Icon icon="heroicons:x-circle" class="h-4 w-4 text-red-600" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Attendance Trend -->
        <Card>
          <CardHeader>
            <CardTitle>Attendance Trend (Last 8 Weeks)</CardTitle>
            <CardDescription>Your attendance rate over time</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="h-48 flex items-end justify-between space-x-2">
              <div v-for="week in attendance_trend" :key="week.week" class="flex-1 flex flex-col items-center">
                <div 
                  class="bg-primary rounded-t w-full transition-all duration-300 mb-2"
                  :style="{ height: `${Math.max(week.attendance_rate * 1.5, 10)}px` }"
                ></div>
                <div class="text-center">
                  <p class="text-xs text-muted-foreground">{{ week.week }}</p>
                  <p class="text-sm font-medium">{{ week.attendance_rate }}%</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Classes Overview -->
        <Card>
          <CardHeader>
            <CardTitle>Classes Overview</CardTitle>
            <CardDescription>Your attendance status for each enrolled class</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div v-for="classData in classes" :key="classData.class.id" 
                   class="border rounded-lg p-4 hover:bg-muted/50 transition-colors cursor-pointer"
                   @click="viewClassDetails(classData.class.id)">
                <div class="flex items-center justify-between">
                  <div class="flex-1">
                    <div class="flex items-center space-x-3">
                      <div>
                        <h3 class="font-semibold">{{ classData.class.subject_code }}</h3>
                        <p class="text-sm text-muted-foreground">
                          {{ classData.class.Subject?.title || classData.class.ShsSubject?.title }}
                        </p>
                      </div>
                      <Badge v-if="classData.needs_attention" variant="destructive">
                        Needs Attention
                      </Badge>
                      <Badge v-else :variant="getAttendanceBadgeVariant(classData.stats.attendance_rate)">
                        {{ classData.stats.attendance_rate }}%
                      </Badge>
                    </div>
                    
                    <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                      <div>
                        <p class="text-muted-foreground">Present</p>
                        <p class="font-medium text-green-600">{{ classData.stats.present_count }}</p>
                      </div>
                      <div>
                        <p class="text-muted-foreground">Absent</p>
                        <p class="font-medium text-red-600">{{ classData.stats.absent }}</p>
                      </div>
                      <div>
                        <p class="text-muted-foreground">Late</p>
                        <p class="font-medium text-yellow-600">{{ classData.stats.late }}</p>
                      </div>
                      <div>
                        <p class="text-muted-foreground">Total Sessions</p>
                        <p class="font-medium">{{ classData.stats.total }}</p>
                      </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-3">
                      <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div 
                          class="h-full transition-all duration-300"
                          :class="getProgressBarColor(classData.stats.attendance_rate)"
                          :style="{ width: `${classData.stats.attendance_rate}%` }"
                        ></div>
                      </div>
                    </div>
                  </div>

                  <div class="flex items-center space-x-2">
                    <Button size="sm" variant="outline" @click.stop="viewClassDetails(classData.class.id)">
                      <Icon icon="heroicons:eye" class="w-4 h-4 mr-2" />
                      View Details
                    </Button>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Upcoming Classes -->
        <Card v-if="upcoming_classes.length > 0">
          <CardHeader>
            <CardTitle>Today's Classes</CardTitle>
            <CardDescription>Your scheduled classes for today</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-for="upcomingClass in upcoming_classes" :key="upcomingClass.class.id"
                   class="flex items-center justify-between p-3 border rounded-lg">
                <div class="flex items-center space-x-3">
                  <div class="h-10 w-10 bg-primary/10 rounded-lg flex items-center justify-center">
                    <Icon icon="heroicons:clock" class="h-5 w-5 text-primary" />
                  </div>
                  <div>
                    <p class="font-medium">{{ upcomingClass.class.subject_code }}</p>
                    <p class="text-sm text-muted-foreground">
                      {{ upcomingClass.next_schedule?.start_time }} - {{ upcomingClass.next_schedule?.end_time }}
                    </p>
                  </div>
                </div>
                <Badge variant="outline">
                  {{ getTimeUntilClass(upcomingClass.next_schedule?.start_time) }}
                </Badge>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Recent Attendance -->
        <Card>
          <CardHeader>
            <div class="flex items-center justify-between">
              <div>
                <CardTitle>Recent Attendance</CardTitle>
                <CardDescription>Your latest attendance records</CardDescription>
              </div>
              <Button @click="viewFullHistory" variant="outline" size="sm">
                <Icon icon="heroicons:eye" class="w-4 h-4 mr-2" />
                View All
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-for="classData in classes.slice(0, 3)" :key="classData.class.id">
                <div v-if="classData.recent_attendances.length > 0">
                  <h4 class="font-medium mb-2">{{ classData.class.subject_code }}</h4>
                  <div class="space-y-2">
                    <div v-for="attendance in classData.recent_attendances.slice(0, 3)" :key="attendance.id"
                         class="flex items-center justify-between text-sm">
                      <span>{{ formatDate(attendance.date) }}</span>
                      <Badge :variant="getStatusBadgeVariant(attendance.status)">
                        {{ attendance.status.charAt(0).toUpperCase() + attendance.status.slice(1) }}
                      </Badge>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Export Dialog -->
    <Dialog v-model:open="showExportDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Export Attendance Data</DialogTitle>
          <DialogDescription>
            Export your attendance records
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4">
          <div>
            <Label>Select Class (Optional)</Label>
            <Select v-model="exportClassId">
              <SelectTrigger>
                <SelectValue placeholder="All Classes" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">All Classes</SelectItem>
                <SelectItem 
                  v-for="classData in classes" 
                  :key="classData.class.id"
                  :value="classData.class.id.toString()"
                >
                  {{ classData.class.subject_code }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <Label>Start Date</Label>
              <Input type="date" v-model="exportStartDate" />
            </div>
            <div>
              <Label>End Date</Label>
              <Input type="date" v-model="exportEndDate" />
            </div>
          </div>
          <div>
            <Label>Format</Label>
            <Select v-model="exportFormat">
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="csv">CSV</SelectItem>
                <SelectItem value="excel">Excel</SelectItem>
                <SelectItem value="pdf">PDF</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
        <DialogFooter>
          <Button @click="showExportDialog = false" variant="outline">
            Cancel
          </Button>
          <Button @click="exportData" :disabled="isExporting">
            {{ isExporting ? 'Exporting...' : 'Export' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Button } from '@/Components/shadcn/ui/button'
import { Badge } from '@/Components/shadcn/ui/badge'
import { Input } from '@/Components/shadcn/ui/input'
import { Label } from '@/Components/shadcn/ui/label'
import { Alert, AlertDescription, AlertTitle } from '@/Components/shadcn/ui/alert'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/shadcn/ui/select'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/shadcn/ui/dialog'
import { Icon } from '@iconify/vue'
import { toast } from 'vue-sonner'

const props = defineProps({
  student: Object,
  classes: Array,
  overall_stats: Object,
  attendance_trend: Array,
  upcoming_classes: Array,
  attendance_alerts: Array,
  user: Object,
})

// Reactive data
const isLoading = ref(false)
const showExportDialog = ref(false)
const exportClassId = ref('')
const exportStartDate = ref('')
const exportEndDate = ref('')
const exportFormat = ref('csv')
const isExporting = ref(false)

// Initialize export dates
onMounted(() => {
  const today = new Date()
  const threeMonthsAgo = new Date(today.getTime() - 90 * 24 * 60 * 60 * 1000)
  exportStartDate.value = threeMonthsAgo.toISOString().split('T')[0]
  exportEndDate.value = today.toISOString().split('T')[0]
})

const getAttendanceRateColor = (rate) => {
  if (rate >= 90) return 'text-green-600'
  if (rate >= 75) return 'text-yellow-600'
  return 'text-red-600'
}

const getAttendanceBadgeVariant = (rate) => {
  if (rate >= 90) return 'success'
  if (rate >= 75) return 'secondary'
  return 'destructive'
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

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const getTimeUntilClass = (startTime) => {
  // Simple implementation - you might want to make this more sophisticated
  return 'Soon'
}

const viewClassDetails = (classId) => {
  router.visit(route('student.attendance.class.show', { class: classId }))
}

const viewFullHistory = () => {
  router.visit(route('student.attendance.history'))
}

const refreshData = () => {
  isLoading.value = true
  router.reload({
    onFinish: () => {
      isLoading.value = false
      toast.success('Data refreshed')
    }
  })
}

const exportData = async () => {
  isExporting.value = true

  try {
    const params = new URLSearchParams({
      class_id: exportClassId.value || '',
      start_date: exportStartDate.value,
      end_date: exportEndDate.value,
      format: exportFormat.value,
    })

    const response = await fetch(`${route('student.attendance.export')}?${params}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    })

    const result = await response.json()

    if (result.success) {
      window.open(result.download_url, '_blank')
      toast.success('Export prepared successfully')
      showExportDialog.value = false
    } else {
      toast.error(result.message || 'Failed to export data')
    }
  } catch (error) {
    console.error('Error exporting data:', error)
    toast.error('Failed to export data')
  } finally {
    isExporting.value = false
  }
}
</script>
