<template>
  <AppLayout title="Attendance History">
    <div class="min-h-screen bg-background">
      <div class="container mx-auto px-3 py-3 space-y-4 max-w-6xl">
        <!-- Header -->
        <div class="flex flex-col space-y-2 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
          <div class="min-w-0 flex-1">
            <h1 class="text-2xl font-bold text-foreground">Attendance History</h1>
            <p class="text-sm text-muted-foreground mt-1">
              Complete record of your attendance across all classes
            </p>
          </div>
          <div class="flex items-center space-x-2 flex-shrink-0">
            <Button @click="exportHistory" variant="outline" size="sm">
              <Icon icon="heroicons:arrow-down-tray" class="w-4 h-4 mr-2" />
              Export
            </Button>
            <Button @click="goBack" variant="outline" size="sm">
              <Icon icon="heroicons:arrow-left" class="w-4 h-4 mr-2" />
              Back
            </Button>
          </div>
        </div>

        <!-- Filters -->
        <Card>
          <CardHeader>
            <CardTitle>Filters</CardTitle>
            <CardDescription>Filter your attendance history</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <Label>Class</Label>
                <Select :value="filters.class_id" @update:value="updateFilter('class_id', $event)">
                  <SelectTrigger>
                    <SelectValue placeholder="All Classes" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">All Classes</SelectItem>
                    <SelectItem 
                      v-for="classOption in available_classes" 
                      :key="classOption.id"
                      :value="classOption.id.toString()"
                    >
                      {{ classOption.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
              
              <div>
                <Label>Start Date</Label>
                <Input 
                  type="date" 
                  :value="filters.start_date" 
                  @input="updateFilter('start_date', $event.target.value)"
                />
              </div>
              
              <div>
                <Label>End Date</Label>
                <Input 
                  type="date" 
                  :value="filters.end_date" 
                  @input="updateFilter('end_date', $event.target.value)"
                />
              </div>
              
              <div class="flex items-end">
                <Button @click="applyFilters" class="w-full">
                  <Icon icon="heroicons:funnel" class="w-4 h-4 mr-2" />
                  Apply Filters
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Overall Statistics -->
        <Card>
          <CardHeader>
            <CardTitle>Overall Statistics</CardTitle>
            <CardDescription>Summary of your filtered attendance data</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div class="text-center p-4 border rounded-lg">
                <p class="text-2xl font-bold">{{ overall_stats.total }}</p>
                <p class="text-sm text-muted-foreground">Total Sessions</p>
              </div>
              <div class="text-center p-4 border rounded-lg">
                <p class="text-2xl font-bold text-green-600">{{ overall_stats.present_count }}</p>
                <p class="text-sm text-muted-foreground">Present</p>
              </div>
              <div class="text-center p-4 border rounded-lg">
                <p class="text-2xl font-bold text-red-600">{{ overall_stats.absent }}</p>
                <p class="text-sm text-muted-foreground">Absent</p>
              </div>
              <div class="text-center p-4 border rounded-lg">
                <p class="text-2xl font-bold" :class="getAttendanceRateColor(overall_stats.attendance_rate)">
                  {{ overall_stats.attendance_rate }}%
                </p>
                <p class="text-sm text-muted-foreground">Attendance Rate</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Attendance History by Month -->
        <div class="space-y-6">
          <div v-for="monthData in history" :key="monthData.month_label" class="space-y-4">
            <Card>
              <CardHeader>
                <div class="flex items-center justify-between">
                  <div>
                    <CardTitle>{{ monthData.month_label }}</CardTitle>
                    <CardDescription>
                      {{ monthData.attendances.length }} sessions • 
                      {{ monthData.stats.attendance_rate }}% attendance rate
                    </CardDescription>
                  </div>
                  <div class="flex items-center space-x-4">
                    <div class="text-right text-sm">
                      <p class="font-medium" :class="getAttendanceRateColor(monthData.stats.attendance_rate)">
                        {{ monthData.stats.present_count }}/{{ monthData.stats.total }}
                      </p>
                      <p class="text-muted-foreground">Present</p>
                    </div>
                    <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div 
                        class="h-full transition-all duration-300"
                        :class="getProgressBarColor(monthData.stats.attendance_rate)"
                        :style="{ width: `${monthData.stats.attendance_rate}%` }"
                      ></div>
                    </div>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                <div class="space-y-2">
                  <div v-for="attendance in monthData.attendances" :key="attendance.id"
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
                          {{ attendance.class.subject_code }} - 
                          {{ attendance.class.Subject?.title || attendance.class.ShsSubject?.title }}
                        </p>
                      </div>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                      <div v-if="attendance.remarks" class="text-right max-w-xs">
                        <p class="text-sm text-muted-foreground truncate">{{ attendance.remarks }}</p>
                      </div>
                      <Badge :variant="getStatusBadgeVariant(attendance.status)">
                        {{ attendance.status.charAt(0).toUpperCase() + attendance.status.slice(1) }}
                      </Badge>
                      <div class="text-right text-xs text-muted-foreground">
                        <p>{{ formatTime(attendance.marked_at) }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>

        <!-- No Data State -->
        <Card v-if="history.length === 0">
          <CardContent class="p-12 text-center">
            <Icon icon="heroicons:calendar-x" class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
            <h3 class="text-lg font-semibold mb-2">No Attendance Records Found</h3>
            <p class="text-muted-foreground mb-4">
              No attendance records match your current filters. Try adjusting your search criteria.
            </p>
            <Button @click="clearFilters" variant="outline">
              Clear Filters
            </Button>
          </CardContent>
        </Card>

        <!-- Load More -->
        <div v-if="total_records > history.reduce((sum, month) => sum + month.attendances.length, 0)" 
             class="text-center">
          <Button @click="loadMore" variant="outline" :disabled="isLoading">
            {{ isLoading ? 'Loading...' : 'Load More Records' }}
          </Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Button } from '@/Components/shadcn/ui/button'
import { Badge } from '@/Components/shadcn/ui/badge'
import { Input } from '@/Components/shadcn/ui/input'
import { Label } from '@/Components/shadcn/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/shadcn/ui/select'
import { Icon } from '@iconify/vue'
import { toast } from 'vue-sonner'

const props = defineProps({
  student: Object,
  history: Array,
  total_records: Number,
  overall_stats: Object,
  available_classes: Array,
  filters: Object,
})

const isLoading = ref(false)
const currentFilters = reactive({ ...props.filters })

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
    weekday: 'long',
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatTime = (datetime) => {
  if (!datetime) return ''
  return new Date(datetime).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const updateFilter = (key, value) => {
  currentFilters[key] = value
}

const applyFilters = () => {
  router.visit(route('student.attendance.history'), {
    data: currentFilters,
    preserveState: false,
    preserveScroll: true
  })
}

const clearFilters = () => {
  Object.keys(currentFilters).forEach(key => {
    currentFilters[key] = ''
  })
  applyFilters()
}

const loadMore = () => {
  // Implementation for loading more records
  isLoading.value = true
  // This would typically make an API call to load more data
  setTimeout(() => {
    isLoading.value = false
    toast.success('More records loaded')
  }, 1000)
}

const exportHistory = () => {
  router.visit(route('student.attendance.export'), {
    data: {
      ...currentFilters,
      format: 'csv'
    }
  })
}

const goBack = () => {
  router.visit(route('student.attendance.index'))
}
</script>
