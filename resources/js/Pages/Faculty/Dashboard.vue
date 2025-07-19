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
            <p class="text-sm font-medium text-foreground">{{ currentSemester }} Semester {{ schoolYear }}</p>
            <div class="flex items-center justify-center mt-2 space-x-4">
              <Badge variant="success" class="text-xs">
                <div class="w-2 h-2 bg-green-500 rounded-full mr-1"></div>
                Active
              </Badge>
              <span class="text-xs text-muted-foreground">6 Classes</span>
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
              <Button variant="secondary" size="sm" class="text-xs">
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
                <Button size="sm" variant="outline" class="hidden md:flex">
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
                <Button size="sm" variant="outline" class="mt-3 md:mt-4">
                  <PlusIcon class="w-4 h-4 mr-2" />
                  Office Hours
                </Button>
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
                    </p>
                    <p class="text-xs text-muted-foreground">
                      Room {{ schedule.room }} • Sec {{ schedule.section }}
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
                  <Button size="sm" class="text-xs">
                    <PlusIcon class="w-4 h-4 mr-2" />
                    Add
                  </Button>
                </div>
              </div>
            </CardHeader>
            <CardContent class="pt-0">
              <div v-if="classes.length === 0" class="text-center py-6 md:py-8">
                <BookOpenIcon class="mx-auto h-8 w-8 md:h-12 md:w-12 text-muted-foreground" />
                <h3 class="mt-2 text-sm font-medium text-foreground">No classes assigned</h3>
                <p class="mt-1 text-xs md:text-sm text-muted-foreground">Contact administration for class assignments.</p>
                <Button size="sm" variant="outline" class="mt-3 md:mt-4">
                  Request Assignment
                </Button>
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
                        </div>
                        <p class="text-xs text-muted-foreground line-clamp-2 mb-2">
                          {{ classItem.subject_title }}
                        </p>
                        <div class="flex items-center space-x-3 text-xs text-muted-foreground">
                          <div class="flex items-center">
                            <UsersIcon class="w-3 h-3 mr-1" />
                            {{ classItem.student_count }}
                          </div>
                          <div class="flex items-center">
                            <MapPinIcon class="w-3 h-3 mr-1" />
                            {{ classItem.room }}
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

      <!-- Additional Faculty Features -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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
  </FacultyLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import FacultyLayout from '@/Layouts/FacultyLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card.js'
import { Button } from '@/Components/ui/button.js'
import { Badge } from '@/Components/ui/badge.js'
import {
  CalendarIcon,
  BookOpenIcon,
  CheckIcon,
  AcademicCapIcon,
  UsersIcon,
  ClockIcon,
  ChartBarIcon,
  PlusIcon,
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
  recentActivities: Array,
  user: Object,
  semester: Number,
  schoolYear: String,
  generalSettings: Object
})

// Computed properties
const currentSemester = computed(() => {
  const semesterNames = {
    1: '1st',
    2: '2nd',
    3: 'Summer'
  }
  return semesterNames[props.semester] || '1st'
})

// Enhanced sample data with colors and trends
const sampleStats = ref([
  {
    label: 'Total Classes',
    value: 6,
    description: 'Classes you are teaching this semester',
    color: 'blue',
    trend: '+1 from last semester'
  },
  {
    label: 'Total Students',
    value: 180,
    description: 'Students enrolled in your classes',
    color: 'green',
    trend: '+12% from last semester'
  },
  {
    label: 'Weekly Schedules',
    value: 18,
    description: 'Your weekly class schedules',
    color: 'purple',
    trend: 'Same as last semester'
  },
  {
    label: 'Avg. Class Size',
    value: 30,
    description: 'Average students per class',
    color: 'amber',
    trend: '+2 students from last semester'
  }
])

const sampleTodaysSchedule = ref([
  {
    id: 1,
    subject_code: 'CS101',
    subject_title: 'Introduction to Computer Science',
    start_time: '08:00',
    end_time: '09:30',
    room: '201',
    section: 'A',
    color: 'blue-500'
  },
  {
    id: 2,
    subject_code: 'MATH201',
    subject_title: 'Calculus II',
    start_time: '10:00',
    end_time: '11:30',
    room: '105',
    section: 'B',
    color: 'green-500'
  },
  {
    id: 3,
    subject_code: 'CS102',
    subject_title: 'Data Structures',
    start_time: '14:00',
    end_time: '15:30',
    room: '301',
    section: 'A',
    color: 'purple-500'
  }
])

const sampleClasses = ref([
  {
    id: 1,
    subject_code: 'CS101',
    subject_title: 'Introduction to Computer Science',
    section: 'A',
    room: '201',
    student_count: 35,
    color: 'blue-500'
  },
  {
    id: 2,
    subject_code: 'CS102',
    subject_title: 'Data Structures and Algorithms',
    section: 'A',
    room: '301',
    student_count: 28,
    color: 'purple-500'
  },
  {
    id: 3,
    subject_code: 'MATH201',
    subject_title: 'Calculus II',
    section: 'B',
    room: '105',
    student_count: 42,
    color: 'green-500'
  },
  {
    id: 4,
    subject_code: 'CS201',
    subject_title: 'Database Systems',
    section: 'C',
    room: '205',
    student_count: 25,
    color: 'amber-500'
  }
])

const sampleActivities = ref([
  {
    id: 1,
    type: 'grade_submitted',
    description: 'Grades submitted for CS101 Midterm Exam',
    timestamp: '2 hours ago'
  },
  {
    id: 2,
    type: 'attendance_recorded',
    description: 'Attendance recorded for MATH201 Section B',
    timestamp: '5 hours ago'
  },
  {
    id: 3,
    type: 'assignment_created',
    description: 'New assignment created for CS102 - Data Structures',
    timestamp: '1 day ago'
  },
  {
    id: 4,
    type: 'schedule_updated',
    description: 'Schedule updated for next week',
    timestamp: '2 days ago'
  },
  {
    id: 5,
    type: 'student_message',
    description: 'New message from student about CS301 project',
    timestamp: '3 hours ago'
  }
])

// Upcoming deadlines
const upcomingDeadlines = ref([
  {
    id: 1,
    title: 'Midterm Grades Due',
    class: 'CS101 - Section A',
    daysLeft: 3,
    urgent: true
  },
  {
    id: 2,
    title: 'Assignment Review',
    class: 'MATH201 - Section B',
    daysLeft: 7,
    urgent: false
  },
  {
    id: 3,
    title: 'Final Project Submission',
    class: 'CS301 - Section A',
    daysLeft: 14,
    urgent: false
  }
])

// Use sample data if props are empty
const stats = computed(() => props.stats?.length ? props.stats : sampleStats.value)
const todaysSchedule = computed(() => props.todaysSchedule?.length ? props.todaysSchedule : sampleTodaysSchedule.value)
const classes = computed(() => props.classes?.length ? props.classes : sampleClasses.value)
const recentActivities = computed(() => props.recentActivities?.length ? props.recentActivities : sampleActivities.value)

// Enhanced quick actions with descriptions
const quickActions = ref([
  {
    name: 'Take Attendance',
    description: 'Record student attendance',
    icon: ClipboardDocumentListIcon,
    action: () => console.log('Take Attendance')
  },
  {
    name: 'Grade Assignment',
    description: 'Grade student submissions',
    icon: DocumentTextIcon,
    action: () => console.log('Grade Students')
  },
  {
    name: 'Create Assignment',
    description: 'Create new assignment',
    icon: PlusIcon,
    action: () => console.log('Create Assignment')
  },
  {
    name: 'Schedule Office Hours',
    description: 'Set availability for students',
    icon: CalendarIcon,
    action: () => console.log('Schedule Office Hours')
  },
  {
    name: 'Send Announcement',
    description: 'Notify all students',
    icon: BellIcon,
    action: () => console.log('Send Announcement')
  },
  {
    name: 'Generate Report',
    description: 'Create class performance report',
    icon: ChartBarIcon,
    action: () => console.log('Generate Report')
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
  console.log('View class details:', classItem)
  // TODO: Navigate to class details page
}

const viewScheduleDetails = (schedule) => {
  console.log('View schedule details:', schedule)
  // TODO: Navigate to schedule details page
}
</script>
