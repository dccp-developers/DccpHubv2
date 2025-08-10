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
    <div class="space-y-3 md:space-y-6">
      <!-- Activities Sheet -->
      <Sheet :open="showActivitiesSheet" @update:open="showActivitiesSheet = $event">
        <SheetContent side="right" class="w-full sm:max-w-md">
          <SheetHeader>
            <SheetTitle>All Recent Activities</SheetTitle>
            <SheetDescription>Overview of your latest activities. Click an activity to open the associated class.</SheetDescription>
          </SheetHeader>
          <div class="mt-4">
            <!-- Filters -->
            <div class="flex items-end gap-2 mb-3">
              <div class="w-1/2">
                <label class="text-xs text-muted-foreground mb-1 block">Type</label>
                <Select v-model="activitiesFilter.type">
                  <SelectTrigger class="w-full">
                    <SelectValue placeholder="All types" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All</SelectItem>
                    <SelectItem value="grade_submitted">Grade submitted</SelectItem>
                    <SelectItem value="class_updated">Class updated</SelectItem>
                    <SelectItem value="student_enrolled">Student enrolled</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="w-1/2">
                <label class="text-xs text-muted-foreground mb-1 block">Class</label>
                <Select v-model="activitiesFilter.classId">
                  <SelectTrigger class="w-full">
                    <SelectValue placeholder="All classes" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">All</SelectItem>
                    <SelectItem v-for="c in classes" :key="c.id" :value="String(c.id)">
                      {{ c.subject_code }} - Sec {{ c.section }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div v-if="recentActivities.length === 0" class="text-sm text-muted-foreground">No recent activity.</div>
            <ScrollArea v-else class="h-[70vh] pr-2" @scroll.passive="onActivitiesScroll">
              <div class="divide-y divide-border">
                <div
                  v-for="activity in activities.items"
                  :key="activity.id + '-' + activity.raw_timestamp"
                  class="flex items-start gap-3 py-3 cursor-pointer hover:bg-accent rounded-md px-2 -mx-2"
                  @click="openActivityClass(activity)"
                >
                  <div class="flex-shrink-0">
                    <div :class="`w-8 h-8 rounded-full flex items-center justify-center ${getActivityColor(activity.type)}`">
                      <component :is="getActivityIcon(activity.type)" class="w-4 h-4" />
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                      <p class="text-sm font-medium text-foreground truncate">{{ activity.description }}</p>
                      <span :class="['text-xs whitespace-nowrap', getTimestampColor(activity.raw_timestamp)]">{{ formatRelativeTime(activity.raw_timestamp) }}</span>
                    </div>
                    <div class="mt-1 flex items-center gap-2">
                      <Badge variant="secondary" class="text-xs capitalize">
                        {{ (activity.type || '').replace('_', ' ') }}
                      </Badge>
                      <Badge v-if="activity.metadata?.subject_code" variant="outline" class="text-xs">Class {{ activity.metadata.subject_code }}</Badge>
                    </div>
                  </div>
                </div>
                <div v-if="activities.loading" class="py-3 text-center text-xs text-muted-foreground">Loading…</div>
                <div v-else-if="activities.nextOffset === null" class="py-3 text-center text-xs text-muted-foreground">No more activities</div>
              </div>
            </ScrollArea>
            <div class="mt-2">
              <Button v-if="activities.nextOffset !== null && !activities.loading" class="w-full" variant="outline" @click="fetchActivities">Load more</Button>
            </div>
          </div>
        </SheetContent>
      </Sheet>
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
        <CardContent class="p-3 md:p-6">
          <div class="flex flex-col space-y-2 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h3 class="text-base md:text-lg font-semibold">Welcome to your Faculty Dashboard</h3>
              <p class="text-primary-foreground/80 mt-1 text-xs md:text-sm">Manage your classes, students, and academic activities</p>
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
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 md:gap-6">
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
              <div v-if="todaysSchedule.length === 0" class="text-center py-4 md:py-8">
                <CalendarIcon class="mx-auto h-6 w-6 md:h-12 md:w-12 text-muted-foreground" />
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
              <div v-if="classes.length === 0" class="text-center py-4 md:py-8">
                <BookOpenIcon class="mx-auto h-6 w-6 md:h-12 md:w-12 text-muted-foreground" />
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

      <!-- Weekly Schedule Overview - Hidden on Mobile -->
      <Card class="hidden md:block">
        <CardContent class="p-3 md:p-6">
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
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 md:gap-6">
        <!-- Attendance Widget -->
        <div>

        </div>

        <!-- Recent Activities -->
        <div class="lg:col-span-2">
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle class="text-base md:text-lg">Recent Activities</CardTitle>
                  <CardDescription class="text-xs md:text-sm">Latest actions across your classes</CardDescription>
                </div>
                <Button size="sm" variant="outline" @click="showActivitiesSheet = true">
                  View All
                </Button>
              </div>
            </CardHeader>
            <CardContent class="pt-0">
              <div v-if="recentActivities.length === 0" class="text-center py-6 text-sm text-muted-foreground">
                No recent activity.
              </div>
              <div v-else class="divide-y divide-border">
                <div
                  v-for="activity in recentActivities.slice(0, 5)"
                  :key="activity.id"
                  class="flex items-start gap-3 py-3"
                >
                  <div class="flex-shrink-0">
                    <div :class="`w-8 h-8 rounded-full flex items-center justify-center ${getActivityColor(activity.type)}`">
                      <component :is="getActivityIcon(activity.type)" class="w-4 h-4" />
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                      <p class="text-sm font-medium text-foreground truncate">{{ activity.description }}</p>
                      <span class="text-xs text-muted-foreground whitespace-nowrap">{{ activity.timestamp }}</span>
                    </div>
                    <div class="mt-1 flex items-center gap-2">
                      <Badge variant="secondary" class="text-xs capitalize">
                        {{ (activity.type || '').replace('_', ' ') }}
                      </Badge>
                      <Badge v-if="activity.metadata?.subject_code || activity.class_code" variant="outline" class="text-xs">Class {{ activity.metadata?.subject_code || activity.class_code }}</Badge>
                    </div>
                  </div>
                  <Button size="sm" variant="ghost" class="shrink-0" :disabled="!resolveActivityClassId(activity)" @click="openActivityClass(activity)">Open</Button>
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
          <Card class="mt-3 md:mt-6">
            <CardHeader class="pb-2 md:pb-6">
              <CardTitle class="text-base md:text-lg">Upcoming Deadlines</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-2 md:space-y-3">
                <div
                  v-for="deadline in upcomingDeadlines"
                  :key="deadline.id"
                  class="flex items-center justify-between p-2 md:p-3 rounded-lg border border-gray-200"
                >
                  <div class="min-w-0">
                    <p class="text-xs md:text-sm font-medium text-foreground truncate">{{ deadline.title }}</p>
                    <div class="flex items-center gap-2 mt-1">
                      <Badge :variant="getDeadlinePriorityVariant(deadline.priority)" class="text-[10px] capitalize">{{ deadline.priority }}</Badge>
                      <Badge v-if="deadline.class_code" variant="outline" class="text-[10px]">{{ deadline.class_code }}</Badge>
                      <span :class="['text-[10px] whitespace-nowrap', getTimestampColor(deadline.due_date)]">Due {{ formatRelativeTime(deadline.due_date) }}</span>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <Button size="sm" variant="ghost" :disabled="!deadline.class_id" @click="openDeadlineClass(deadline)">Open</Button>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Performance Analytics (Data-Driven) - Hidden on Mobile -->
      <Card class="hidden md:block">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle class="text-lg">Performance Analytics</CardTitle>
              <CardDescription>Key metrics based on your recent classes and attendance</CardDescription>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Attendance -->
            <div>
              <div class="text-2xl font-bold text-blue-600">{{ performanceMetrics.attendance?.rate ?? 0 }}%</div>
              <div class="text-sm text-muted-foreground">Attendance Rate ({{ performanceMetrics.attendance?.period || 'last 30 days' }})</div>
              <div class="text-xs text-muted-foreground mt-2">
                Present: <span class="text-green-600 font-medium">{{ performanceMetrics.attendance?.present ?? 0 }}</span>
                · Absent: <span class="text-red-600 font-medium">{{ performanceMetrics.attendance?.absent ?? 0 }}</span>
                · Total marks: {{ performanceMetrics.attendance?.total ?? 0 }}
              </div>
            </div>

            <!-- Grades & Completion -->
            <div>
              <div class="text-2xl font-bold text-green-600">{{ performanceMetrics.grades?.completion_rate ?? 0 }}%</div>
              <div class="text-sm text-muted-foreground">Grade Completion</div>
              <div class="text-xs text-muted-foreground mt-2">
                Finalized: <span class="text-green-600 font-medium">{{ performanceMetrics.grades?.finalized_count ?? 0 }}</span>
                · Enrollments: {{ performanceMetrics.grades?.total_enrollments ?? 0 }}
              </div>
              <div class="text-xs mt-1">
                Passing Rate: <span class="font-medium">{{ performanceMetrics.grades?.passing_rate ?? 0 }}%</span>
                · Avg Grade: <span class="font-medium">{{ performanceMetrics.grades?.average_grade ?? 0 }}</span>
              </div>
            </div>

            <!-- Teaching Overview -->
            <div>
              <div class="text-2xl font-bold text-purple-600">{{ performanceMetrics.teaching?.total_classes ?? 0 }}</div>
              <div class="text-sm text-muted-foreground">Active Classes</div>
              <div class="text-xs text-muted-foreground mt-2">
                Students: <span class="font-medium">{{ performanceMetrics.teaching?.total_students ?? 0 }}</span>
                · At risk: <span class="text-amber-600 font-medium">{{ performanceMetrics.teaching?.students_at_risk ?? 0 }}</span>
              </div>
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
import TeachingGuide from '@/Components/Faculty/TeachingGuide.vue'
import WeeklySchedule from '@/Components/Faculty/WeeklySchedule.vue'
import { Badge } from '@/Components/shadcn/ui/badge'
import { Button } from '@/Components/shadcn/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { ScrollArea } from '@/Components/shadcn/ui/scroll-area'
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/Components/shadcn/ui/sheet'
import FacultyLayout from '@/Layouts/FacultyLayout.vue'
import {
  AcademicCapIcon,
  ArrowTrendingUpIcon,
  BookOpenIcon,
  CalendarIcon,
  ChartBarIcon,
  CheckIcon,
  ChevronRightIcon,
  ClipboardDocumentListIcon,
  ClockIcon,
  DocumentTextIcon,
  EllipsisVerticalIcon,
  FunnelIcon,
  MapPinIcon,
  UserGroupIcon,
  UsersIcon
} from '@heroicons/vue/24/outline'
import { router } from '@inertiajs/vue3'
import { computed, reactive, ref, watch } from 'vue'
import { route } from 'ziggy-js'

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
const recentActivities = computed(() => props.recentActivities || []) // initial batch for card
const upcomingDeadlines = computed(() => props.upcomingDeadlines || [])
const performanceMetrics = computed(() => props.performanceMetrics || {})
const scheduleOverview = computed(() => props.scheduleOverview || {})

// Error handling
const hasError = computed(() => !!props.error)

// Reactive state
const showTeachingGuide = ref(false)
const showActivitiesSheet = ref(false)

// Activities lazy load state
const activities = reactive({ items: [], offset: 0, limit: 20, nextOffset: 0, loading: false, initialized: false })
const activitiesFilter = reactive({ type: 'all', classId: '' })

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

// Activities helpers and lazy loading
const formatRelativeTime = (date) => {
  const d = new Date(date)
  const diff = (Date.now() - d.getTime()) / 1000
  if (diff < 60) return 'just now'
  if (diff < 3600) return Math.floor(diff / 60) + 'm ago'
  if (diff < 86400) return Math.floor(diff / 3600) + 'h ago'
  return Math.floor(diff / 86400) + 'd ago'
}

const getTimestampColor = (date) => {
  const d = new Date(date)
  const diffH = (Date.now() - d.getTime()) / 3600000
  if (diffH < 1) return 'text-green-600'
  if (diffH < 24) return 'text-amber-600'
  return 'text-muted-foreground'
}

const fetchActivities = async () => {
  if (activities.loading || activities.nextOffset === null) return
  activities.loading = true
  try {
    const params = {
      offset: activities.offset,
      limit: activities.limit,
    }
    if (activitiesFilter.type && activitiesFilter.type !== 'all') params.type = activitiesFilter.type
    if (activitiesFilter.classId) params.class_id = activitiesFilter.classId

    const res = await axios.get(route('faculty.activities', params))
    if (res.data?.success) {
      const newItems = res.data.data || []
      activities.items.push(...newItems)
      activities.nextOffset = res.data.nextOffset
      activities.offset = activities.nextOffset ?? activities.offset
    }
  } catch (e) {
    console.error('Failed to load activities', e)
  } finally {
    activities.loading = false
    activities.initialized = true
  }
}

const onActivitiesScroll = (e) => {
  const el = e.target
  if (el.scrollTop + el.clientHeight >= el.scrollHeight - 100) {
    fetchActivities()
  }
}

// Initialize activities when sheet opens
watch(showActivitiesSheet, async (open) => {
  if (open) {
    if (!activities.initialized) {
      // seed with initial recentActivities for instant UI, then fetch more
      if (activities.items.length === 0 && recentActivities.value.length > 0) {
        activities.items.push(...recentActivities.value)
        activities.offset = recentActivities.value.length
        activities.nextOffset = activities.offset
      }
      await fetchActivities()
    }
  }
})

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

const openActivityClass = (activity) => {
  const classId = resolveActivityClassId(activity)
  if (classId) {
    router.visit(route('faculty.classes.show', { class: classId }))
  }
}

const resolveActivityClassId = (activity) => {
  // Centralized mapping for different activity payload shapes
  return activity.class_id || activity.classId || activity.class?.id || null
}

const viewScheduleDetails = (schedule) => {
  console.log('View schedule details:', schedule)
}

const viewFullCalendar = () => {
  router.visit(route('faculty.schedule.index'))
}

const viewAllSchedule = () => {
  router.visit(route('faculty.schedule.index'))
}
</script>
