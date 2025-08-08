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
              {{ new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div class="text-center">
                <div class="text-2xl font-bold text-primary">{{ todaysClasses }}</div>
                <div class="text-sm text-muted-foreground">Classes Today</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ attendanceRate }}%</div>
                <div class="text-sm text-muted-foreground">Avg Attendance</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ pendingGrades }}</div>
                <div class="text-sm text-muted-foreground">Pending Grades</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-orange-600">{{ upcomingExams }}</div>
                <div class="text-sm text-muted-foreground">Upcoming Exams</div>
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
              Common tasks for today
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-3">
            <Button @click="goToAttendance" class="w-full justify-start" variant="outline">
              <UsersIcon class="w-4 h-4 mr-2" />
              Take Attendance
            </Button>
            <Button @click="goToGrades" class="w-full justify-start" variant="outline">
              <PencilIcon class="w-4 h-4 mr-2" />
              Enter Grades
            </Button>
            <Button @click="viewSchedule" class="w-full justify-start" variant="outline">
              <CalendarIcon class="w-4 h-4 mr-2" />
              View Schedule
            </Button>
            <Button @click="sendAnnouncement" class="w-full justify-start" variant="outline">
              <SpeakerWaveIcon class="w-4 h-4 mr-2" />
              Send Announcement
            </Button>
            <div class="border-t my-2"></div>
            <Button @click="viewReports" class="w-full justify-start" variant="ghost" size="sm">
              <ChartBarIcon class="w-4 h-4 mr-2" />
              View Reports
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

// Today's overview computed properties
const todaysClasses = computed(() => {
  // This would typically come from backend, using mock data for now
  return Math.min(classes.value.length, 3)
})

const attendanceRate = computed(() => {
  // Mock calculation - would come from backend
  return 85
})

const pendingGrades = computed(() => {
  // Mock calculation - would come from backend
  return 12
})

const upcomingExams = computed(() => {
  // Mock calculation - would come from backend
  return 2
})

// Helper methods
const getClassSchedule = (classItem) => {
  // Mock schedule - would come from backend
  return 'MWF 9:00 AM'
}

const getClassStatus = (classItem) => {
  // Mock status - would come from backend
  return 'Active'
}

// Action Methods for Teachers
const goToAttendance = () => {
  router.visit('/faculty/attendance')
}

const goToGrades = () => {
  router.visit('/faculty/grades')
}

const viewSchedule = () => {
  router.visit('/faculty/schedule')
}

const sendAnnouncement = () => {
  router.visit('/faculty/announcements/create')
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
const showEditModal = ref(false)
const selectedClass = ref(null)

// Student list (replace with real fetch)
const students = ref([])

// Edit form state
const editForm = ref({
  subject_code: '',
  subject_title: '',
  section: '',
  room: '',
  units: 3,
  schedule: '',
  description: ''
})

const isLoading = ref(false)

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

function openEditSheet(classItem) {
  selectedClass.value = classItem
  editForm.value = {
    subject_code: classItem.subject_code || '',
    subject_title: classItem.subject_title || '',
    section: classItem.section || '',
    room: classItem.room || '',
    units: classItem.units || 3,
    schedule: getClassSchedule(classItem),
    description: classItem.description || ''
  }
  showEditModal.value = true
}

function saveClassChanges() {
  if (!selectedClass.value) return
  isLoading.value = true
  router.put(`/faculty/classes/${selectedClass.value.id}`,
    editForm.value,
    {
      onSuccess: () => {
        showEditModal.value = false
      },
      onFinish: () => {
        isLoading.value = false
      }
    }
  )
}
</script>
