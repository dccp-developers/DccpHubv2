<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-2xl max-h-[90vh] overflow-hidden">
      <DialogHeader>
        <DialogTitle class="flex items-center space-x-2">
          <div :class="`w-4 h-4 rounded-full bg-${schedule?.color || 'gray-500'}`"></div>
          <span>{{ schedule?.subject_code || 'Class Details' }}</span>
        </DialogTitle>
        <DialogDescription>
          {{ schedule?.subject_title || 'Class information and details' }}
        </DialogDescription>
      </DialogHeader>

      <div v-if="schedule" class="space-y-6 overflow-y-auto max-h-[60vh]">
        <!-- Basic Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Card>
            <CardHeader class="pb-3">
              <CardTitle class="text-base">Schedule Information</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-muted-foreground">Day</span>
                <span class="font-medium">{{ schedule.day_of_week }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-muted-foreground">Time</span>
                <span class="font-medium">{{ schedule.start_time }} - {{ schedule.end_time }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-muted-foreground">Duration</span>
                <span class="font-medium">{{ schedule.duration }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-muted-foreground">Status</span>
                <Badge :variant="getStatusVariant(schedule.status)">
                  {{ getStatusLabel(schedule.status) }}
                </Badge>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader class="pb-3">
              <CardTitle class="text-base">Class Information</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-muted-foreground">Section</span>
                <span class="font-medium">{{ schedule.section }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-muted-foreground">Room</span>
                <span class="font-medium">{{ schedule.room }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-muted-foreground">Students</span>
                <span class="font-medium">{{ schedule.student_count || 0 }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-muted-foreground">Class ID</span>
                <span class="font-medium text-xs">{{ schedule.class_id }}</span>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Students List -->
        <Card v-if="schedule.students && schedule.students.length > 0">
          <CardHeader class="pb-3">
            <div class="flex items-center justify-between">
              <CardTitle class="text-base">Enrolled Students</CardTitle>
              <Badge variant="secondary">{{ schedule.students.length }} students</Badge>
            </div>
          </CardHeader>
          <CardContent>
            <div class="max-h-48 overflow-y-auto">
              <div class="space-y-2">
                <div
                  v-for="student in schedule.students"
                  :key="student.id"
                  class="flex items-center justify-between p-2 rounded-lg border border-border hover:bg-accent transition-colors"
                >
                  <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
                      <span class="text-xs font-medium text-primary-foreground">
                        {{ student.name.charAt(0) }}
                      </span>
                    </div>
                    <div>
                      <p class="text-sm font-medium">{{ student.name }}</p>
                      <p class="text-xs text-muted-foreground">{{ student.student_id }}</p>
                    </div>
                  </div>
                  <Button variant="ghost" size="sm" @click="viewStudent(student)">
                    <EyeIcon class="w-4 h-4" />
                  </Button>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Quick Actions -->
        <Card>
          <CardHeader class="pb-3">
            <CardTitle class="text-base">Quick Actions</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
              <Button variant="outline" class="h-auto p-3 justify-start" @click="takeAttendance">
                <ClipboardDocumentListIcon class="w-4 h-4 mr-2" />
                <div class="text-left">
                  <div class="text-xs font-medium">Take</div>
                  <div class="text-xs text-muted-foreground">Attendance</div>
                </div>
              </Button>
              
              <Button variant="outline" class="h-auto p-3 justify-start" @click="manageGrades">
                <ChartBarIcon class="w-4 h-4 mr-2" />
                <div class="text-left">
                  <div class="text-xs font-medium">View</div>
                  <div class="text-xs text-muted-foreground">Grades</div>
                </div>
              </Button>

              <Button variant="outline" class="h-auto p-3 justify-start" @click="viewStudents">
                <UsersIcon class="w-4 h-4 mr-2" />
                <div class="text-left">
                  <div class="text-xs font-medium">View</div>
                  <div class="text-xs text-muted-foreground">Students</div>
                </div>
              </Button>

              <Button variant="outline" class="h-auto p-3 justify-start" @click="viewClassDetails">
                <EyeIcon class="w-4 h-4 mr-2" />
                <div class="text-left">
                  <div class="text-xs font-medium">View</div>
                  <div class="text-xs text-muted-foreground">Class Details</div>
                </div>
              </Button>
            </div>
          </CardContent>
        </Card>

        <!-- Class Progress (for ongoing classes) -->
        <Card v-if="schedule.status === 'ongoing'">
          <CardHeader class="pb-3">
            <CardTitle class="text-base">Class Progress</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div class="flex items-center justify-between text-sm">
                <span class="text-muted-foreground">Time Elapsed</span>
                <span class="font-medium">{{ getClassProgress() }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                <div 
                  class="bg-green-600 h-2 rounded-full transition-all duration-300"
                  :style="{ width: getClassProgress() + '%' }"
                ></div>
              </div>
              <div class="flex items-center justify-between text-xs text-muted-foreground">
                <span>Started: {{ schedule.start_time }}</span>
                <span>Ends: {{ schedule.end_time }}</span>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Additional Information -->
        <Card>
          <CardHeader class="pb-3">
            <CardTitle class="text-base">Additional Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-3 text-sm">
              <div class="flex items-center space-x-2">
                <CalendarIcon class="w-4 h-4 text-muted-foreground" />
                <span class="text-muted-foreground">This is a recurring weekly class</span>
              </div>
              <div class="flex items-center space-x-2">
                <ClockIcon class="w-4 h-4 text-muted-foreground" />
                <span class="text-muted-foreground">Class duration: {{ schedule.duration }}</span>
              </div>
              <div class="flex items-center space-x-2">
                <MapPinIcon class="w-4 h-4 text-muted-foreground" />
                <span class="text-muted-foreground">Location: Room {{ schedule.room }}</span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <DialogFooter class="mt-6">
        <Button variant="outline" @click="$emit('update:open', false)">
          Close
        </Button>
        <Button @click="viewFullClass">
          <ArrowTopRightOnSquareIcon class="w-4 h-4 mr-2" />
          View Full Class
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/shadcn/ui/dialog'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Button } from '@/Components/shadcn/ui/button'
import { Badge } from '@/Components/shadcn/ui/badge'
import {
  CalendarIcon,
  ClockIcon,
  MapPinIcon,
  EyeIcon,
  ClipboardDocumentListIcon,
  ChartBarIcon,
  DocumentTextIcon,
  BellIcon,
  UsersIcon,
  ArrowTopRightOnSquareIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  open: {
    type: Boolean,
    default: false
  },
  schedule: {
    type: Object,
    default: null
  }
})

defineEmits(['update:open'])

const getStatusVariant = (status) => {
  switch (status) {
    case 'ongoing': return 'default'
    case 'upcoming': return 'secondary'
    case 'completed': return 'outline'
    default: return 'secondary'
  }
}

const getStatusLabel = (status) => {
  switch (status) {
    case 'ongoing': return 'In Progress'
    case 'upcoming': return 'Upcoming'
    case 'completed': return 'Completed'
    default: return 'Scheduled'
  }
}

const getClassProgress = () => {
  if (!props.schedule || props.schedule.status !== 'ongoing') return 0
  
  const now = new Date()
  const currentMinutes = now.getHours() * 60 + now.getMinutes()
  const startMinutes = timeToMinutes(props.schedule.raw_start_time)
  const endMinutes = timeToMinutes(props.schedule.raw_end_time)
  
  const totalDuration = endMinutes - startMinutes
  const elapsed = currentMinutes - startMinutes
  
  return Math.min(Math.max(Math.round((elapsed / totalDuration) * 100), 0), 100)
}

const timeToMinutes = (timeString) => {
  const [hours, minutes] = timeString.split(':').map(Number)
  return hours * 60 + minutes
}

const viewStudent = (student) => {
  router.visit(route('faculty.students.show', { student: student.id }))
}

const takeAttendance = () => {
  // Navigate to attendance page
  console.log('Take attendance for class:', props.schedule.class_id)
}

const manageGrades = () => {
  // Navigate to grades page
  console.log('View grades for class:', props.schedule.class_id)
}

const viewStudents = () => {
  // Navigate to students page for this class
  router.visit(route('faculty.students.index', { class: props.schedule.class_id }))
}

const viewClassDetails = () => {
  // Navigate to class details page
  router.visit(route('faculty.classes.show', { class: props.schedule.class_id }))
}

const viewFullClass = () => {
  router.visit(route('faculty.classes.show', { class: props.schedule.class_id }))
}
</script>
