<template>
  <AppLayout title="Attendance Statistics">
    <div class="min-h-screen bg-background">
      <div class="container mx-auto px-3 py-3 space-y-4 max-w-6xl">
        <!-- Header -->
        <div class="flex flex-col space-y-2 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
          <div class="min-w-0 flex-1">
            <h1 class="text-2xl font-bold text-foreground">Attendance Statistics</h1>
            <p class="text-sm text-muted-foreground mt-1">
              Detailed analysis of your attendance patterns and performance
            </p>
          </div>
          <div class="flex items-center space-x-2 flex-shrink-0">
            <Button @click="exportStatistics" variant="outline" size="sm">
              <Icon icon="heroicons:arrow-down-tray" class="w-4 h-4 mr-2" />
              Export
            </Button>
            <Button @click="goBack" variant="outline" size="sm">
              <Icon icon="heroicons:arrow-left" class="w-4 h-4 mr-2" />
              Back
            </Button>
          </div>
        </div>

        <!-- Date Range Filter -->
        <Card>
          <CardHeader>
            <CardTitle>Analysis Period</CardTitle>
            <CardDescription>Select the date range for your statistics</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <Label>Start Date</Label>
                <Input type="date" v-model="startDate" />
              </div>
              <div>
                <Label>End Date</Label>
                <Input type="date" v-model="endDate" />
              </div>
              <div class="flex items-end">
                <Button @click="updateDateRange" class="w-full">
                  <Icon icon="heroicons:arrow-path" class="w-4 h-4 mr-2" />
                  Update
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Overall Statistics -->
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
                  <Icon icon="heroicons:chart-bar" class="h-4 w-4 text-green-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent class="p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Total Sessions</p>
                  <p class="text-2xl font-bold">{{ overall_stats.total }}</p>
                </div>
                <div class="h-8 w-8 bg-blue-100 rounded-lg flex items-center justify-center">
                  <Icon icon="heroicons:calendar" class="h-4 w-4 text-blue-600" />
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
                  <Icon icon="heroicons:check-circle" class="h-4 w-4 text-green-600" />
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

        <!-- Attendance Trends -->
        <Card>
          <CardHeader>
            <CardTitle>Attendance Trends</CardTitle>
            <CardDescription>Your attendance rate over time</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="h-64 flex items-end justify-between space-x-2">
              <div v-for="trend in trends" :key="trend.period" class="flex-1 flex flex-col items-center">
                <div 
                  class="bg-primary rounded-t w-full transition-all duration-300 mb-2"
                  :style="{ height: `${Math.max(trend.stats.attendance_rate * 2, 10)}px` }"
                ></div>
                <div class="text-center">
                  <p class="text-xs text-muted-foreground">{{ formatPeriod(trend.period) }}</p>
                  <p class="text-sm font-medium">{{ trend.stats.attendance_rate }}%</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Class-wise Performance -->
        <Card>
          <CardHeader>
            <CardTitle>Performance by Class</CardTitle>
            <CardDescription>Your attendance rate for each enrolled class</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div v-for="classStats in classes_stats" :key="classStats.class.id" 
                   class="flex items-center justify-between p-4 border rounded-lg">
                <div class="flex items-center space-x-4">
                  <div>
                    <h3 class="font-semibold">{{ classStats.class.subject_code }}</h3>
                    <p class="text-sm text-muted-foreground">
                      {{ classStats.class.Subject?.title || classStats.class.ShsSubject?.title }}
                    </p>
                  </div>
                  <Badge :variant="getAttendanceBadgeVariant(classStats.stats.attendance_rate)">
                    {{ classStats.stats.attendance_rate }}%
                  </Badge>
                </div>
                
                <div class="flex items-center space-x-4">
                  <!-- Progress bar -->
                  <div class="w-32 h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div 
                      class="h-full transition-all duration-300"
                      :class="getProgressBarColor(classStats.stats.attendance_rate)"
                      :style="{ width: `${classStats.stats.attendance_rate}%` }"
                    ></div>
                  </div>
                  
                  <div class="text-right text-sm">
                    <p class="font-medium">{{ classStats.stats.present_count }}/{{ classStats.stats.total }}</p>
                    <p class="text-muted-foreground">Present/Total</p>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Detailed Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Status Breakdown -->
          <Card>
            <CardHeader>
              <CardTitle>Status Breakdown</CardTitle>
              <CardDescription>Distribution of your attendance statuses</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <div class="h-3 w-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm">Present</span>
                  </div>
                  <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium">{{ overall_stats.present }}</span>
                    <span class="text-xs text-muted-foreground">
                      ({{ Math.round((overall_stats.present / overall_stats.total) * 100) }}%)
                    </span>
                  </div>
                </div>
                
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <div class="h-3 w-3 bg-yellow-500 rounded-full"></div>
                    <span class="text-sm">Late</span>
                  </div>
                  <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium">{{ overall_stats.late }}</span>
                    <span class="text-xs text-muted-foreground">
                      ({{ Math.round((overall_stats.late / overall_stats.total) * 100) }}%)
                    </span>
                  </div>
                </div>
                
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <div class="h-3 w-3 bg-blue-500 rounded-full"></div>
                    <span class="text-sm">Partial</span>
                  </div>
                  <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium">{{ overall_stats.partial }}</span>
                    <span class="text-xs text-muted-foreground">
                      ({{ Math.round((overall_stats.partial / overall_stats.total) * 100) }}%)
                    </span>
                  </div>
                </div>
                
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <div class="h-3 w-3 bg-gray-500 rounded-full"></div>
                    <span class="text-sm">Excused</span>
                  </div>
                  <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium">{{ overall_stats.excused }}</span>
                    <span class="text-xs text-muted-foreground">
                      ({{ Math.round((overall_stats.excused / overall_stats.total) * 100) }}%)
                    </span>
                  </div>
                </div>
                
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <div class="h-3 w-3 bg-red-500 rounded-full"></div>
                    <span class="text-sm">Absent</span>
                  </div>
                  <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium">{{ overall_stats.absent }}</span>
                    <span class="text-xs text-muted-foreground">
                      ({{ Math.round((overall_stats.absent / overall_stats.total) * 100) }}%)
                    </span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Performance Insights -->
          <Card>
            <CardHeader>
              <CardTitle>Performance Insights</CardTitle>
              <CardDescription>Key insights about your attendance</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div class="p-3 border rounded-lg">
                  <div class="flex items-center space-x-2 mb-2">
                    <Icon icon="heroicons:chart-bar" class="h-4 w-4 text-blue-600" />
                    <span class="font-medium text-sm">Overall Performance</span>
                  </div>
                  <p class="text-sm text-muted-foreground">
                    Your overall attendance rate is {{ overall_stats.attendance_rate }}%, which is 
                    {{ overall_stats.attendance_rate >= 75 ? 'above' : 'below' }} the recommended 75% threshold.
                  </p>
                </div>

                <div class="p-3 border rounded-lg">
                  <div class="flex items-center space-x-2 mb-2">
                    <Icon icon="heroicons:trophy" class="h-4 w-4 text-yellow-600" />
                    <span class="font-medium text-sm">Best Performing Class</span>
                  </div>
                  <p class="text-sm text-muted-foreground">
                    {{ getBestPerformingClass()?.class.subject_code || 'N/A' }} with 
                    {{ getBestPerformingClass()?.stats.attendance_rate || 0 }}% attendance rate.
                  </p>
                </div>

                <div class="p-3 border rounded-lg">
                  <div class="flex items-center space-x-2 mb-2">
                    <Icon icon="heroicons:exclamation-triangle" class="h-4 w-4 text-red-600" />
                    <span class="font-medium text-sm">Areas for Improvement</span>
                  </div>
                  <p class="text-sm text-muted-foreground">
                    {{ getImprovementAreas() }}
                  </p>
                </div>

                <div class="p-3 border rounded-lg">
                  <div class="flex items-center space-x-2 mb-2">
                    <Icon icon="heroicons:calendar" class="h-4 w-4 text-green-600" />
                    <span class="font-medium text-sm">Attendance Streak</span>
                  </div>
                  <p class="text-sm text-muted-foreground">
                    You have attended {{ overall_stats.present_count }} out of {{ overall_stats.total }} sessions 
                    in the selected period.
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
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
import { Input } from '@/Components/shadcn/ui/input'
import { Label } from '@/Components/shadcn/ui/label'
import { Icon } from '@iconify/vue'
import { toast } from 'vue-sonner'

const props = defineProps({
  student: Object,
  overall_stats: Object,
  classes_stats: Array,
  trends: Array,
  date_range: Object,
})

const startDate = ref(props.date_range.start)
const endDate = ref(props.date_range.end)

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

const formatPeriod = (period) => {
  return new Date(period).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric'
  })
}

const getBestPerformingClass = () => {
  return props.classes_stats.reduce((best, current) => {
    return (!best || current.stats.attendance_rate > best.stats.attendance_rate) ? current : best
  }, null)
}

const getImprovementAreas = () => {
  const poorClasses = props.classes_stats.filter(c => c.stats.attendance_rate < 75)
  if (poorClasses.length === 0) {
    return 'Great job! All your classes have good attendance rates.'
  }
  if (poorClasses.length === 1) {
    return `Focus on improving attendance in ${poorClasses[0].class.subject_code}.`
  }
  return `Focus on improving attendance in ${poorClasses.length} classes with rates below 75%.`
}

const updateDateRange = () => {
  router.visit(route('student.attendance.statistics'), {
    data: {
      start_date: startDate.value,
      end_date: endDate.value,
    },
    preserveState: false,
    preserveScroll: true
  })
}

const exportStatistics = () => {
  router.visit(route('student.attendance.export'), {
    data: {
      start_date: startDate.value,
      end_date: endDate.value,
      format: 'pdf'
    }
  })
}

const goBack = () => {
  router.visit(route('student.attendance.index'))
}
</script>
