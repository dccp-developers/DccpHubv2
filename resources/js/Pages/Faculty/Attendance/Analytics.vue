<template>
  <FacultyLayout title="Attendance Analytics">
    <template #header>
      <div class="flex flex-col space-y-2 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0 flex-1">
          <h2 class="text-xl font-semibold text-foreground truncate">
            Attendance Analytics
          </h2>
          <p class="text-sm text-muted-foreground mt-1">
            Analyze attendance patterns and trends across your classes
          </p>
        </div>
        <div class="flex items-center space-x-2 flex-shrink-0">
          <Button @click="refreshData" :disabled="isLoading">
            <Icon icon="heroicons:arrow-path" class="w-4 h-4 mr-2" :class="{ 'animate-spin': isLoading }" />
            Refresh
          </Button>
          <Button @click="goBack" variant="outline" size="sm">
            <Icon icon="heroicons:arrow-left" class="w-4 h-4 mr-2" />
            Back
          </Button>
        </div>
      </div>
    </template>

    <div class="space-y-6">
      <!-- Filter Controls -->
      <Card>
        <CardHeader>
          <CardTitle>Analytics Filters</CardTitle>
          <CardDescription>Customize your analytics view</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <Label>Time Period</Label>
              <Select v-model="timePeriod" @update:value="updateAnalytics">
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="week">Last Week</SelectItem>
                  <SelectItem value="month">Last Month</SelectItem>
                  <SelectItem value="semester">This Semester</SelectItem>
                  <SelectItem value="year">This Year</SelectItem>
                </SelectContent>
              </Select>
            </div>
            
            <div>
              <Label>Class Filter</Label>
              <Select v-model="selectedClassFilter" @update:value="updateAnalytics">
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
            
            <div>
              <Label>View Type</Label>
              <Select v-model="viewType" @update:value="updateAnalytics">
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="overview">Overview</SelectItem>
                  <SelectItem value="trends">Trends</SelectItem>
                  <SelectItem value="patterns">Patterns</SelectItem>
                  <SelectItem value="comparison">Comparison</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Key Metrics -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Average Attendance</p>
                <p class="text-2xl font-bold" :class="getAttendanceRateColor(analytics.averageAttendance)">
                  {{ analytics.averageAttendance }}%
                </p>
                <p class="text-xs text-muted-foreground">
                  <span :class="analytics.attendanceTrend > 0 ? 'text-green-600' : 'text-red-600'">
                    {{ analytics.attendanceTrend > 0 ? '+' : '' }}{{ analytics.attendanceTrend }}%
                  </span>
                  vs last period
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
                <p class="text-sm font-medium text-muted-foreground">Best Performing Class</p>
                <p class="text-lg font-bold">{{ analytics.bestClass?.subject_code || 'N/A' }}</p>
                <p class="text-xs text-muted-foreground">
                  {{ analytics.bestClass?.attendance_rate || 0 }}% attendance
                </p>
              </div>
              <div class="h-8 w-8 bg-blue-100 rounded-lg flex items-center justify-center">
                <Icon icon="heroicons:trophy" class="h-4 w-4 text-blue-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Students at Risk</p>
                <p class="text-2xl font-bold text-red-600">{{ analytics.studentsAtRisk }}</p>
                <p class="text-xs text-muted-foreground">
                  Below 75% attendance
                </p>
              </div>
              <div class="h-8 w-8 bg-red-100 rounded-lg flex items-center justify-center">
                <Icon icon="heroicons:exclamation-triangle" class="h-4 w-4 text-red-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Total Sessions</p>
                <p class="text-2xl font-bold">{{ analytics.totalSessions }}</p>
                <p class="text-xs text-muted-foreground">
                  Across all classes
                </p>
              </div>
              <div class="h-8 w-8 bg-purple-100 rounded-lg flex items-center justify-center">
                <Icon icon="heroicons:calendar" class="h-4 w-4 text-purple-600" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Attendance Trends Chart -->
      <Card>
        <CardHeader>
          <CardTitle>Attendance Trends</CardTitle>
          <CardDescription>Weekly attendance rates over time</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="h-64 flex items-center justify-center">
            <div class="w-full">
              <!-- Simple bar chart representation -->
              <div class="flex items-end justify-between h-48 space-x-2">
                <div v-for="(week, index) in analytics.weeklyTrends" :key="index" class="flex-1 flex flex-col items-center">
                  <div 
                    class="bg-primary rounded-t w-full transition-all duration-300 mb-2"
                    :style="{ height: `${week.attendance_rate * 2}px` }"
                  ></div>
                  <div class="text-center">
                    <p class="text-xs text-muted-foreground">{{ week.week }}</p>
                    <p class="text-sm font-medium">{{ week.attendance_rate }}%</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Class Performance Comparison -->
      <Card>
        <CardHeader>
          <CardTitle>Class Performance Comparison</CardTitle>
          <CardDescription>Compare attendance rates across your classes</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div v-for="classData in classes" :key="classData.class.id" class="flex items-center justify-between p-4 border rounded-lg">
              <div class="flex items-center space-x-4">
                <div>
                  <h3 class="font-semibold">{{ classData.class.subject_code }}</h3>
                  <p class="text-sm text-muted-foreground">
                    {{ classData.class.Subject?.title || classData.class.ShsSubject?.title }}
                  </p>
                </div>
                <Badge :variant="classData.attendance_stats.attendance_rate >= 75 ? 'success' : 'destructive'">
                  {{ classData.attendance_stats.attendance_rate }}%
                </Badge>
              </div>
              
              <div class="flex items-center space-x-4">
                <!-- Progress bar -->
                <div class="w-32 h-2 bg-gray-200 rounded-full overflow-hidden">
                  <div 
                    class="h-full transition-all duration-300"
                    :class="classData.attendance_stats.attendance_rate >= 75 ? 'bg-green-500' : 'bg-red-500'"
                    :style="{ width: `${classData.attendance_stats.attendance_rate}%` }"
                  ></div>
                </div>
                
                <div class="text-right text-sm">
                  <p class="font-medium">{{ classData.enrollment_count }} students</p>
                  <p class="text-muted-foreground">{{ classData.attendance_stats.total }} sessions</p>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Attendance Patterns -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Day of Week Analysis</CardTitle>
            <CardDescription>Attendance patterns by day</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-for="day in analytics.dayPatterns" :key="day.day" class="flex items-center justify-between">
                <span class="text-sm font-medium">{{ day.day }}</span>
                <div class="flex items-center space-x-2">
                  <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div 
                      class="h-full bg-primary transition-all duration-300"
                      :style="{ width: `${day.attendance_rate}%` }"
                    ></div>
                  </div>
                  <span class="text-sm text-muted-foreground w-12 text-right">{{ day.attendance_rate }}%</span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Time of Day Analysis</CardTitle>
            <CardDescription>Attendance patterns by time</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-for="time in analytics.timePatterns" :key="time.period" class="flex items-center justify-between">
                <span class="text-sm font-medium">{{ time.period }}</span>
                <div class="flex items-center space-x-2">
                  <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div 
                      class="h-full bg-primary transition-all duration-300"
                      :style="{ width: `${time.attendance_rate}%` }"
                    ></div>
                  </div>
                  <span class="text-sm text-muted-foreground w-12 text-right">{{ time.attendance_rate }}%</span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Students Needing Attention -->
      <Card>
        <CardHeader>
          <CardTitle>Students Needing Attention</CardTitle>
          <CardDescription>Students with attendance below 75%</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div v-for="student in analytics.studentsNeedingAttention" :key="student.id" 
                 class="flex items-center justify-between p-3 border rounded-lg">
              <div class="flex items-center space-x-3">
                <Avatar class="h-8 w-8">
                  <AvatarImage :src="student.profile_url" />
                  <AvatarFallback>
                    {{ getStudentInitials(student.name) }}
                  </AvatarFallback>
                </Avatar>
                <div>
                  <p class="font-medium">{{ student.name }}</p>
                  <p class="text-sm text-muted-foreground">{{ student.student_id }}</p>
                </div>
              </div>
              
              <div class="flex items-center space-x-4">
                <div class="text-right">
                  <p class="font-medium text-red-600">{{ student.attendance_rate }}%</p>
                  <p class="text-sm text-muted-foreground">{{ student.class_name }}</p>
                </div>
                <Button size="sm" variant="outline" @click="contactStudent(student)">
                  <Icon icon="heroicons:envelope" class="w-4 h-4 mr-2" />
                  Contact
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </FacultyLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import FacultyLayout from '@/Layouts/FacultyLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Button } from '@/Components/shadcn/ui/button'
import { Badge } from '@/Components/shadcn/ui/badge'
import { Label } from '@/Components/shadcn/ui/label'
import { Avatar, AvatarImage, AvatarFallback } from '@/Components/shadcn/ui/avatar'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/shadcn/ui/select'
import { Icon } from '@iconify/vue'
import { toast } from 'vue-sonner'

const props = defineProps({
  faculty: Object,
  classes: Array,
  summary: Object,
})

// Reactive data
const timePeriod = ref('month')
const selectedClassFilter = ref('')
const viewType = ref('overview')
const isLoading = ref(false)

// Mock analytics data - replace with actual API data
const analytics = ref({
  averageAttendance: 82,
  attendanceTrend: 3.2,
  bestClass: {
    subject_code: 'CS101',
    attendance_rate: 95
  },
  studentsAtRisk: 8,
  totalSessions: 45,
  weeklyTrends: [
    { week: 'Week 1', attendance_rate: 78 },
    { week: 'Week 2', attendance_rate: 82 },
    { week: 'Week 3', attendance_rate: 85 },
    { week: 'Week 4', attendance_rate: 80 },
    { week: 'Week 5', attendance_rate: 88 },
  ],
  dayPatterns: [
    { day: 'Monday', attendance_rate: 85 },
    { day: 'Tuesday', attendance_rate: 88 },
    { day: 'Wednesday', attendance_rate: 82 },
    { day: 'Thursday', attendance_rate: 79 },
    { day: 'Friday', attendance_rate: 75 },
  ],
  timePatterns: [
    { period: 'Morning (8-12)', attendance_rate: 88 },
    { period: 'Afternoon (1-5)', attendance_rate: 82 },
    { period: 'Evening (6-9)', attendance_rate: 75 },
  ],
  studentsNeedingAttention: [
    {
      id: 1,
      name: 'John Doe',
      student_id: '2024001',
      attendance_rate: 65,
      class_name: 'CS101',
      profile_url: null
    },
    {
      id: 2,
      name: 'Jane Smith',
      student_id: '2024002',
      attendance_rate: 70,
      class_name: 'MATH201',
      profile_url: null
    }
  ]
})

const getAttendanceRateColor = (rate) => {
  if (rate >= 90) return 'text-green-600'
  if (rate >= 75) return 'text-yellow-600'
  return 'text-red-600'
}

const getStudentInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const updateAnalytics = () => {
  // Implementation for updating analytics based on filters
  isLoading.value = true
  setTimeout(() => {
    isLoading.value = false
    toast.success('Analytics updated')
  }, 1000)
}

const refreshData = () => {
  updateAnalytics()
}

const contactStudent = (student) => {
  toast.success(`Contacting ${student.name}`)
}

const goBack = () => {
  router.visit(route('faculty.attendance.index'))
}
</script>
