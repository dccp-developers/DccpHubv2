<template>
  <FacultyLayout title="Faculty Dashboard">
    <template #header>
      <div class="flex flex-col space-y-2 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0 flex-1">
          <h2 class="text-xl font-semibold text-foreground truncate">
            Good {{ getGreeting() }}, {{ faculty.name }}
          </h2>
          <p class="text-sm text-muted-foreground mt-1">
            {{ getCurrentDate() }}
          </p>
        </div>
        <div class="flex items-center space-x-2 flex-shrink-0">
          <Badge variant="success" class="hidden sm:flex items-center">
            <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
            Active
          </Badge>
        </div>
      </div>
    </template>

    <!-- Main Dashboard Content -->
    <div class="space-y-4 md:space-y-6">
      <!-- Semester Info Card (Mobile) -->
      <Card class="sm:hidden">
        <CardContent class="p-4">
          <div class="text-center">
            <p class="text-sm font-medium text-foreground">{{ props.currentSemester }} Semester {{ props.schoolYear }}</p>
            <div class="flex items-center justify-center mt-2 space-x-4">
              <Badge variant="success" class="text-xs">
                <div class="w-2 h-2 bg-green-500 rounded-full mr-1"></div>
                Active
              </Badge>
              <span class="text-xs text-muted-foreground">{{ classes.length }} Classes</span>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Welcome Banner -->
      <Card class="bg-gradient-to-r from-primary to-primary/80 text-primary-foreground">
        <CardContent class="p-4 md:p-6">
          <div class="flex flex-col space-y-3 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h3 class="text-lg font-semibold">Welcome to your Faculty Dashboard</h3>
              <p class="text-primary-foreground/80 mt-1 text-sm">Manage your classes, students, and academic activities</p>
            </div>
            <div class="flex space-x-2">
              <Button variant="secondary" size="sm" class="text-xs" @click="showTeachingGuide = true">
                <BookOpenIcon class="w-4 h-4 mr-2" />
                Teaching Guide
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Stats Overview -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
        <Card v-for="stat in stats" :key="stat.label" class="hover:shadow-md transition-shadow">
          <CardContent class="p-3 md:p-4">
            <div class="flex flex-col space-y-2">
              <div class="flex items-center justify-between">
                <div :class="`w-8 h-8 md:w-10 md:h-10 rounded-lg bg-${stat.color}-100 dark:bg-${stat.color}-900/20 flex items-center justify-center`">
                  <component :is="getStatIcon(stat.label)" :class="`w-4 h-4 md:w-5 md:h-5 text-${stat.color}-600 dark:text-${stat.color}-400`" />
                </div>
                <Badge variant="outline" class="text-xs hidden md:flex">
                  +12%
                </Badge>
              </div>
              <div>
                <p class="text-lg md:text-2xl font-bold text-foreground">{{ stat.value }}</p>
                <p class="text-xs md:text-sm font-medium text-muted-foreground">{{ stat.label }}</p>
              </div>
              <div class="md:hidden">
                <div class="flex items-center text-xs">
                  <ArrowTrendingUpIcon class="w-3 h-3 text-green-500 mr-1" />
                  <span class="text-green-600 font-medium">+12%</span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
        <!-- Today's Schedule -->
        <div class="lg:col-span-1">
          <Card>
            <CardHeader class="pb-3">
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle class="text-base md:text-lg">Today's Schedule</CardTitle>
                  <CardDescription class="text-xs md:text-sm">{{ getCurrentDateShort() }}</CardDescription>
                </div>
                <Button size="sm" variant="outline" class="hidden md:flex" @click="viewAllSchedule">
                  <CalendarIcon class="w-4 h-4 mr-2" />
                  View All
                </Button>
              </div>
            </CardHeader>
            <CardContent class="pt-0">
              <div v-if="todaysSchedule.length === 0" class="text-center py-6 md:py-8">
                <CalendarIcon class="mx-auto h-8 w-8 md:h-12 md:w-12 text-muted-foreground" />
                <h3 class="mt-2 text-sm font-medium text-foreground">No classes today</h3>
                <p class="mt-1 text-xs md:text-sm text-muted-foreground">Enjoy your free day!</p>
              </div>
              <div v-else class="space-y-2 md:space-y-3">
                <div
                  v-for="schedule in todaysSchedule"
                  :key="schedule.id"
                  class="flex items-center p-2 md:p-3 rounded-lg border border-border hover:bg-accent transition-colors cursor-pointer"
                  @click="viewScheduleDetails(schedule)"
                >
                  <div class="flex-shrink-0">
                    <div :class="`w-3 h-3 rounded-full bg-${schedule.color}`"></div>
                  </div>
                  <div class="ml-3 flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                      <p class="text-sm font-medium text-foreground truncate">
                        {{ schedule.subject_code }}
                      </p>
                      <Badge variant="secondary" class="text-xs ml-2">
                        {{ getTimeStatus(schedule) }}
                      </Badge>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">
                      {{ schedule.start_time }} - {{ schedule.end_time }}
                      <span v-if="schedule.duration" class="ml-2">({{ schedule.duration }})</span>
                    </p>
                    <p class="text-xs text-muted-foreground">
                      Room {{ schedule.room }} • Sec {{ schedule.section }}
                      <span v-if="schedule.student_count" class="ml-2">• {{ schedule.student_count }} students</span>
                    </p>
                  </div>
                  <ChevronRightIcon class="w-4 h-4 text-muted-foreground ml-2" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Classes Overview -->
        <div class="lg:col-span-2">
          <Card>
            <CardHeader class="pb-3">
              <div class="flex flex-col space-y-2 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <CardTitle class="text-base md:text-lg">My Classes</CardTitle>
                  <CardDescription class="text-xs md:text-sm">{{ classes.length }} classes this semester</CardDescription>
                </div>
                <div class="flex space-x-2">
                  <Button size="sm" variant="outline" class="hidden md:flex">
                    <FunnelIcon class="w-4 h-4 mr-2" />
                    Filter
                  </Button>
                </div>
              </div>
            </CardHeader>
            <CardContent class="pt-0">
              <div v-if="classes.length === 0" class="text-center py-6 md:py-8">
                <BookOpenIcon class="mx-auto h-8 w-8 md:h-12 md:w-12 text-muted-foreground" />
                <h3 class="mt-2 text-sm font-medium text-foreground">No classes assigned</h3>
                <p class="mt-1 text-xs md:text-sm text-muted-foreground">Contact administration for class assignments.</p>
              </div>
              <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
                <Card
                  v-for="classItem in classes"
                  :key="classItem.id"
                  class="hover:shadow-md transition-all cursor-pointer border-l-4"
                  :class="`border-l-${classItem.color}`"
                  @click="viewClassDetails(classItem)"
                >
                  <CardContent class="p-3 md:p-4">
                    <div class="flex items-start justify-between mb-3">
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-2 mb-1">
                          <h4 class="text-sm font-semibold text-foreground truncate">
                            {{ classItem.subject_code }}
                          </h4>
                          <Badge variant="secondary" class="text-xs">
                            Sec {{ classItem.section }}
                          </Badge>
                          <Badge
                            :variant="classItem.classification === 'college' ? 'default' : 'outline'"
                            class="text-xs"
                          >
                            {{ classItem.classification === 'college' ? 'College' : 'SHS' }}
                          </Badge>
                        </div>
                        <p class="text-xs text-muted-foreground line-clamp-2 mb-2">
                          {{ classItem.subject_title }}
                        </p>
                        <div class="flex items-center space-x-3 text-xs text-muted-foreground">
                          <div class="flex items-center">
                            <UsersIcon class="w-3 h-3 mr-1" />
                            {{ classItem.student_count || 0 }} students
                          </div>
                          <div class="flex items-center">
                            <MapPinIcon class="w-3 h-3 mr-1" />
                            {{ classItem.room || 'TBA' }}
                          </div>
                          <div class="flex items-center">
                            <ClockIcon class="w-3 h-3 mr-1" />
                            {{ classItem.units || 3 }} units
                          </div>
                        </div>
                      </div>
                      <div class="flex flex-col items-end space-y-2 ml-2">
                        <div :class="`w-3 h-3 rounded-full bg-${classItem.color}`"></div>
                        <Button size="sm" variant="ghost" class="h-6 w-6 p-0">
                          <EllipsisVerticalIcon class="w-4 h-4" />
                        </Button>
                      </div>
                    </div>

                    <!-- Quick Actions for Class -->
                    <div class="grid grid-cols-3 gap-1 md:gap-2 pt-2 border-t border-border">
                      <Button size="sm" variant="outline" class="text-xs p-1 md:p-2">
                        <ClipboardDocumentListIcon class="w-3 h-3 md:mr-1" />
                        <span class="hidden md:inline">Attend</span>
                      </Button>
                      <Button size="sm" variant="outline" class="text-xs p-1 md:p-2">
                        <ChartBarIcon class="w-3 h-3 md:mr-1" />
                        <span class="hidden md:inline">Grades</span>
                      </Button>
                      <Button size="sm" variant="outline" class="text-xs p-1 md:p-2">
                        <DocumentTextIcon class="w-3 h-3 md:mr-1" />
                        <span class="hidden md:inline">Assign</span>
                      </Button>
                    </div>
                  </CardContent>
                </Card>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Weekly Schedule Overview -->
      <Card>
        <CardContent class="p-6">
          <WeeklySchedule
            :weekly-schedule="weeklySchedule"
            :schedule-overview="scheduleOverview"
            :current-semester="currentSemester"
            :school-year="schoolYear"
            @schedule-click="viewScheduleDetails"
            @view-full-calendar="viewFullCalendar"
          />
        </CardContent>
      </Card>

      <!-- Additional Faculty Features -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Attendance Widget -->
        <div>
          <AttendanceWidget
            :overallStats="attendanceOverallStats"
            :classesData="classes"
            :recentSessions="recentAttendanceSessions"
            :attendanceTrend="attendanceTrend"
            :studentsAtRisk="studentsAtRisk"
          />
        </div>

        <!-- Recent Activities -->
        <div class="lg:col-span-2">
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <CardTitle class="text-lg">Recent Activities</CardTitle>
                <Button size="sm" variant="outline">
                  View All
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div
                  v-for="activity in recentActivities"
                  :key="activity.id"
                  class="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors"
                >
                  <div class="flex-shrink-0">
                    <div :class="`w-8 h-8 rounded-full flex items-center justify-center ${getActivityColor(activity.type)}`">
                      <component :is="getActivityIcon(activity.type)" class="w-4 h-4" />
                    </div>
                  </div>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900">{{ activity.description }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ activity.timestamp }}</p>
                  </div>
                  <Badge variant="secondary" class="text-xs">
                    {{ activity.type.replace('_', ' ') }}
                  </Badge>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Quick Actions & Tools -->
        <div>
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Quick Actions</CardTitle>
              <CardDescription>Frequently used tools</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="grid grid-cols-1 gap-3">
                <Button
                  v-for="action in quickActions"
                  :key="action.name"
                  @click="action.action"
                  variant="outline"
                  class="justify-start h-auto p-4"
                >
                  <component :is="action.icon" class="w-5 h-5 mr-3 text-blue-500" />
                  <div class="text-left">
                    <div class="font-medium">{{ action.name }}</div>
                    <div class="text-xs text-gray-500">{{ action.description }}</div>
                  </div>
                </Button>
              </div>
            </CardContent>
          </Card>

          <!-- Upcoming Deadlines -->
          <Card class="mt-6">
            <CardHeader>
              <CardTitle class="text-lg">Upcoming Deadlines</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-3">
                <div
                  v-for="deadline in upcomingDeadlines"
                  :key="deadline.id"
                  class="flex items-center justify-between p-3 rounded-lg border border-gray-200"
                >
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ deadline.title }}</p>
                    <p class="text-xs text-gray-500">{{ deadline.class }}</p>
                  </div>
                  <Badge :variant="deadline.urgent ? 'destructive' : 'secondary'" class="text-xs">
                    {{ deadline.daysLeft }} days
                  </Badge>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Performance Analytics -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle class="text-lg">Performance Analytics</CardTitle>
              <CardDescription>Overview of your teaching metrics</CardDescription>
            </div>
            <div class="flex space-x-2">
              <Button size="sm" variant="outline">
                <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
                Export
              </Button>
              <Button size="sm" variant="outline">
                <ChartBarIcon class="w-4 h-4 mr-2" />
                Detailed Report
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
              <div class="text-2xl font-bold text-blue-600">94%</div>
              <div class="text-sm text-gray-600">Average Attendance</div>
              <div class="text-xs text-green-600 mt-1">+2% from last month</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-green-600">87%</div>
              <div class="text-sm text-gray-600">Assignment Completion</div>
              <div class="text-xs text-green-600 mt-1">+5% from last month</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-purple-600">4.8</div>
              <div class="text-sm text-gray-600">Student Rating</div>
              <div class="text-xs text-green-600 mt-1">+0.2 from last semester</div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Teaching Guide Modal -->
    <TeachingGuide
      :open="showTeachingGuide"
      @update:open="showTeachingGuide = $event"
    />
  </FacultyLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import FacultyLayout from '@/Layouts/FacultyLayout.vue'
import WeeklySchedule from '@/Components/Faculty/WeeklySchedule.vue'
import TeachingGuide from '@/Components/Faculty/TeachingGuide.vue'
import AttendanceWidget from '@/Components/Faculty/AttendanceWidget.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Button } from '@/Components/shadcn/ui/button'
import { Badge } from '@/Components/shadcn/ui/badge'
import {
  CalendarIcon,
  BookOpenIcon,
  CheckIcon,
  AcademicCapIcon,
  UsersIcon,
  ClockIcon,
  ChartBarIcon,
  DocumentTextIcon,
  UserGroupIcon,
  ClipboardDocumentListIcon,
  ChevronRightIcon,
  EllipsisVerticalIcon,
  MapPinIcon,
  FunnelIcon,
  ArrowTrendingUpIcon,
  ArrowDownTrayIcon,
  ExclamationTriangleIcon,
  BellIcon,
  CogIcon
} from '@heroicons/vue/24/outline'

// Props from the controller
const props = defineProps({
  faculty: Object,
  stats: Array,
  classes: Array,
  todaysSchedule: Array,
  weeklySchedule: Object,
  classEnrollments: Array,
  recentActivities: Array,
  upcomingDeadlines: Array,
  performanceMetrics: Object,
  currentSemester: String,
  schoolYear: String,
  scheduleOverview: Object,
  error: String,
  attendanceData: {
    type: Object,
    default: () => ({
      overallStats: { attendance_rate: 0, total: 0, present_count: 0 },
      recentSessions: [],
      attendanceTrend: [],
      studentsAtRisk: 0
    })
  }
})

// Computed properties
const stats = computed(() => props.stats || [])
const classes = computed(() => props.classes || [])
const todaysSchedule = computed(() => props.todaysSchedule || [])
const weeklySchedule = computed(() => props.weeklySchedule || {})
const classEnrollments = computed(() => props.classEnrollments || [])
const recentActivities = computed(() => props.recentActivities || [])
const upcomingDeadlines = computed(() => props.upcomingDeadlines || [])
const performanceMetrics = computed(() => props.performanceMetrics || {})
const scheduleOverview = computed(() => props.scheduleOverview || {})

// Attendance data
const attendanceOverallStats = computed(() => props.attendanceData?.overallStats || { attendance_rate: 0, total: 0, present_count: 0 })
const recentAttendanceSessions = computed(() => props.attendanceData?.recentSessions || [])
const attendanceTrend = computed(() => props.attendanceData?.attendanceTrend || [])
const studentsAtRisk = computed(() => props.attendanceData?.studentsAtRisk || 0)

// Error handling
const hasError = computed(() => !!props.error)

// Reactive state
const showTeachingGuide = ref(false)

// Days of the week for schedule display
const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']

// Get formatted weekly schedule
const formattedWeeklySchedule = computed(() => {
  const schedule = {}
  daysOfWeek.forEach(day => {
    schedule[day] = weeklySchedule.value[day] || []
  })
  return schedule
})

// Enhanced quick actions with descriptions
const quickActions = ref([
  {
    name: 'Take Attendance',
    description: 'Record student attendance',
    icon: ClipboardDocumentListIcon,
    action: () => console.log('Take Attendance')
  },
  {
    name: 'View Grades',
    description: 'View student grades',
    icon: ChartBarIcon,
    action: () => console.log('View Grades')
  },
  {
    name: 'View Schedule',
    description: 'View your teaching schedule',
    icon: CalendarIcon,
    action: () => viewAllSchedule()
  },
  {
    name: 'View Students',
    description: 'View your students',
    icon: UsersIcon,
    action: () => router.visit(route('faculty.students.index'))
  }
])

// Methods
const getStatIcon = (label) => {
  const iconMap = {
    'Total Classes': BookOpenIcon,
    'Total Students': UsersIcon,
    'Weekly Schedules': ClockIcon,
    'Avg. Class Size': ChartBarIcon
  }
  return iconMap[label] || AcademicCapIcon
}

const getCurrentDate = () => {
  return new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const getCurrentDateShort = () => {
  return new Date().toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric'
  })
}

const getCurrentDay = () => {
  return new Date().toLocaleDateString('en-US', { weekday: 'long' })
}

const getGreeting = () => {
  const hour = new Date().getHours()
  if (hour < 12) return 'morning'
  if (hour < 17) return 'afternoon'
  return 'evening'
}

const getTimeStatus = (schedule) => {
  const now = new Date()
  const currentTime = now.getHours() * 60 + now.getMinutes()
  const startTime = parseInt(schedule.start_time.split(':')[0]) * 60 + parseInt(schedule.start_time.split(':')[1])
  const endTime = parseInt(schedule.end_time.split(':')[0]) * 60 + parseInt(schedule.end_time.split(':')[1])

  if (currentTime < startTime) return 'Upcoming'
  if (currentTime >= startTime && currentTime <= endTime) return 'In Progress'
  return 'Completed'
}

const getActivityIcon = (type) => {
  const iconMap = {
    'grade_submitted': CheckIcon,
    'attendance_recorded': ClipboardDocumentListIcon,
    'assignment_created': DocumentTextIcon,
    'schedule_updated': CalendarIcon,
    'student_message': UserGroupIcon
  }
  return iconMap[type] || CheckIcon
}

const getActivityColor = (type) => {
  const colorMap = {
    'grade_submitted': 'bg-green-100 text-green-600',
    'attendance_recorded': 'bg-blue-100 text-blue-600',
    'assignment_created': 'bg-purple-100 text-purple-600',
    'schedule_updated': 'bg-amber-100 text-amber-600',
    'student_message': 'bg-pink-100 text-pink-600'
  }
  return colorMap[type] || 'bg-gray-100 text-gray-600'
}

const viewClassDetails = (classItem) => {
  router.visit(route('faculty.classes.show', { class: classItem.id }))
}

const viewScheduleDetails = (schedule) => {
  console.log('View schedule details:', schedule)
  // TODO: Navigate to schedule details page
}

const viewFullCalendar = () => {
  router.visit(route('faculty.schedule.index'))
}

const viewAllSchedule = () => {
  router.visit(route('faculty.schedule.index'))
}
</script>
