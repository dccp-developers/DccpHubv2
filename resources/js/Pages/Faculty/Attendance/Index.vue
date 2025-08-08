<template>
  <FacultyLayout title="Attendance Management">
    <template #header>
      <div class="flex flex-col space-y-2 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0 flex-1">
          <h2 class="text-xl font-semibold text-foreground truncate">
            Attendance Management
          </h2>
          <p class="text-sm text-muted-foreground mt-1">
            Track and manage student attendance across your classes
          </p>
        </div>
        <div class="flex items-center space-x-2 flex-shrink-0">
          <Button @click="showReports = true" variant="outline" size="sm">
            <Icon icon="heroicons:chart-bar" class="w-4 h-4 mr-2" />
            Reports
          </Button>
          <Button @click="showAnalytics = true" variant="outline" size="sm">
            <Icon icon="heroicons:chart-pie" class="w-4 h-4 mr-2" />
            Analytics
          </Button>
        </div>
      </div>
    </template>

    <div class="space-y-6">
      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Total Classes</p>
                <p class="text-2xl font-bold">{{ summary.total_classes }}</p>
              </div>
              <div class="h-8 w-8 bg-primary/10 rounded-lg flex items-center justify-center">
                <Icon icon="heroicons:academic-cap" class="h-4 w-4 text-primary" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Total Students</p>
                <p class="text-2xl font-bold">{{ summary.total_students }}</p>
              </div>
              <div class="h-8 w-8 bg-blue-100 rounded-lg flex items-center justify-center">
                <Icon icon="heroicons:users" class="h-4 w-4 text-blue-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Overall Attendance</p>
                <p class="text-2xl font-bold">{{ summary.overall_stats.attendance_rate }}%</p>
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
                <p class="text-sm font-medium text-muted-foreground">Classes Need Attention</p>
                <p class="text-2xl font-bold text-orange-600">{{ summary.classes_needing_attention }}</p>
              </div>
              <div class="h-8 w-8 bg-orange-100 rounded-lg flex items-center justify-center">
                <Icon icon="heroicons:exclamation-triangle" class="h-4 w-4 text-orange-600" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Attendance Trend Chart -->
      <Card>
        <CardHeader>
          <CardTitle>Attendance Trend (Last 4 Weeks)</CardTitle>
          <CardDescription>Weekly attendance rates across all your classes</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="h-64 flex items-center justify-center">
            <div class="flex space-x-4 w-full">
              <div v-for="week in summary.attendance_trend" :key="week.week" class="flex-1">
                <div class="text-center">
                  <div class="h-32 bg-gray-100 rounded-lg mb-2 flex items-end justify-center p-2">
                    <div 
                      class="bg-primary rounded-t w-full transition-all duration-300"
                      :style="{ height: `${week.attendance_rate}%` }"
                    ></div>
                  </div>
                  <p class="text-xs text-muted-foreground">{{ week.week }}</p>
                  <p class="text-sm font-medium">{{ week.attendance_rate }}%</p>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Classes List -->
      <Card>
        <CardHeader>
          <CardTitle>Your Classes</CardTitle>
          <CardDescription>Manage attendance for each of your classes</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div v-for="classData in classes" :key="classData.class.id" 
                 class="border rounded-lg p-4 hover:bg-muted/50 transition-colors">
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
                  </div>
                  
                  <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                      <p class="text-muted-foreground">Students</p>
                      <p class="font-medium">{{ classData.enrollment_count }}</p>
                    </div>
                    <div>
                      <p class="text-muted-foreground">Attendance Rate</p>
                      <p class="font-medium" :class="getAttendanceRateColor(classData.attendance_stats.attendance_rate)">
                        {{ classData.attendance_stats.attendance_rate }}%
                      </p>
                    </div>
                    <div>
                      <p class="text-muted-foreground">Total Sessions</p>
                      <p class="font-medium">{{ classData.attendance_stats.total }}</p>
                    </div>
                    <div>
                      <p class="text-muted-foreground">Last Session</p>
                      <p class="font-medium">
                        {{ classData.last_session ? formatDate(classData.last_session.date) : 'Never' }}
                      </p>
                    </div>
                  </div>
                </div>

                <div class="flex items-center space-x-2">
                  <Button 
                    @click="goToClassAttendance(classData.class.id)" 
                    size="sm"
                  >
                    <Icon icon="heroicons:clipboard-document-list" class="w-4 h-4 mr-2" />
                    Manage
                  </Button>
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="ghost" size="sm">
                        <Icon icon="heroicons:ellipsis-vertical" class="w-4 h-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem @click="viewClassReports(classData.class.id)">
                        <Icon icon="heroicons:chart-bar" class="w-4 h-4 mr-2" />
                        View Reports
                      </DropdownMenuItem>
                      <DropdownMenuItem @click="exportClassData(classData.class.id)">
                        <Icon icon="heroicons:arrow-down-tray" class="w-4 h-4 mr-2" />
                        Export Data
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Recent Sessions -->
      <Card>
        <CardHeader>
          <CardTitle>Recent Attendance Sessions</CardTitle>
          <CardDescription>Latest attendance records across all classes</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div v-for="session in summary.recent_sessions" :key="`${session.class.id}-${session.date}`"
                 class="flex items-center justify-between p-3 border rounded-lg">
              <div class="flex items-center space-x-3">
                <div class="h-10 w-10 bg-primary/10 rounded-lg flex items-center justify-center">
                  <Icon icon="heroicons:calendar" class="h-5 w-5 text-primary" />
                </div>
                <div>
                  <p class="font-medium">{{ session.class.subject_code }}</p>
                  <p class="text-sm text-muted-foreground">{{ formatDate(session.date) }}</p>
                </div>
              </div>
              <div class="text-right">
                <p class="font-medium">{{ session.stats.attendance_rate }}% Present</p>
                <p class="text-sm text-muted-foreground">
                  {{ session.stats.present_count }}/{{ session.stats.total }} students
                </p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </FacultyLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import FacultyLayout from '@/Layouts/FacultyLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Button } from '@/Components/shadcn/ui/button'
import { Badge } from '@/Components/shadcn/ui/badge'
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuTrigger 
} from '@/Components/shadcn/ui/dropdown-menu'
import { Icon } from '@iconify/vue'

const props = defineProps({
  faculty: Object,
  classes: Array,
  summary: Object,
  attendance_statuses: Array,
})

const showReports = ref(false)
const showAnalytics = ref(false)

const getAttendanceRateColor = (rate) => {
  if (rate >= 90) return 'text-green-600'
  if (rate >= 75) return 'text-yellow-600'
  return 'text-red-600'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const goToClassAttendance = (classId) => {
  router.visit(route('faculty.attendance.class.show', { class: classId }))
}

const viewClassReports = (classId) => {
  router.visit(route('faculty.attendance.reports'), {
    data: { class_id: classId }
  })
}

const exportClassData = (classId) => {
  router.visit(route('faculty.attendance.class.export', { class: classId }), {
    data: {
      start_date: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
      end_date: new Date().toISOString().split('T')[0],
      format: 'csv'
    }
  })
}
</script>
