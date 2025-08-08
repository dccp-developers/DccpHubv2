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
          <Button @click="showResetDialog = true" variant="destructive" size="sm">
            <Icon icon="heroicons:arrow-path" class="w-4 h-4 mr-2" />
            Reset All
          </Button>
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
      <!-- Enhanced Date Selection and Quick Actions -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card class="lg:col-span-2">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Icon icon="heroicons:calendar-days" class="w-5 h-5" />
              Attendance Session
            </CardTitle>
            <CardDescription>Select date and manage attendance for this class</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="flex items-center space-x-4">
              <div class="flex-1">
                <Label for="attendance-date" class="text-sm font-medium">Session Date</Label>
                <Input
                  id="attendance-date"
                  type="date"
                  v-model="selectedDate"
                  @change="loadAttendanceForDate"
                  class="w-full mt-1"
                />
              </div>
              <div class="flex flex-col gap-2">
                <Button @click="setToday" variant="outline" size="sm">
                  <Icon icon="heroicons:calendar" class="w-4 h-4 mr-2" />
                  Today
                </Button>
              </div>
            </div>

            <!-- Quick Actions -->
            <div class="flex items-center justify-between pt-4 border-t">
              <div>
                <h4 class="text-sm font-medium">Quick Actions</h4>
                <p class="text-xs text-muted-foreground">Mark all students at once</p>
              </div>
              <div class="flex gap-2">
                <Button
                  @click="markAllPresent"
                  variant="default"
                  size="sm"
                  :disabled="!canMarkAttendance"
                >
                  <Icon icon="heroicons:check-circle" class="w-4 h-4 mr-2" />
                  All Present
                </Button>
                <Button
                  @click="markAllAbsent"
                  variant="outline"
                  size="sm"
                  :disabled="!canMarkAttendance"
                >
                  <Icon icon="heroicons:x-circle" class="w-4 h-4 mr-2" />
                  All Absent
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Icon icon="heroicons:chart-pie" class="w-5 h-5" />
              Session Statistics
            </CardTitle>
            <CardDescription>{{ formatDate(selectedDate) }}</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <!-- Total Students -->
              <div class="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                <div class="flex items-center gap-2">
                  <Icon icon="heroicons:users" class="w-4 h-4 text-muted-foreground" />
                  <span class="text-sm font-medium">Total Students</span>
                </div>
                <span class="text-lg font-bold">{{ session_stats.total || roster.length }}</span>
              </div>

              <!-- Attendance Breakdown -->
              <div class="grid grid-cols-3 gap-2">
                <div class="text-center p-3 bg-green-50 border border-green-200 rounded-lg">
                  <div class="text-lg font-bold text-green-600">{{ session_stats.present || 0 }}</div>
                  <div class="text-xs text-green-600">Present</div>
                </div>
                <div class="text-center p-3 bg-red-50 border border-red-200 rounded-lg">
                  <div class="text-lg font-bold text-red-600">{{ session_stats.absent || 0 }}</div>
                  <div class="text-xs text-red-600">Absent</div>
                </div>
                <div class="text-center p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                  <div class="text-lg font-bold text-yellow-600">{{ session_stats.late || 0 }}</div>
                  <div class="text-xs text-yellow-600">Late</div>
                </div>
              </div>

              <!-- Attendance Rate -->
              <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium text-blue-900">Attendance Rate</span>
                  <span class="text-2xl font-bold" :class="getAttendanceRateColor(session_stats.attendance_rate || 0)">
                    {{ session_stats.attendance_rate || 0 }}%
                  </span>
                </div>
                <div class="mt-2">
                  <div class="w-full bg-blue-200 rounded-full h-2">
                    <div
                      class="h-2 rounded-full transition-all duration-300"
                      :class="getAttendanceRateBarColor(session_stats.attendance_rate || 0)"
                      :style="{ width: `${session_stats.attendance_rate || 0}%` }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Enhanced Student Roster -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle class="flex items-center gap-2">
                <Icon icon="heroicons:academic-cap" class="w-5 h-5" />
                Student Roster
                <Badge variant="secondary" class="ml-2">{{ roster.length }} students</Badge>
              </CardTitle>
              <CardDescription>Mark attendance for {{ formatDate(selectedDate) }}</CardDescription>
            </div>
            <div class="flex items-center space-x-2">
              <Button
                @click="handleSaveClick"
                :disabled="!hasChanges || isSaving"
                class="bg-green-600 hover:bg-green-700"
              >
                <Icon v-if="isSaving" icon="heroicons:arrow-path" class="w-4 h-4 mr-2 animate-spin" />
                <Icon v-else icon="heroicons:check" class="w-4 h-4 mr-2" />
                {{ isSaving ? 'Saving...' : 'Save Attendance' }}
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <template v-if="roster && roster.length > 0">
            <div class="space-y-3">
              <div v-for="(student, index) in roster" :key="student.enrollment.id"
                   class="group flex items-center justify-between p-4 border rounded-lg hover:bg-muted/50 hover:border-primary/20 transition-all duration-200">
                <div class="flex items-center space-x-4">
                  <!-- Student Number -->
                  <div class="flex-shrink-0 w-8 h-8 bg-muted rounded-full flex items-center justify-center text-sm font-medium text-muted-foreground">
                    {{ index + 1 }}
                  </div>

                  <!-- Avatar -->
                  <Avatar class="h-12 w-12 border-2 border-background shadow-sm">
                    <AvatarImage :src="student.student.profile_url" />
                    <AvatarFallback class="bg-gradient-to-br from-blue-500 to-purple-600 text-white font-semibold">
                      {{ getStudentInitials(student.student_name) }}
                    </AvatarFallback>
                  </Avatar>

                  <!-- Student Info -->
                  <div class="flex-1">
                    <div class="flex items-center gap-2">
                      <p class="font-semibold text-foreground">{{ student.student_name }}</p>
                      <Badge
                        :variant="getAttendanceRateBadgeVariant(student.stats.attendance_rate)"
                        class="text-xs"
                      >
                        {{ student.stats.attendance_rate }}% overall
                      </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">{{ student.student_id_display }}</p>
                    <div v-if="hasRemarks(student)" class="mt-1">
                      <Badge variant="outline" class="text-xs">
                        <Icon icon="heroicons:chat-bubble-left-ellipsis" class="w-3 h-3 mr-1" />
                        Has remarks
                      </Badge>
                    </div>
                  </div>
                </div>

                <!-- Enhanced Attendance Controls -->
                <div class="flex items-center space-x-3">
                  <!-- Quick Status Buttons -->
                  <div class="flex items-center space-x-1 bg-muted/50 rounded-lg p-1">
                    <Button
                      @click="updateStudentAttendance(student, 'present')"
                      :variant="getStudentAttendanceStatus(student) === 'present' ? 'default' : 'ghost'"
                      size="sm"
                      class="h-8 px-3"
                    >
                      <Icon icon="heroicons:check-circle" class="w-4 h-4 mr-1" />
                      <span class="hidden sm:inline">Present</span>
                    </Button>
                    <Button
                      @click="updateStudentAttendance(student, 'absent')"
                      :variant="getStudentAttendanceStatus(student) === 'absent' ? 'destructive' : 'ghost'"
                      size="sm"
                      class="h-8 px-3"
                    >
                      <Icon icon="heroicons:x-circle" class="w-4 h-4 mr-1" />
                      <span class="hidden sm:inline">Absent</span>
                    </Button>
                    <Button
                      @click="updateStudentAttendance(student, 'late')"
                      :variant="getStudentAttendanceStatus(student) === 'late' ? 'secondary' : 'ghost'"
                      size="sm"
                      class="h-8 px-3"
                    >
                      <Icon icon="heroicons:clock" class="w-4 h-4 mr-1" />
                      <span class="hidden sm:inline">Late</span>
                    </Button>
                  </div>

                  <!-- Remarks Button -->
                  <Button
                    @click="openRemarksDialog(student)"
                    variant="outline"
                    size="sm"
                    class="h-8 px-3"
                    :class="{ 'border-blue-500 text-blue-600 bg-blue-50': hasRemarks(student) }"
                  >
                    <Icon icon="heroicons:chat-bubble-left-ellipsis" class="w-4 h-4" />
                    <span class="hidden sm:inline ml-1">{{ hasRemarks(student) ? 'Edit' : 'Add' }} Note</span>
                  </Button>
                </div>
              </div>
            </div>
          </template>
          <template v-else>
            <div class="p-6 text-center border rounded-lg bg-muted/30">
              <Icon icon="heroicons:information-circle" class="w-8 h-8 mx-auto text-muted-foreground mb-2" />
              <p class="text-sm text-muted-foreground">Attendance has not been initialized for this class yet.</p>
              <p class="text-xs text-muted-foreground">Use the Reset button above to clear residual data or go back to Attendance to initialize.</p>
            </div>
          </template>
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

    <!-- Reset Attendance Dialog -->
    <Dialog v-model:open="showResetDialog">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <Icon icon="heroicons:exclamation-triangle" class="w-5 h-5 text-destructive" />
            Reset All Attendance Data
          </DialogTitle>
          <DialogDescription>
            This will permanently delete all attendance records for {{ props.class.subject_code }}. This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4">
          <div class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <div class="flex items-start gap-3">
              <Icon icon="heroicons:exclamation-triangle" class="w-5 h-5 text-destructive mt-0.5" />
              <div class="space-y-1">
                <p class="text-sm font-medium text-destructive">Warning: This action is irreversible</p>
                <ul class="text-sm text-muted-foreground space-y-1">
                  <li>• All attendance records will be deleted</li>
                  <li>• Attendance statistics will be reset</li>
                  <li>• Historical data will be lost</li>
                </ul>
              </div>
            </div>
          </div>
          <div class="space-y-2">
            <Label for="reset-confirmation">Type "RESET" to confirm:</Label>
            <Input
              id="reset-confirmation"
              v-model="resetConfirmationText"
              placeholder="Type RESET to confirm"
              class="font-mono"
            />
          </div>
        </div>
        <DialogFooter class="gap-2">
          <Button variant="outline" @click="showResetDialog = false">
            Cancel
          </Button>
          <Button
            variant="destructive"
            @click="handleResetClick"
            :disabled="resetConfirmationText !== 'RESET' || isResetting"
          >
            <Icon v-if="isResetting" icon="heroicons:arrow-path" class="w-4 h-4 mr-2 animate-spin" />
            <Icon v-else icon="heroicons:trash" class="w-4 h-4 mr-2" />
            {{ isResetting ? 'Resetting...' : 'Reset All Data' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </FacultyLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import FacultyLayout from '@/Layouts/FacultyLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Button } from '@/Components/shadcn/ui/button'
import { Badge } from '@/Components/shadcn/ui/badge'
import { Input } from '@/Components/shadcn/ui/input'
import { Label } from '@/Components/shadcn/ui/label'
import { Textarea } from '@/Components/shadcn/ui/textarea'
import { Avatar, AvatarImage, AvatarFallback } from '@/Components/shadcn/ui/avatar'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/shadcn/ui/select'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/shadcn/ui/dialog'
import { Icon } from '@iconify/vue'
import { toast } from 'vue-sonner'
import axios from 'axios'

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
const showResetDialog = ref(false)
const resetConfirmationText = ref('')
const isResetting = ref(false)

// Initialize attendance data
// Get class ID from props or URL as fallback
const getClassId = () => {
  if (props.class?.id) {
    return props.class.id
  }

  // Fallback: extract from current URL
  const urlParts = window.location.pathname.split('/')
  const classIndex = urlParts.indexOf('class')
  if (classIndex !== -1 && urlParts[classIndex + 1]) {
    return urlParts[classIndex + 1]
  }

  return null
}

onMounted(() => {
  console.log('ClassAttendance mounted with props:', {
    class: props.class,
    classId: props.class?.id,
    classIdFromUrl: getClassId(),
    roster: props.roster?.length,
    session_stats: props.session_stats
  })
  initializeAttendanceData()
  setDefaultExportDates()
})

const initializeAttendanceData = () => {
  // If attendance isn't initialized yet (empty roster or no attendance records), be safe
  if (!props.roster || props.roster.length === 0) {
    attendanceData.value = {}
    return
  }

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
  const classId = getClassId()
  router.visit(`/faculty/attendance/class/${classId}?date=${selectedDate.value}`, {
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

const handleSaveClick = () => {
  const classId = getClassId()
  console.log('Save button clicked', {
    hasChanges: hasChanges.value,
    isSaving: isSaving.value,
    attendanceDataCount: Object.keys(attendanceData.value).length,
    classId: classId,
    propsClassId: props.class?.id
  })

  if (!classId) {
    toast.error('Class ID is missing. Please refresh the page.')
    return
  }

  saveAttendance()
}

const saveAttendance = async () => {
  isSaving.value = true

  try {
    const classId = getClassId()
    const attendanceArray = Object.values(attendanceData.value)

    console.log('Saving attendance:', {
      classId: classId,
      date: selectedDate.value,
      attendanceCount: attendanceArray.length,
      attendance: attendanceArray
    })

    const response = await axios.post(`/faculty/attendance/class/${classId}/mark`, {
      date: selectedDate.value,
      attendance: attendanceArray,
    })

    console.log('Save response:', response.data)

    if (response.data.success) {
      toast.success('Attendance saved successfully')
      hasChanges.value = false
      // Update session stats if provided
      if (response.data.session_stats) {
        props.session_stats.present = response.data.session_stats.present
        props.session_stats.absent = response.data.session_stats.absent
        props.session_stats.late = response.data.session_stats.late
        props.session_stats.total = response.data.session_stats.total
        props.session_stats.attendance_rate = response.data.session_stats.attendance_rate
      }
    } else {
      toast.error(response.data.message || 'Failed to save attendance')
    }
  } catch (error) {
    console.error('Error saving attendance:', error)
    if (error.response) {
      console.error('Response data:', error.response.data)
      console.error('Response status:', error.response.status)
      toast.error(`Failed to save attendance: ${error.response.data.message || error.response.statusText}`)
    } else {
      toast.error('Failed to save attendance - network error')
    }
  } finally {
    isSaving.value = false
  }
}

const exportData = async () => {
  isExporting.value = true

  try {
    const classId = getClassId()
    const { data } = await axios.get(`/faculty/attendance/class/${classId}/export`, {
      params: {
        start_date: exportStartDate.value,
        end_date: exportEndDate.value,
        format: exportFormat.value,
      }
    })

    if (data.success) {
      window.open(data.download_url, '_blank')
      toast.success('Export prepared successfully')
      showExportDialog.value = false
    } else {
      toast.error(data.message || 'Failed to export data')
    }
  } catch (error) {
    console.error('Error exporting data:', error)
    toast.error('Failed to export data')
  } finally {
    isExporting.value = false
  }
}

const markAllAbsent = () => {
  props.roster.forEach(student => {
    updateStudentAttendance(student, 'absent')
  })
  toast.success('All students marked as absent')
}

const getAttendanceRateBarColor = (rate) => {
  if (rate >= 90) return 'bg-green-500'
  if (rate >= 75) return 'bg-yellow-500'
  return 'bg-red-500'
}

const getAttendanceRateBadgeVariant = (rate) => {
  if (rate >= 90) return 'default'
  if (rate >= 75) return 'secondary'
  return 'destructive'
}

const handleResetClick = () => {
  const classId = getClassId()
  console.log('Reset button clicked', {
    confirmationText: resetConfirmationText.value,
    isResetting: isResetting.value,
    classId: classId,
    propsClassId: props.class?.id
  })

  if (!classId) {
    toast.error('Class ID is missing. Please refresh the page.')
    return
  }

  resetAllAttendance()
}

const resetAllAttendance = async () => {
  if (resetConfirmationText.value !== 'RESET') {
    toast.error('Please type "RESET" to confirm')
    return
  }

  isResetting.value = true

  try {
    const classId = getClassId()
    console.log('Resetting attendance for class:', classId)

    const response = await axios.delete(`/faculty/classes/${classId}/attendance/reset`)

    console.log('Reset response:', response.data)

    if (response.data.success) {
      // Close dialog first so toast is visible above overlay
      showResetDialog.value = false
      toast.success('All attendance data has been reset successfully')
      resetConfirmationText.value = ''

      // Optimistically clear local roster/session state without redirect
      selectedDate.value = new Date().toISOString().split('T')[0]
      attendanceData.value = {}

      // Update the stats to zeros to reflect reset
      if (props.session_stats) {
        props.session_stats.present = 0
        props.session_stats.absent = 0
        props.session_stats.late = 0
        props.session_stats.total = props.roster?.length || 0
        props.session_stats.attendance_rate = 0
      }
    } else {
      toast.error(response.data.message || 'Failed to reset attendance data')
    }
  } catch (error) {
    console.error('Failed to reset attendance:', error)
    if (error.response) {
      console.error('Response data:', error.response.data)
      console.error('Response status:', error.response.status)
      toast.error(`Failed to reset attendance: ${error.response.data.message || error.response.statusText}`)
    } else {
      toast.error('Failed to reset attendance data - network error')
    }
  } finally {
    isResetting.value = false
  }
}

const goBack = () => {
  router.visit('/faculty/attendance')
}
</script>
