<template>
  <FacultyLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">My Classes</h1>
          <p class="text-muted-foreground">
            {{ currentSemester }} {{ schoolYear }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Badge variant="secondary" class="text-sm">
            {{ classes.length }} {{ classes.length === 1 ? 'Class' : 'Classes' }}
          </Badge>
          <Badge variant="outline" class="text-sm">
            {{ totalStudents }} {{ totalStudents === 1 ? 'Student' : 'Students' }}
          </Badge>
        </div>
      </div>

      <!-- Error Message -->
      <div v-if="props.error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
        <div class="flex items-center space-x-2">
          <ExclamationTriangleIcon class="w-5 h-5 text-destructive" />
          <p class="text-destructive font-medium">{{ props.error }}</p>
        </div>
      </div>

      <!-- Quick Actions & Overview -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Today's Overview -->
        <Card class="lg:col-span-2">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <CalendarDaysIcon class="w-5 h-5" />
              Today's Overview
            </CardTitle>
            <CardDescription>
              {{ getCurrentDateFormatted() }}
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <!-- Today's Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div class="text-center">
                <div class="text-2xl font-bold text-primary">{{ todaysStats.scheduledClasses }}</div>
                <div class="text-sm text-muted-foreground">Classes Today</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ todaysStats.averageAttendance }}%</div>
                <div class="text-sm text-muted-foreground">Avg Attendance</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ todaysStats.pendingGrades }}</div>
                <div class="text-sm text-muted-foreground">Pending Grades</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-orange-600">{{ todaysStats.upcomingTasks }}</div>
                <div class="text-sm text-muted-foreground">Upcoming Tasks</div>
              </div>
            </div>

            <!-- Today's Schedule -->
            <div class="space-y-3">
              <h4 class="font-medium text-foreground">Today's Schedule</h4>
              <div v-if="todaysSchedule.length === 0" class="text-center py-4">
                <CalendarDaysIcon class="mx-auto h-8 w-8 text-muted-foreground" />
                <p class="mt-2 text-sm text-muted-foreground">No classes scheduled for today</p>
              </div>
              <div v-else class="space-y-2">
                <div
                  v-for="schedule in todaysSchedule"
                  :key="schedule.id"
                  class="flex items-center justify-between p-3 rounded-lg border border-border hover:bg-accent transition-colors cursor-pointer"
                  @click="goToClass(schedule.class)"
                >
                  <div class="flex items-center space-x-3">
                    <div :class="`w-3 h-3 rounded-full ${getScheduleStatusColor(schedule)}`"></div>
                    <div>
                      <p class="font-medium text-foreground">{{ schedule.subject_code }} - {{ schedule.section }}</p>
                      <p class="text-sm text-muted-foreground">{{ schedule.time_range }} • {{ schedule.room }}</p>
                    </div>
                  </div>
                  <div class="flex items-center space-x-2">
                    <Badge :variant="getScheduleStatusVariant(schedule)" class="text-xs">
                      {{ schedule.status }}
                    </Badge>
                    <Button
                      v-if="schedule.status === 'upcoming' || schedule.status === 'ongoing'"
                      variant="outline"
                      size="sm"
                      @click.stop="takeAttendance(schedule.class)"
                    >
                      <UsersIcon class="w-4 h-4 mr-1" />
                      Attendance
                    </Button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Recent Activities -->
            <div class="space-y-3">
              <h4 class="font-medium text-foreground">Recent Activities</h4>
              <div v-if="recentActivities.length === 0" class="text-center py-4">
                <ClockIcon class="mx-auto h-8 w-8 text-muted-foreground" />
                <p class="mt-2 text-sm text-muted-foreground">No recent activities</p>
              </div>
              <div v-else class="space-y-2">
                <div
                  v-for="activity in recentActivities.slice(0, 3)"
                  :key="activity.id"
                  class="flex items-start space-x-3 p-2 rounded-lg hover:bg-accent transition-colors"
                >
                  <div :class="`p-1 rounded-full ${getActivityIconColor(activity.type)}`">
                    <component :is="getActivityIcon(activity.type)" class="w-3 h-3 text-white" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-foreground">{{ activity.title }}</p>
                    <p class="text-xs text-muted-foreground">{{ activity.description }}</p>
                    <p class="text-xs text-muted-foreground">{{ formatRelativeTime(activity.timestamp) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Quick Actions -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <BoltIcon class="w-5 h-5" />
              Quick Actions
            </CardTitle>
            <CardDescription>
              Common tasks and shortcuts
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-3">
            <Button
              v-for="action in quickActions"
              :key="action.id"
              :variant="action.variant || 'outline'"
              :disabled="action.disabled"
              @click="executeQuickAction(action)"
              class="w-full justify-start"
            >
              <component :is="action.icon" class="w-4 h-4 mr-3" />
              <div class="text-left">
                <div class="font-medium">{{ action.title }}</div>
                <div class="text-xs text-muted-foreground">{{ action.description }}</div>
              </div>
            </Button>

            <!-- Separator -->
            <div class="border-t my-3"></div>

            <!-- Secondary Actions -->
            <Button @click="viewReports" class="w-full justify-start" variant="ghost" size="sm">
              <ChartBarIcon class="w-4 h-4 mr-2" />
              View Reports
            </Button>
            <Button @click="contactAdmin" class="w-full justify-start" variant="ghost" size="sm">
              <ExclamationTriangleIcon class="w-4 h-4 mr-2" />
              Contact Support
            </Button>
          </CardContent>
        </Card>
      </div>

      <!-- Classes Section -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle class="flex items-center gap-2">
                <BookOpenIcon class="w-5 h-5" />
                My Classes
              </CardTitle>
              <CardDescription>
                All your assigned classes for this semester
              </CardDescription>
            </div>
            <Button variant="outline" size="sm" @click="refreshClasses">
              <ArrowPathIcon class="w-4 h-4 mr-2" />
              Refresh
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          <div v-if="classes.length === 0" class="text-center py-8">
            <BookOpenIcon class="w-12 h-12 text-muted-foreground mx-auto mb-4" />
            <h3 class="text-lg font-medium mb-2">No classes assigned</h3>
            <p class="text-muted-foreground mb-4">
              You don't have any classes assigned for this semester yet.
            </p>
            <Button variant="outline" @click="contactAdmin">
              Contact Administrator
            </Button>
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="classItem in classes"
              :key="classItem.id"
              class="border rounded-lg p-4 hover:bg-muted/25 transition-colors cursor-pointer"
              @click="goToClass(classItem)"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <div>
                      <h3 class="font-semibold text-lg">{{ classItem.subject_code }}</h3>
                      <p class="text-muted-foreground">{{ classItem.subject_title }}</p>
                    </div>
                    <Badge variant="secondary">Section {{ classItem.section }}</Badge>
                  </div>

                  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div class="flex items-center gap-2">
                      <UsersIcon class="w-4 h-4 text-muted-foreground" />
                      <span>{{ classItem.student_count || 0 }} students</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <MapPinIcon class="w-4 h-4 text-muted-foreground" />
                      <span>{{ classItem.room || 'TBA' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <ClockIcon class="w-4 h-4 text-muted-foreground" />
                      <span>{{ classItem.units || 3 }} units</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <CalendarIcon class="w-4 h-4 text-muted-foreground" />
                      <span>{{ getClassSchedule(classItem) }}</span>
                    </div>
                  </div>
                </div>

                <div class="flex flex-col items-end gap-2">
                  <div class="flex gap-1">
                    <Button variant="ghost" size="sm" @click.stop="openStudentModal(classItem)">
                      <UsersIcon class="w-4 h-4" />
                    </Button>
                    <!-- <Button variant="ghost" size="sm" @click.stop="openEditSheet(classItem)">
                      <PencilIcon class="w-4 h-4" />
                    </Button> -->
                    <Button variant="ghost" size="sm" @click.stop="viewClassDetails(classItem)">
                      <EyeIcon class="w-4 h-4" />
                    </Button>
                  </div>
                  <Badge variant="outline" class="text-xs">
                    {{ getClassStatus(classItem) }}
                  </Badge>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Student List Modal -->
      <Dialog :open="showStudentModal" @update:open="val => (showStudentModal = val)">
        <DialogContent class="max-w-4xl max-h-[80vh] overflow-hidden">
          <DialogHeader>
            <DialogTitle class="flex items-center gap-2">
              <UsersIcon class="w-5 h-5" />
              Students in {{ selectedClass?.subject_code }} - Section {{ selectedClass?.section }}
            </DialogTitle>
            <DialogDescription>
              {{ selectedClass?.subject_title }} • {{ students.length }} students enrolled
            </DialogDescription>
          </DialogHeader>

          <div class="space-y-4">
            <!-- Export Options -->
            <div class="flex items-center justify-between p-4 bg-muted/50 rounded-lg">
              <div>
                <h4 class="font-medium">Export Student List</h4>
                <p class="text-sm text-muted-foreground">Download the complete student roster</p>
              </div>
              <div class="flex gap-2">
                <Button @click="exportToExcel" variant="outline" size="sm">
                  <span class="mr-2">📊</span>
                  Excel
                </Button>
                <Button @click="exportToPDF" variant="outline" size="sm">
                  <span class="mr-2">🧾</span>
                  PDF
                </Button>
              </div>
            </div>

            <!-- Student List -->
            <div class="border rounded-lg overflow-hidden">
              <div class="max-h-96 overflow-y-auto">
                <table class="w-full">
                  <thead class="bg-muted/50 sticky top-0">
                    <tr>
                      <th class="text-left p-3 font-medium">#</th>
                      <th class="text-left p-3 font-medium">Student ID</th>
                      <th class="text-left p-3 font-medium">Name</th>
                      <th class="text-left p-3 font-medium">Email</th>
                      <th class="text-left p-3 font-medium">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(student, index) in students" :key="student.id" class="border-t hover:bg-muted/25">
                      <td class="p-3 text-sm">{{ index + 1 }}</td>
                      <td class="p-3 text-sm font-mono">{{ student.student_id }}</td>
                      <td class="p-3">
                        <div class="flex items-center gap-3">
                          <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                            <span class="text-xs font-medium">{{ student.name.charAt(0) }}</span>
                          </div>
                          <span class="font-medium">{{ student.name }}</span>
                        </div>
                      </td>
                      <td class="p-3 text-sm text-muted-foreground">{{ student.email }}</td>
                      <td class="p-3">
                        <Badge :variant="student.status === 'active' ? 'default' : 'secondary'">
                          {{ student.status }}
                        </Badge>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <!-- Class Edit Sheet -->
      <Sheet :open="showEditModal" @update:open="val => (showEditModal = val)">
        <SheetContent side="right" class="sm:max-w-xl">
          <SheetHeader>
            <SheetTitle class="flex items-center gap-2">
              <PencilIcon class="w-5 h-5" />
              Edit Class Information
            </SheetTitle>
            <SheetDescription>
              Update the details for {{ selectedClass?.subject_code }} - {{ selectedClass?.subject_title }}
            </SheetDescription>
          </SheetHeader>

          <form @submit.prevent="saveClassChanges" class="space-y-6 mt-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="subject_code">Subject Code</Label>
                <Input id="subject_code" v-model="editForm.subject_code" placeholder="e.g., MATH101" required />
              </div>
              <div class="space-y-2">
                <Label for="section">Section</Label>
                <Input id="section" v-model="editForm.section" placeholder="e.g., A" required />
              </div>
            </div>

            <div class="space-y-2">
              <Label for="subject_title">Subject Title</Label>
              <Input id="subject_title" v-model="editForm.subject_title" placeholder="e.g., Introduction to Algebra" required />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="room">Room</Label>
                <Input id="room" v-model="editForm.room" placeholder="e.g., Room 101" />
              </div>
              <div class="space-y-2">
                <Label for="units">Units</Label>
                <Input id="units" v-model.number="editForm.units" type="number" min="1" max="6" placeholder="3" />
              </div>
            </div>

            <div class="space-y-2">
              <Label for="schedule">Schedule</Label>
              <Input id="schedule" v-model="editForm.schedule" placeholder="e.g., MWF 9:00-10:00 AM" />
            </div>

            <div class="space-y-2">
              <Label for="description">Description</Label>
              <Textarea id="description" v-model="editForm.description" placeholder="Brief description of the class..." rows="3" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t">
              <Button type="button" variant="outline" @click="showEditModal = false">Cancel</Button>
              <Button type="submit" :disabled="isLoading">
                <span v-if="isLoading" class="flex items-center gap-2">
                  <div class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></div>
                  Saving...
                </span>
                <span v-else>Save Changes</span>
              </Button>
            </div>
          </form>
        </SheetContent>
      </Sheet>


    </div>
  </FacultyLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import FacultyLayout from '@/Layouts/FacultyLayout.vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card.js'
import { Button } from '@/Components/ui/button.js'
import { Badge } from '@/Components/ui/badge.js'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/shadcn/ui/dialog'
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/Components/shadcn/ui/sheet'
import { Input } from '@/Components/shadcn/ui/input'
import { Textarea } from '@/Components/shadcn/ui/textarea'
import { Label } from '@/Components/shadcn/ui/label'
import {
  BookOpenIcon,
  UsersIcon,
  CalendarIcon,
  PencilIcon,
  SpeakerWaveIcon,
  CalendarDaysIcon,
  BoltIcon,
  ChartBarIcon,
  ArrowPathIcon,
  MapPinIcon,
  ClockIcon,
  EyeIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

// Props from the controller
const props = defineProps({
  classes: Array,
  stats: Array,
  subjects: Array,
  todaysData: Object,
  faculty: Object,
  currentSemester: String,
  schoolYear: String,
  availableSemesters: Object,
  availableSchoolYears: Object,
  error: String
})

// Computed properties
const classes = computed(() => props.classes || [])

const totalStudents = computed(() => {
  return classes.value.reduce((total, classItem) => total + (classItem.student_count || 0), 0)
})

// Today's overview computed properties based on real data from backend
const todaysStats = computed(() => {
  if (!props.todaysData) {
    return {
      scheduledClasses: 0,
      averageAttendance: 0,
      pendingGrades: 0,
      upcomingTasks: 0
    }
  }

  // Calculate average attendance from stats
  const totalAttendance = props.stats?.reduce((sum, stat) => sum + (stat.attendance_rate || 0), 0) || 0
  const averageAttendance = props.stats?.length > 0 ? Math.round(totalAttendance / props.stats.length) : 0

  // Calculate pending grades (students without complete grades)
  const pendingGrades = props.stats?.reduce((sum, stat) => {
    const studentsWithoutGrades = (stat.total_students || 0) - (stat.graded_students || 0)
    return sum + studentsWithoutGrades
  }, 0) || 0

  return {
    scheduledClasses: props.todaysData.scheduled_classes || 0,
    averageAttendance: averageAttendance,
    pendingGrades: pendingGrades,
    upcomingTasks: props.todaysData.total_classes || 0
  }
})

const todaysSchedule = computed(() => {
  if (!props.todaysData || !props.todaysData.schedules) {
    return []
  }

  return props.todaysData.schedules.map(schedule => ({
    id: schedule.id,
    class: schedule.class,
    subject_code: schedule.subject_code,
    section: schedule.section,
    time_range: schedule.time_range,
    room: schedule.room,
    status: schedule.status
  }))
})

const recentActivities = computed(() => {
  if (!props.todaysData || !props.todaysData.activities) {
    return []
  }

  return props.todaysData.activities.map(activity => ({
    id: activity.id,
    type: activity.type,
    title: activity.title,
    description: activity.description,
    timestamp: new Date(activity.timestamp)
  }))
})

const quickActions = computed(() => {
  const hasClasses = classes.value.length > 0
  const hasScheduledToday = todaysSchedule.value.length > 0

  return [
    {
      id: 'take-attendance',
      title: 'Take Attendance',
      description: hasScheduledToday ? 'Record attendance for today\'s classes' : 'No classes scheduled today',
      icon: UsersIcon,
      variant: hasScheduledToday ? 'default' : 'outline',
      disabled: !hasScheduledToday
    },
    {
      id: 'enter-grades',
      title: 'Enter Grades',
      description: hasClasses ? 'Update student grades' : 'No classes available',
      icon: PencilIcon,
      variant: 'outline',
      disabled: !hasClasses
    },
    {
      id: 'view-schedule',
      title: 'View Schedule',
      description: 'Check your class schedule',
      icon: CalendarIcon,
      variant: 'outline',
      disabled: false
    },
    {
      id: 'send-announcement',
      title: 'Send Announcement',
      description: hasClasses ? 'Notify your students' : 'No classes to notify',
      icon: SpeakerWaveIcon,
      variant: 'outline',
      disabled: !hasClasses
    }
  ]
})

// Helper methods
const getCurrentDateFormatted = () => {
  return new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const getScheduleStatusColor = (schedule) => {
  switch (schedule.status) {
    case 'ongoing':
      return 'bg-green-500'
    case 'completed':
      return 'bg-gray-400'
    case 'upcoming':
    default:
      return 'bg-blue-500'
  }
}

const getScheduleStatusVariant = (schedule) => {
  switch (schedule.status) {
    case 'ongoing':
      return 'default'
    case 'completed':
      return 'secondary'
    case 'upcoming':
    default:
      return 'outline'
  }
}

const getActivityIcon = (type) => {
  switch (type) {
    case 'enrollment':
      return UsersIcon
    case 'attendance':
      return UsersIcon
    case 'grades':
      return PencilIcon
    default:
      return ClockIcon
  }
}

const getActivityIconColor = (type) => {
  switch (type) {
    case 'enrollment':
      return 'bg-blue-500'
    case 'attendance':
      return 'bg-green-500'
    case 'grades':
      return 'bg-yellow-500'
    default:
      return 'bg-gray-500'
  }
}

const formatRelativeTime = (timestamp) => {
  const now = new Date()
  const diff = now - timestamp
  const hours = Math.floor(diff / (1000 * 60 * 60))
  const days = Math.floor(hours / 24)

  if (days > 0) {
    return `${days} day${days > 1 ? 's' : ''} ago`
  } else if (hours > 0) {
    return `${hours} hour${hours > 1 ? 's' : ''} ago`
  } else {
    return 'Just now'
  }
}

const getClassSchedule = (classItem) => {
  return classItem.schedule || 'TBA'
}

const getClassStatus = (classItem) => {
  return classItem.status || 'Active'
}

// Quick Action execution
const executeQuickAction = (action) => {
  switch (action.id) {
    case 'take-attendance':
      if (todaysSchedule.value.length > 0) {
        // Go to the first scheduled class for today
        takeAttendance(todaysSchedule.value[0].class)
      } else {
        router.visit('/faculty/attendance')
      }
      break
    case 'enter-grades':
      if (classes.value.length > 0) {
        // Go to the first class for grades
        enterGrades(classes.value[0])
      } else {
        router.visit('/faculty/grades')
      }
      break
    case 'view-schedule':
      router.visit('/faculty/schedule')
      break
    case 'send-announcement':
      router.visit('/faculty/announcements/create')
      break
    default:
      console.log(`Action ${action.id} not implemented`)
  }
}

const viewReports = () => {
  router.visit('/faculty/reports')
}

const refreshClasses = () => {
  router.reload()
}

const contactAdmin = () => {
  router.visit('/contact')
}

const goToClass = (classItem) => {
  router.visit(`/faculty/classes/${classItem.id}`)
}

const takeAttendance = (classItem) => {
  router.visit(`/faculty/classes/${classItem.id}/attendance`)
}

const enterGrades = (classItem) => {
  router.visit(`/faculty/classes/${classItem.id}/grades`)
}

const viewClassDetails = (classItem) => {
  router.visit(`/faculty/classes/${classItem.id}`)
}

// Modal state
const showStudentModal = ref(false)
const selectedClass = ref(null)

// Student list (replace with real fetch)
const students = ref([])

function openStudentModal(classItem) {
  selectedClass.value = classItem
  showStudentModal.value = true
  // TODO: Fetch students for this class from backend
  // For now, mock
  // Fetch from API
  axios.get(`/faculty/classes/${classItem.id}/students`).then((res) => {
    if (res.data && res.data.success) {
      students.value = res.data.data
    } else {
      students.value = []
    }
  }).catch(() => {
    students.value = []
  })
}

function exportToExcel() {
  if (!students.value?.length) return
  const headers = ['#', 'Student ID', 'Name', 'Email', 'Status']
  const rows = students.value.map((s, i) => [i + 1, s.student_id ?? '', s.name ?? '', s.email ?? '', s.status ?? ''])
  const csv = [headers, ...rows].map(r => r.map((cell) => {
    const str = String(cell ?? '')
    // Escape quotes and wrap if contains comma/newline
    const escaped = '"' + str.replace(/"/g, '""') + '"'
    return escaped
  }).join(',')).join('\n')
  // Prepend BOM for Excel UTF-8 support
  const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  const filename = `${selectedClass.value?.subject_code || 'class'}_${selectedClass.value?.section || ''}_students.csv`
  a.href = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

function exportToPDF() {
  if (!students.value?.length) return
  const title = `${selectedClass.value?.subject_code || 'Class'} - Section ${selectedClass.value?.section || ''}`
  const win = window.open('', '_blank')
  if (!win) return
  const style = `
    <style>
      body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"; padding: 24px; }
      h1 { font-size: 18px; margin: 0 0 8px; }
      p { margin: 0 0 16px; color: #666; }
      table { width: 100%; border-collapse: collapse; font-size: 12px; }
      th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
      th { background: #f7f7f7; }
    </style>
  `
  const rows = students.value.map((s, i) => `
    <tr>
      <td>${i + 1}</td>
      <td>${s.student_id ?? ''}</td>
      <td>${s.name ?? ''}</td>
      <td>${s.email ?? ''}</td>
      <td>${s.status ?? ''}</td>
    </tr>`).join('')
  const html = `
    <html>
      <head>
        <title>${title} - Students</title>
        ${style}
      </head>
      <body>
        <h1>Student Roster</h1>
        <p>${title}</p>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Student ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            ${rows}
          </tbody>
        </table>
      </body>
    </html>
  `
  win.document.open()
  win.document.write(html)
  win.document.close()
  win.focus()
  win.print()
}

// Removed unused functions - openEditSheet and saveClassChanges
// These were not being used in the current implementation
</script>
