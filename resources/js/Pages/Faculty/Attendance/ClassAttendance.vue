<template>
  <FacultyLayout :title="pageTitle">
    <template #header>
      <div class="flex flex-col space-y-2 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0 flex-1">
          <h2 class="text-xl font-semibold text-foreground truncate">
            {{ props.class.subject_code }} - Attendance
          </h2>
          <p class="text-sm text-muted-foreground mt-1">
            {{ props.class.Subject?.title || props.class.ShsSubject?.title }}
          </p>
        </div>
        <div class="flex items-center space-x-2 flex-shrink-0">
          <Button @click="showExportDialog = true" variant="outline" size="sm">
            <Icon icon="heroicons:arrow-down-tray" class="w-4 h-4 mr-2" />
            Export
          </Button>
          <Button @click="goBack" variant="outline" size="sm">
            <Icon icon="heroicons:arrow-left" class="w-4 h-4 mr-2" />
            Back
          </Button>
        </div>
      </div>
    </template>

    <div class="space-y-6">
      <!-- Date Selection and Session Stats -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card class="lg:col-span-2">
          <CardHeader>
            <CardTitle>Select Date</CardTitle>
            <CardDescription>Choose the date for attendance marking</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="flex items-center space-x-4">
              <div class="flex-1">
                <Input
                  type="date"
                  v-model="selectedDate"
                  @change="loadAttendanceForDate"
                  class="w-full"
                />
              </div>
              <Button @click="setToday" variant="outline">
                Today
              </Button>
              <Button 
                @click="markAllPresent" 
                variant="outline"
                :disabled="!canMarkAttendance"
              >
                Mark All Present
              </Button>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Session Stats</CardTitle>
            <CardDescription>{{ formatDate(selectedDate) }}</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div class="flex justify-between">
                <span class="text-sm text-muted-foreground">Total Students</span>
                <span class="font-medium">{{ session_stats.total || roster.length }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-muted-foreground">Present</span>
                <span class="font-medium text-green-600">{{ session_stats.present || 0 }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-muted-foreground">Absent</span>
                <span class="font-medium text-red-600">{{ session_stats.absent || 0 }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-muted-foreground">Late</span>
                <span class="font-medium text-yellow-600">{{ session_stats.late || 0 }}</span>
              </div>
              <Separator />
              <div class="flex justify-between">
                <span class="text-sm font-medium">Attendance Rate</span>
                <span class="font-bold" :class="getAttendanceRateColor(session_stats.attendance_rate || 0)">
                  {{ session_stats.attendance_rate || 0 }}%
                </span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Student Roster -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle>Student Roster</CardTitle>
              <CardDescription>Mark attendance for {{ formatDate(selectedDate) }}</CardDescription>
            </div>
            <div class="flex items-center space-x-2">
              <Button 
                @click="saveAttendance" 
                :disabled="!hasChanges || isSaving"
                :loading="isSaving"
              >
                <Icon icon="heroicons:check" class="w-4 h-4 mr-2" />
                {{ isSaving ? 'Saving...' : 'Save Attendance' }}
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div class="space-y-2">
            <div v-for="student in roster" :key="student.enrollment.id"
                 class="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/50">
              <div class="flex items-center space-x-4">
                <Avatar class="h-10 w-10">
                  <AvatarImage :src="student.student.profile_url" />
                  <AvatarFallback>
                    {{ getStudentInitials(student.student_name) }}
                  </AvatarFallback>
                </Avatar>
                <div>
                  <p class="font-medium">{{ student.student_name }}</p>
                  <p class="text-sm text-muted-foreground">{{ student.student_id_display }}</p>
                </div>
                <Badge variant="outline" class="text-xs">
                  {{ student.stats.attendance_rate }}% overall
                </Badge>
              </div>

              <div class="flex items-center space-x-4">
                <!-- Attendance Status Selector -->
                <Select 
                  :value="getStudentAttendanceStatus(student)"
                  @update:value="updateStudentAttendance(student, $event)"
                >
                  <SelectTrigger class="w-32">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem 
                      v-for="status in attendance_statuses" 
                      :key="status.value"
                      :value="status.value"
                    >
                      <div class="flex items-center space-x-2">
                        <Icon :icon="`heroicons:${status.icon}`" class="w-4 h-4" />
                        <span>{{ status.label }}</span>
                      </div>
                    </SelectItem>
                  </SelectContent>
                </Select>

                <!-- Remarks Button -->
                <Button 
                  @click="openRemarksDialog(student)"
                  variant="ghost" 
                  size="sm"
                  :class="{ 'text-blue-600': hasRemarks(student) }"
                >
                  <Icon icon="heroicons:chat-bubble-left-ellipsis" class="w-4 h-4" />
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Recent Sessions -->
      <Card>
        <CardHeader>
          <CardTitle>Recent Sessions</CardTitle>
          <CardDescription>Previous attendance sessions for this class</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-2">
            <div v-for="session in recent_sessions" :key="session.date"
                 class="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/50 cursor-pointer"
                 @click="loadSession(session.date)">
              <div class="flex items-center space-x-3">
                <div class="h-8 w-8 bg-primary/10 rounded-lg flex items-center justify-center">
                  <Icon icon="heroicons:calendar" class="h-4 w-4 text-primary" />
                </div>
                <div>
                  <p class="font-medium">{{ formatDate(session.date) }}</p>
                  <p class="text-sm text-muted-foreground">{{ session.day_name }}</p>
                </div>
              </div>
              <div class="text-right">
                <p class="font-medium">{{ session.stats.attendance_rate }}%</p>
                <p class="text-sm text-muted-foreground">
                  {{ session.stats.present_count }}/{{ session.stats.total }}
                </p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Remarks Dialog -->
    <Dialog v-model:open="showRemarksDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Add Remarks</DialogTitle>
          <DialogDescription>
            Add remarks for {{ selectedStudentForRemarks?.student_name }}
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4">
          <Textarea
            v-model="remarksText"
            placeholder="Enter remarks for this student's attendance..."
            rows="4"
          />
        </div>
        <DialogFooter>
          <Button @click="showRemarksDialog = false" variant="outline">
            Cancel
          </Button>
          <Button @click="saveRemarks">
            Save Remarks
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Export Dialog -->
    <Dialog v-model:open="showExportDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Export Attendance Data</DialogTitle>
          <DialogDescription>
            Export attendance data for {{ props.class.subject_code }}
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <Label>Start Date</Label>
              <Input type="date" v-model="exportStartDate" />
            </div>
            <div>
              <Label>End Date</Label>
              <Input type="date" v-model="exportEndDate" />
            </div>
          </div>
          <div>
            <Label>Format</Label>
            <Select v-model="exportFormat">
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="csv">CSV</SelectItem>
                <SelectItem value="excel">Excel</SelectItem>
                <SelectItem value="pdf">PDF</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
        <DialogFooter>
          <Button @click="showExportDialog = false" variant="outline">
            Cancel
          </Button>
          <Button @click="exportData" :disabled="isExporting">
            {{ isExporting ? 'Exporting...' : 'Export' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </FacultyLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import FacultyLayout from '@/Layouts/FacultyLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Button } from '@/Components/shadcn/ui/button'
import { Badge } from '@/Components/shadcn/ui/badge'
import { Input } from '@/Components/shadcn/ui/input'
import { Label } from '@/Components/shadcn/ui/label'
import { Textarea } from '@/Components/shadcn/ui/textarea'
import { Separator } from '@/Components/shadcn/ui/separator'
import { Avatar, AvatarImage, AvatarFallback } from '@/Components/shadcn/ui/avatar'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/shadcn/ui/select'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/shadcn/ui/dialog'
import { Icon } from '@iconify/vue'
import { toast } from 'vue-sonner'

const props = defineProps({
  class: Object,
  roster: Array,
  session_stats: Object,
  selected_date: String,
  recent_sessions: Array,
  attendance_statuses: Array,
  faculty: Object,
})

// Reactive data
const selectedDate = ref(props.selected_date)
const attendanceData = ref({})
const hasChanges = ref(false)
const isSaving = ref(false)
const showRemarksDialog = ref(false)
const showExportDialog = ref(false)
const selectedStudentForRemarks = ref(null)
const remarksText = ref('')
const exportStartDate = ref('')
const exportEndDate = ref('')
const exportFormat = ref('csv')
const isExporting = ref(false)

// Initialize attendance data
onMounted(() => {
  initializeAttendanceData()
  setDefaultExportDates()
})

const initializeAttendanceData = () => {
  props.roster.forEach(student => {
    attendanceData.value[student.enrollment.id] = {
      student_id: student.enrollment.student_id,
      status: student.attendance?.status || 'absent',
      remarks: student.attendance?.remarks || '',
    }
  })
}

const setDefaultExportDates = () => {
  const today = new Date()
  const thirtyDaysAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000)
  exportStartDate.value = thirtyDaysAgo.toISOString().split('T')[0]
  exportEndDate.value = today.toISOString().split('T')[0]
}

const canMarkAttendance = computed(() => {
  return props.roster.length > 0
})

const pageTitle = computed(() => {
  return `Attendance - ${props.class.subject_code}`
})

const getAttendanceRateColor = (rate) => {
  if (rate >= 90) return 'text-green-600'
  if (rate >= 75) return 'text-yellow-600'
  return 'text-red-600'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const getStudentInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getStudentAttendanceStatus = (student) => {
  return attendanceData.value[student.enrollment.id]?.status || 'absent'
}

const updateStudentAttendance = (student, status) => {
  if (!attendanceData.value[student.enrollment.id]) {
    attendanceData.value[student.enrollment.id] = {
      student_id: student.enrollment.student_id,
      status: 'absent',
      remarks: '',
    }
  }
  attendanceData.value[student.enrollment.id].status = status
  hasChanges.value = true
}

const hasRemarks = (student) => {
  return attendanceData.value[student.enrollment.id]?.remarks?.length > 0
}

const setToday = () => {
  selectedDate.value = new Date().toISOString().split('T')[0]
  loadAttendanceForDate()
}

const markAllPresent = () => {
  props.roster.forEach(student => {
    updateStudentAttendance(student, 'present')
  })
}

const loadAttendanceForDate = () => {
  router.visit(route('faculty.attendance.class.show', { 
    class: props.class.id,
    date: selectedDate.value 
  }), {
    preserveState: false,
    preserveScroll: true
  })
}

const loadSession = (date) => {
  selectedDate.value = date
  loadAttendanceForDate()
}

const openRemarksDialog = (student) => {
  selectedStudentForRemarks.value = student
  remarksText.value = attendanceData.value[student.enrollment.id]?.remarks || ''
  showRemarksDialog.value = true
}

const saveRemarks = () => {
  if (selectedStudentForRemarks.value) {
    const enrollmentId = selectedStudentForRemarks.value.enrollment.id
    if (!attendanceData.value[enrollmentId]) {
      attendanceData.value[enrollmentId] = {
        student_id: selectedStudentForRemarks.value.enrollment.student_id,
        status: 'absent',
        remarks: '',
      }
    }
    attendanceData.value[enrollmentId].remarks = remarksText.value
    hasChanges.value = true
  }
  showRemarksDialog.value = false
}

const saveAttendance = async () => {
  isSaving.value = true
  
  try {
    const attendanceArray = Object.values(attendanceData.value)
    
    const response = await fetch(route('faculty.attendance.class.mark', { class: props.class.id }), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify({
        date: selectedDate.value,
        attendance: attendanceArray,
      }),
    })

    const result = await response.json()

    if (result.success) {
      toast.success('Attendance saved successfully')
      hasChanges.value = false
      // Reload the page to get updated stats
      loadAttendanceForDate()
    } else {
      toast.error(result.message || 'Failed to save attendance')
    }
  } catch (error) {
    console.error('Error saving attendance:', error)
    toast.error('Failed to save attendance')
  } finally {
    isSaving.value = false
  }
}

const exportData = async () => {
  isExporting.value = true
  
  try {
    const response = await fetch(route('faculty.attendance.class.export', { class: props.class.id }), {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    })

    const result = await response.json()

    if (result.success) {
      // Open download URL
      window.open(result.download_url, '_blank')
      toast.success('Export prepared successfully')
      showExportDialog.value = false
    } else {
      toast.error(result.message || 'Failed to export data')
    }
  } catch (error) {
    console.error('Error exporting data:', error)
    toast.error('Failed to export data')
  } finally {
    isExporting.value = false
  }
}

const goBack = () => {
  router.visit(route('faculty.attendance.index'))
}
</script>
