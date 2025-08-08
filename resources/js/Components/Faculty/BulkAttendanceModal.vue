<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-background rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
      <!-- Header -->
      <div class="flex items-center justify-between p-6 border-b border-border">
        <h3 class="text-lg font-semibold text-foreground">Bulk Edit Attendance</h3>
        <Button variant="ghost" size="sm" @click="$emit('close')">
          <XMarkIcon class="w-5 h-5" />
        </Button>
      </div>

      <!-- Content -->
      <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
        <!-- Quick Actions -->
        <div class="mb-6">
          <div class="flex flex-wrap items-center gap-2">
            <Button size="sm" @click="selectAll">
              <CheckIcon class="w-4 h-4 mr-2" />
              Select All
            </Button>
            <Button size="sm" variant="outline" @click="selectNone">
              <XMarkIcon class="w-4 h-4 mr-2" />
              Select None
            </Button>
            <div class="border-l border-border h-6 mx-2"></div>
            <Button size="sm" @click="setSelectedStatus('present')" :disabled="selectedStudents.length === 0">
              Mark Selected Present
            </Button>
            <Button size="sm" variant="outline" @click="setSelectedStatus('absent')" :disabled="selectedStudents.length === 0">
              Mark Selected Absent
            </Button>
            <Button size="sm" variant="outline" @click="setSelectedStatus('late')" :disabled="selectedStudents.length === 0">
              Mark Selected Late
            </Button>
            <Button size="sm" variant="outline" @click="setSelectedStatus('excused')" :disabled="selectedStudents.length === 0">
              Mark Selected Excused
            </Button>
          </div>
        </div>

        <!-- Student List -->
        <div class="space-y-2">
          <div
            v-for="student in localRoster"
            :key="student.student_id"
            class="flex items-center space-x-4 p-3 border border-border rounded-lg hover:bg-muted/25"
          >
            <!-- Checkbox -->
            <input
              type="checkbox"
              :value="student.student_id"
              v-model="selectedStudents"
              class="rounded border-border"
            />

            <!-- Student Info -->
            <div class="flex items-center space-x-3 flex-1">
              <div class="flex-shrink-0">
                <img
                  v-if="student.student.photo"
                  :src="student.student.photo"
                  :alt="student.student.name"
                  class="w-8 h-8 rounded-full object-cover"
                />
                <div
                  v-else
                  class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center"
                >
                  <UserIcon class="w-4 h-4 text-primary" />
                </div>
              </div>
              <div class="flex-1">
                <div class="font-medium text-foreground">{{ student.student.name }}</div>
                <div class="text-sm text-muted-foreground">ID: {{ student.student_id }}</div>
              </div>
            </div>

            <!-- Status -->
            <div class="w-32">
              <select
                v-model="student.attendance.status"
                :class="getStatusClass(student.attendance.status)"
                class="w-full px-2 py-1 rounded border border-border text-sm font-medium"
              >
                <option value="present">Present</option>
                <option value="absent">Absent</option>
                <option value="late">Late</option>
                <option value="excused">Excused</option>
              </select>
            </div>

            <!-- Remarks -->
            <div class="w-48">
              <input
                type="text"
                v-model="student.attendance.remarks"
                placeholder="Remarks..."
                class="w-full px-2 py-1 text-sm border border-border rounded focus:outline-none focus:ring-1 focus:ring-primary"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-between p-6 border-t border-border">
        <div class="text-sm text-muted-foreground">
          {{ selectedStudents.length }} of {{ localRoster.length }} students selected
        </div>
        <div class="flex items-center space-x-3">
          <Button variant="outline" @click="$emit('close')">
            Cancel
          </Button>
          <Button @click="applyChanges">
            <CloudArrowUpIcon class="w-4 h-4 mr-2" />
            Apply Changes
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Button } from '@/Components/ui/button.js'
import {
  CheckIcon,
  XMarkIcon,
  CloudArrowUpIcon,
  UserIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  roster: Array
})

const emit = defineEmits(['close', 'bulk-update'])

// Create a local copy of the roster for editing
const localRoster = ref(JSON.parse(JSON.stringify(props.roster)))
const selectedStudents = ref([])

const getStatusClass = (status) => {
  const classes = {
    present: 'bg-green-50 text-green-700 border-green-200',
    absent: 'bg-red-50 text-red-700 border-red-200',
    late: 'bg-yellow-50 text-yellow-700 border-yellow-200',
    excused: 'bg-blue-50 text-blue-700 border-blue-200'
  }
  return classes[status] || 'bg-gray-50 text-gray-700 border-gray-200'
}

const selectAll = () => {
  selectedStudents.value = localRoster.value.map(student => student.student_id)
}

const selectNone = () => {
  selectedStudents.value = []
}

const setSelectedStatus = (status) => {
  selectedStudents.value.forEach(studentId => {
    const student = localRoster.value.find(s => s.student_id === studentId)
    if (student) {
      student.attendance.status = status
    }
  })
}

const applyChanges = () => {
  const updates = localRoster.value.map(student => ({
    student_id: student.student_id,
    status: student.attendance.status,
    remarks: student.attendance.remarks || null
  }))
  
  emit('bulk-update', updates)
}
</script>
