<template>
  <FacultyLayout title="Attendance Reports">
    <template #header>
      <div class="flex flex-col space-y-2 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0 flex-1">
          <h2 class="text-xl font-semibold text-foreground truncate">
            Attendance Reports
          </h2>
          <p class="text-sm text-muted-foreground mt-1">
            Generate and view detailed attendance reports for your classes
          </p>
        </div>
        <div class="flex items-center space-x-2 flex-shrink-0">
          <Button @click="generateReport" :disabled="!selectedClass || isGenerating">
            <Icon icon="heroicons:document-chart-bar" class="w-4 h-4 mr-2" />
            {{ isGenerating ? 'Generating...' : 'Generate Report' }}
          </Button>
          <Button @click="goBack" variant="outline" size="sm">
            <Icon icon="heroicons:arrow-left" class="w-4 h-4 mr-2" />
            Back
          </Button>
        </div>
      </div>
    </template>

    <div class="space-y-6">
      <!-- Report Configuration -->
      <Card>
        <CardHeader>
          <CardTitle>Report Configuration</CardTitle>
          <CardDescription>Configure your attendance report parameters</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
              <Label>Select Class</Label>
              <Select v-model="selectedClass">
                <SelectTrigger>
                  <SelectValue placeholder="Choose a class" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem 
                    v-for="classData in classes" 
                    :key="classData.class.id"
                    :value="classData.class.id.toString()"
                  >
                    {{ classData.class.subject_code }} - {{ classData.class.Subject?.title || classData.class.ShsSubject?.title }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            
            <div>
              <Label>Start Date</Label>
              <Input type="date" v-model="startDate" />
            </div>
            
            <div>
              <Label>End Date</Label>
              <Input type="date" v-model="endDate" />
            </div>
            
            <div>
              <Label>Report Type</Label>
              <Select v-model="reportType">
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="summary">Summary Report</SelectItem>
                  <SelectItem value="detailed">Detailed Report</SelectItem>
                  <SelectItem value="student_wise">Student-wise Report</SelectItem>
                  <SelectItem value="date_wise">Date-wise Report</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Quick Stats -->
      <div v-if="selectedClassData" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Total Students</p>
                <p class="text-2xl font-bold">{{ selectedClassData.enrollment_count }}</p>
              </div>
              <div class="h-8 w-8 bg-blue-100 rounded-lg flex items-center justify-center">
                <Icon icon="heroicons:users" class="h-4 w-4 text-blue-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Attendance Rate</p>
                <p class="text-2xl font-bold" :class="getAttendanceRateColor(selectedClassData.attendance_stats.attendance_rate)">
                  {{ selectedClassData.attendance_stats.attendance_rate }}%
                </p>
              </div>
              <div class="h-8 w-8 bg-green-100 rounded-lg flex items-center justify-center">
                <Icon icon="heroicons:check-circle" class="h-4 w-4 text-green-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Total Sessions</p>
                <p class="text-2xl font-bold">{{ selectedClassData.attendance_stats.total }}</p>
              </div>
              <div class="h-8 w-8 bg-purple-100 rounded-lg flex items-center justify-center">
                <Icon icon="heroicons:calendar" class="h-4 w-4 text-purple-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Last Session</p>
                <p class="text-lg font-bold">
                  {{ selectedClassData.last_session ? formatDate(selectedClassData.last_session.date) : 'Never' }}
                </p>
              </div>
              <div class="h-8 w-8 bg-orange-100 rounded-lg flex items-center justify-center">
                <Icon icon="heroicons:clock" class="h-4 w-4 text-orange-600" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Report Preview -->
      <Card v-if="reportData">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle>{{ reportData.title }}</CardTitle>
              <CardDescription>{{ reportData.description }}</CardDescription>
            </div>
            <div class="flex items-center space-x-2">
              <Button @click="exportReport('csv')" variant="outline" size="sm">
                <Icon icon="heroicons:document-text" class="w-4 h-4 mr-2" />
                CSV
              </Button>
              <Button @click="exportReport('excel')" variant="outline" size="sm">
                <Icon icon="heroicons:table-cells" class="w-4 h-4 mr-2" />
                Excel
              </Button>
              <Button @click="exportReport('pdf')" variant="outline" size="sm">
                <Icon icon="heroicons:document" class="w-4 h-4 mr-2" />
                PDF
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <!-- Summary Report -->
          <div v-if="reportType === 'summary'" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="text-center p-4 border rounded-lg">
                <p class="text-2xl font-bold text-green-600">{{ reportData.stats.present_count }}</p>
                <p class="text-sm text-muted-foreground">Total Present</p>
              </div>
              <div class="text-center p-4 border rounded-lg">
                <p class="text-2xl font-bold text-red-600">{{ reportData.stats.absent }}</p>
                <p class="text-sm text-muted-foreground">Total Absent</p>
              </div>
              <div class="text-center p-4 border rounded-lg">
                <p class="text-2xl font-bold text-yellow-600">{{ reportData.stats.late }}</p>
                <p class="text-sm text-muted-foreground">Total Late</p>
              </div>
            </div>
          </div>

          <!-- Detailed Report -->
          <div v-if="reportType === 'detailed'" class="space-y-4">
            <div class="overflow-x-auto">
              <table class="w-full border-collapse border border-gray-200">
                <thead>
                  <tr class="bg-muted">
                    <th class="border border-gray-200 p-2 text-left">Student Name</th>
                    <th class="border border-gray-200 p-2 text-left">Student ID</th>
                    <th class="border border-gray-200 p-2 text-center">Present</th>
                    <th class="border border-gray-200 p-2 text-center">Absent</th>
                    <th class="border border-gray-200 p-2 text-center">Late</th>
                    <th class="border border-gray-200 p-2 text-center">Attendance Rate</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="student in reportData.students" :key="student.student.id">
                    <td class="border border-gray-200 p-2">{{ student.student.fullname || (student.student.first_name + ' ' + student.student.last_name) }}</td>
                    <td class="border border-gray-200 p-2">{{ student.student.student_lrn || student.student.student_id }}</td>
                    <td class="border border-gray-200 p-2 text-center">{{ student.stats.present }}</td>
                    <td class="border border-gray-200 p-2 text-center">{{ student.stats.absent }}</td>
                    <td class="border border-gray-200 p-2 text-center">{{ student.stats.late }}</td>
                    <td class="border border-gray-200 p-2 text-center" :class="getAttendanceRateColor(student.stats.attendance_rate)">
                      {{ student.stats.attendance_rate }}%
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Student-wise Report -->
          <div v-if="reportType === 'student_wise'" class="space-y-4">
            <div v-for="student in reportData.students" :key="student.student.id" class="border rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <div>
                  <h3 class="font-semibold">{{ student.student.fullname || (student.student.first_name + ' ' + student.student.last_name) }}</h3>
                  <p class="text-sm text-muted-foreground">{{ student.student.student_lrn || student.student.student_id }}</p>
                </div>
                <Badge :variant="student.stats.attendance_rate >= 75 ? 'success' : 'destructive'">
                  {{ student.stats.attendance_rate }}% Attendance
                </Badge>
              </div>
              <div class="grid grid-cols-4 gap-4 text-sm">
                <div>
                  <p class="text-muted-foreground">Present</p>
                  <p class="font-medium text-green-600">{{ student.stats.present }}</p>
                </div>
                <div>
                  <p class="text-muted-foreground">Absent</p>
                  <p class="font-medium text-red-600">{{ student.stats.absent }}</p>
                </div>
                <div>
                  <p class="text-muted-foreground">Late</p>
                  <p class="font-medium text-yellow-600">{{ student.stats.late }}</p>
                </div>
                <div>
                  <p class="text-muted-foreground">Total Sessions</p>
                  <p class="font-medium">{{ student.stats.total }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Date-wise Report -->
          <div v-if="reportType === 'date_wise'" class="space-y-4">
            <div class="overflow-x-auto">
              <table class="w-full border-collapse border border-gray-200">
                <thead>
                  <tr class="bg-muted">
                    <th class="border border-gray-200 p-2 text-left">Date</th>
                    <th class="border border-gray-200 p-2 text-center">Total Students</th>
                    <th class="border border-gray-200 p-2 text-center">Present</th>
                    <th class="border border-gray-200 p-2 text-center">Absent</th>
                    <th class="border border-gray-200 p-2 text-center">Late</th>
                    <th class="border border-gray-200 p-2 text-center">Attendance Rate</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="session in reportData.sessions" :key="session.date">
                    <td class="border border-gray-200 p-2">{{ formatDate(session.date) }}</td>
                    <td class="border border-gray-200 p-2 text-center">{{ session.stats.total }}</td>
                    <td class="border border-gray-200 p-2 text-center">{{ session.stats.present }}</td>
                    <td class="border border-gray-200 p-2 text-center">{{ session.stats.absent }}</td>
                    <td class="border border-gray-200 p-2 text-center">{{ session.stats.late }}</td>
                    <td class="border border-gray-200 p-2 text-center" :class="getAttendanceRateColor(session.stats.attendance_rate)">
                      {{ session.stats.attendance_rate }}%
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- No Data State -->
      <Card v-if="!reportData && !isGenerating">
        <CardContent class="p-12 text-center">
          <Icon icon="heroicons:document-chart-bar" class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
          <h3 class="text-lg font-semibold mb-2">No Report Generated</h3>
          <p class="text-muted-foreground mb-4">
            Select a class and date range, then click "Generate Report" to view attendance data.
          </p>
        </CardContent>
      </Card>
    </div>
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/shadcn/ui/select'
import { Icon } from '@iconify/vue'
import { toast } from 'vue-sonner'

const props = defineProps({
  faculty: Object,
  classes: Array,
  summary: Object,
})

// Reactive data
const selectedClass = ref('')
const startDate = ref('')
const endDate = ref('')
const reportType = ref('summary')
const reportData = ref(null)
const isGenerating = ref(false)

// Computed
const selectedClassData = computed(() => {
  if (!selectedClass.value) return null
  return props.classes.find(c => c.class.id.toString() === selectedClass.value)
})

// Initialize dates
onMounted(() => {
  const today = new Date()
  const thirtyDaysAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000)
  startDate.value = thirtyDaysAgo.toISOString().split('T')[0]
  endDate.value = today.toISOString().split('T')[0]
})

const getAttendanceRateColor = (rate) => {
  if (rate >= 90) return 'text-green-600'
  if (rate >= 75) return 'text-yellow-600'
  return 'text-red-600'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const generateReport = async () => {
  if (!selectedClass.value) {
    toast.error('Please select a class')
    return
  }

  isGenerating.value = true
  
  try {
    // Mock report generation - replace with actual API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Mock data - replace with actual API response
    reportData.value = {
      title: `${reportType.value.charAt(0).toUpperCase() + reportType.value.slice(1)} Report`,
      description: `${formatDate(startDate.value)} to ${formatDate(endDate.value)}`,
      stats: {
        total: 100,
        present: 85,
        present_count: 85,
        absent: 10,
        late: 5,
        attendance_rate: 85
      },
      students: [
        {
          student: {
            id: 1,
            first_name: 'John',
            last_name: 'Doe',
            student_id: '2024001'
          },
          stats: {
            total: 10,
            present: 8,
            absent: 1,
            late: 1,
            attendance_rate: 90
          }
        }
      ],
      sessions: [
        {
          date: '2024-01-15',
          stats: {
            total: 25,
            present: 22,
            absent: 2,
            late: 1,
            attendance_rate: 92
          }
        }
      ]
    }
    
    toast.success('Report generated successfully')
  } catch (error) {
    console.error('Error generating report:', error)
    toast.error('Failed to generate report')
  } finally {
    isGenerating.value = false
  }
}

const exportReport = (format) => {
  // Implementation for export functionality
  toast.success(`Exporting report as ${format.toUpperCase()}`)
}

const goBack = () => {
  router.visit(route('faculty.attendance.index'))
}
</script>
