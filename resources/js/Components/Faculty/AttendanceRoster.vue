<template>
  <div class="space-y-4">
    <!-- Session Statistics -->
    <Card v-if="sessionStats">
      <CardContent class="p-4">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
          <div class="text-center">
            <div class="text-2xl font-bold text-foreground">{{ sessionStats.total }}</div>
            <div class="text-sm text-muted-foreground">Total Students</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-green-600">{{ sessionStats.present }}</div>
            <div class="text-sm text-muted-foreground">Present</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-red-600">{{ sessionStats.absent }}</div>
            <div class="text-sm text-muted-foreground">Absent</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-yellow-600">{{ sessionStats.late }}</div>
            <div class="text-sm text-muted-foreground">Late</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-primary">{{ sessionStats.attendance_rate }}%</div>
            <div class="text-sm text-muted-foreground">Attendance Rate</div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Quick Actions -->
    <Card>
      <CardContent class="p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center space-x-2">
            <Button size="sm" @click="markAllPresent" :disabled="loading">
              <CheckIcon class="w-4 h-4 mr-2" />
              Mark All Present
            </Button>
            <Button size="sm" variant="outline" @click="markAllAbsent" :disabled="loading">
              <XMarkIcon class="w-4 h-4 mr-2" />
              Mark All Absent
            </Button>
            <Button size="sm" variant="outline" @click="resetAll" :disabled="loading">
              <ArrowPathIcon class="w-4 h-4 mr-2" />
              Reset All
            </Button>
          </div>
          
          <div class="flex items-center space-x-2">
            <Button size="sm" variant="outline" @click="showBulkUpdate = true">
              <PencilSquareIcon class="w-4 h-4 mr-2" />
              Bulk Edit
            </Button>
            <Button size="sm" @click="saveChanges" :disabled="loading || !hasChanges">
              <CloudArrowUpIcon class="w-4 h-4 mr-2" />
              Save Changes
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Student Roster -->
    <Card>
      <CardContent class="p-0">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-muted/50">
              <tr>
                <th class="text-left p-4 font-medium text-foreground">Student</th>
                <th class="text-center p-4 font-medium text-foreground">Status</th>
                <th class="text-center p-4 font-medium text-foreground">Time Marked</th>
                <th class="text-center p-4 font-medium text-foreground">Remarks</th>
                <th class="text-center p-4 font-medium text-foreground">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="student in roster"
                :key="student.student_id"
                class="border-b border-border hover:bg-muted/25"
              >
                <!-- Student Info -->
                <td class="p-4">
                  <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                      <img
                        v-if="student.student.photo"
                        :src="student.student.photo"
                        :alt="student.student.name"
                        class="w-10 h-10 rounded-full object-cover"
                      />
                      <div
                        v-else
                        class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center"
                      >
                        <UserIcon class="w-5 h-5 text-primary" />
                      </div>
                    </div>
                    <div>
                      <div class="font-medium text-foreground">{{ student.student.name }}</div>
                      <div class="text-sm text-muted-foreground">ID: {{ student.student_id }}</div>
                    </div>
                  </div>
                </td>

                <!-- Status -->
                <td class="p-4 text-center">
                  <select
                    v-model="student.attendance.status"
                    @change="markStatusChange(student.student_id)"
                    :class="getStatusClass(student.attendance.status)"
                    class="px-3 py-1 rounded-md border border-border text-sm font-medium"
                  >
                    <option value="present">Present</option>
                    <option value="absent">Absent</option>
                    <option value="late">Late</option>
                    <option value="excused">Excused</option>
                  </select>
                </td>

                <!-- Time Marked -->
                <td class="p-4 text-center text-sm text-muted-foreground">
                  {{ formatTime(student.attendance.marked_at) }}
                </td>

                <!-- Remarks -->
                <td class="p-4">
                  <input
                    type="text"
                    v-model="student.attendance.remarks"
                    @input="markStatusChange(student.student_id)"
                    placeholder="Add remarks..."
                    class="w-full px-2 py-1 text-sm border border-border rounded focus:outline-none focus:ring-1 focus:ring-primary"
                  />
                </td>

                <!-- Actions -->
                <td class="p-4 text-center">
                  <div class="flex items-center justify-center space-x-2">
                    <Button
                      size="sm"
                      variant="outline"
                      @click="quickMarkPresent(student.student_id)"
                      :disabled="loading"
                    >
                      <CheckIcon class="w-4 h-4" />
                    </Button>
                    <Button
                      size="sm"
                      variant="outline"
                      @click="quickMarkAbsent(student.student_id)"
                      :disabled="loading"
                    >
                      <XMarkIcon class="w-4 h-4" />
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>

    <!-- Bulk Update Modal -->
    <BulkAttendanceModal
      v-if="showBulkUpdate"
      :roster="roster"
      @close="showBulkUpdate = false"
      @bulk-update="handleBulkUpdate"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Card, CardContent } from '@/Components/ui/card.js'
import { Button } from '@/Components/ui/button.js'
import BulkAttendanceModal from './BulkAttendanceModal.vue'
import {
  CheckIcon,
  XMarkIcon,
  ArrowPathIcon,
  PencilSquareIcon,
  CloudArrowUpIcon,
  UserIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  roster: Array,
  sessionStats: Object,
  settings: Object,
  loading: Boolean
})

const emit = defineEmits(['update-attendance', 'bulk-update'])

const showBulkUpdate = ref(false)
const changedStudents = ref(new Set())

const hasChanges = computed(() => changedStudents.value.size > 0)

const getStatusClass = (status) => {
  const classes = {
    present: 'bg-green-50 text-green-700 border-green-200',
    absent: 'bg-red-50 text-red-700 border-red-200',
    late: 'bg-yellow-50 text-yellow-700 border-yellow-200',
    excused: 'bg-blue-50 text-blue-700 border-blue-200'
  }
  return classes[status] || 'bg-gray-50 text-gray-700 border-gray-200'
}

const formatTime = (timestamp) => {
  if (!timestamp) return '-'
  return new Date(timestamp).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const markStatusChange = (studentId) => {
  changedStudents.value.add(studentId)
}

const quickMarkPresent = (studentId) => {
  const student = props.roster.find(s => s.student_id === studentId)
  if (student) {
    student.attendance.status = 'present'
    emit('update-attendance', studentId, 'present', student.attendance.remarks)
  }
}

const quickMarkAbsent = (studentId) => {
  const student = props.roster.find(s => s.student_id === studentId)
  if (student) {
    student.attendance.status = 'absent'
    emit('update-attendance', studentId, 'absent', student.attendance.remarks)
  }
}

const markAllPresent = () => {
  const updates = props.roster.map(student => ({
    student_id: student.student_id,
    status: 'present',
    remarks: student.attendance.remarks
  }))
  emit('bulk-update', updates)
}

const markAllAbsent = () => {
  const updates = props.roster.map(student => ({
    student_id: student.student_id,
    status: 'absent',
    remarks: student.attendance.remarks
  }))
  emit('bulk-update', updates)
}

const resetAll = () => {
  const defaultStatus = props.settings?.policy?.default_status || 'present'
  const updates = props.roster.map(student => ({
    student_id: student.student_id,
    status: defaultStatus,
    remarks: ''
  }))
  emit('bulk-update', updates)
}

const saveChanges = () => {
  const updates = props.roster
    .filter(student => changedStudents.value.has(student.student_id))
    .map(student => ({
      student_id: student.student_id,
      status: student.attendance.status,
      remarks: student.attendance.remarks
    }))
  
  if (updates.length > 0) {
    emit('bulk-update', updates)
    changedStudents.value.clear()
  }
}

const handleBulkUpdate = (updates) => {
  emit('bulk-update', updates)
  showBulkUpdate.value = false
}
</script>
