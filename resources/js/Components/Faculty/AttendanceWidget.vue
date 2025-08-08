<template>
  <Card class="hover:shadow-md transition-shadow duration-200">
    <CardHeader class="pb-3">
      <div class="flex items-center justify-between">
        <div>
          <CardTitle class="text-base">Attendance Overview</CardTitle>
          <CardDescription class="text-xs">Class attendance summary</CardDescription>
        </div>
        <div class="h-8 w-8 bg-blue-100 rounded-lg flex items-center justify-center">
          <Icon icon="heroicons:clipboard-document-list" class="h-4 w-4 text-blue-600" />
        </div>
      </div>
    </CardHeader>
    <CardContent class="pt-0">
      <div class="space-y-4">
        <!-- Overall Stats -->
        <div class="grid grid-cols-2 gap-3 text-center">
          <div class="p-2 bg-green-50 rounded-lg">
            <div class="text-lg font-bold text-green-600">{{ overallStats.attendance_rate }}%</div>
            <div class="text-xs text-green-700">Overall Rate</div>
          </div>
          <div class="p-2 bg-blue-50 rounded-lg">
            <div class="text-lg font-bold text-blue-600">{{ totalClasses }}</div>
            <div class="text-xs text-blue-700">Total Classes</div>
          </div>
        </div>

        <!-- Classes Needing Attention -->
        <div v-if="classesNeedingAttention.length > 0">
          <div class="text-xs font-medium text-muted-foreground mb-2">Classes Needing Attention</div>
          <div class="space-y-1">
            <div v-for="classData in classesNeedingAttention.slice(0, 3)" :key="classData.class.id"
                 class="flex items-center justify-between p-2 bg-red-50 rounded-lg">
              <div class="min-w-0 flex-1">
                <div class="text-xs font-medium text-red-700 truncate">
                  {{ classData.class.subject_code }}
                </div>
                <div class="text-xs text-red-600">
                  {{ classData.attendance_stats.attendance_rate }}% attendance
                </div>
              </div>
              <Badge variant="destructive" class="text-xs">
                Low
              </Badge>
            </div>
          </div>
        </div>

        <!-- Recent Sessions -->
        <div v-if="recentSessions.length > 0">
          <div class="text-xs font-medium text-muted-foreground mb-2">Recent Sessions</div>
          <div class="space-y-1">
            <div v-for="session in recentSessions.slice(0, 3)" :key="`${session.class.id}-${session.date}`"
                 class="flex items-center justify-between text-xs">
              <div class="min-w-0 flex-1">
                <div class="font-medium truncate">{{ session.class.subject_code }}</div>
                <div class="text-muted-foreground">{{ formatDate(session.date) }}</div>
              </div>
              <div class="text-right">
                <div class="font-medium" :class="getAttendanceRateColor(session.stats.attendance_rate)">
                  {{ session.stats.attendance_rate }}%
                </div>
                <div class="text-muted-foreground">
                  {{ session.stats.present_count }}/{{ session.stats.total }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Attendance Trend (Mini Chart) -->
        <div v-if="attendanceTrend.length > 0">
          <div class="text-xs font-medium text-muted-foreground mb-2">Weekly Trend</div>
          <div class="flex items-end justify-between h-12 space-x-1">
            <div v-for="week in attendanceTrend" :key="week.week" class="flex-1 flex flex-col items-center">
              <div 
                class="bg-primary rounded-t w-full transition-all duration-300 mb-1"
                :style="{ height: `${Math.max(week.attendance_rate * 0.4, 2)}px` }"
              ></div>
              <div class="text-xs text-muted-foreground">{{ week.week.slice(-1) }}</div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-2 gap-2">
          <Button @click="viewAttendanceManagement" size="sm" variant="outline" class="text-xs">
            <Icon icon="heroicons:clipboard-document-list" class="w-3 h-3 mr-1" />
            Manage
          </Button>
          <Button @click="viewAttendanceReports" size="sm" variant="outline" class="text-xs">
            <Icon icon="heroicons:chart-bar" class="w-3 h-3 mr-1" />
            Reports
          </Button>
        </div>

        <!-- Students at Risk Alert -->
        <div v-if="studentsAtRisk > 0" class="p-2 bg-orange-50 rounded-lg">
          <div class="flex items-center space-x-2">
            <Icon icon="heroicons:exclamation-triangle" class="h-4 w-4 text-orange-600" />
            <div class="text-xs">
              <div class="font-medium text-orange-700">{{ studentsAtRisk }} students at risk</div>
              <div class="text-orange-600">Below 75% attendance</div>
            </div>
          </div>
        </div>
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
  overallStats: {
    type: Object,
    default: () => ({
      attendance_rate: 0,
      total: 0,
      present_count: 0,
    })
  },
  classesData: {
    type: Array,
    default: () => []
  },
  recentSessions: {
    type: Array,
    default: () => []
  },
  attendanceTrend: {
    type: Array,
    default: () => []
  },
  studentsAtRisk: {
    type: Number,
    default: 0
  }
})

const totalClasses = computed(() => props.classesData.length)

const classesNeedingAttention = computed(() => {
  return props.classesData.filter(classData => 
    classData.attendance_stats && classData.attendance_stats.attendance_rate < 75
  )
})

const getAttendanceRateColor = (rate) => {
  if (rate >= 90) return 'text-green-600'
  if (rate >= 75) return 'text-yellow-600'
  return 'text-red-600'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric'
  })
}

const viewAttendanceManagement = () => {
  router.visit(route('faculty.attendance.index'))
}

const viewAttendanceReports = () => {
  router.visit(route('faculty.attendance.reports'))
}
</script>
