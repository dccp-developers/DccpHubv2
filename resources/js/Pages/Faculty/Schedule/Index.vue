<template>
  <FacultyLayout title="Faculty Schedule">
    <template #header>
      <div class="flex flex-col space-y-4 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0 flex-1">
          <h2 class="text-xl sm:text-2xl font-bold text-foreground">My Schedule</h2>
          <p class="text-xs sm:text-sm text-muted-foreground mt-1">
            {{ currentSemester }} Semester {{ schoolYear }}
          </p>
        </div>
        <div class="flex items-center space-x-2">
          <Button
            variant="outline"
            size="sm"
            @click="openExportDialog"
            :disabled="exportState.isExporting"
            class="hidden sm:flex"
          >
            <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
            {{ exportState.isExporting ? 'Exporting...' : 'Export' }}
          </Button>
          <Button
            variant="outline"
            size="sm"
            @click="openExportDialog"
            :disabled="exportState.isExporting"
            class="sm:hidden"
          >
            <ArrowDownTrayIcon class="w-4 h-4" />
          </Button>
          <Button variant="outline" size="sm" @click="refreshSchedule" class="hidden sm:flex">
            <ArrowPathIcon class="w-4 h-4 mr-2" />
            Refresh
          </Button>
          <Button variant="outline" size="sm" @click="refreshSchedule" class="sm:hidden">
            <ArrowPathIcon class="w-4 h-4" />
          </Button>
        </div>
      </div>
    </template>

    <!-- Error State -->
    <template v-if="hasError">
      <div class="mb-6">
        <Alert variant="destructive">
          <ExclamationTriangleIcon class="h-4 w-4" />
          <AlertTitle>Error</AlertTitle>
          <AlertDescription>{{ error }}</AlertDescription>
        </Alert>
      </div>
    </template>

    <!-- Schedule Content -->
    <template v-else>
      <div class="space-y-6">
        <!-- Schedule Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <Card v-for="(stat, key) in scheduleStatsDisplay" :key="key" class="hover:shadow-md transition-shadow">
            <CardContent class="p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-2xl font-bold text-foreground">{{ stat.value }}</p>
                  <p class="text-sm font-medium text-muted-foreground">{{ stat.label }}</p>
                </div>
                <div :class="`w-10 h-10 rounded-lg bg-${stat.color}-100 dark:bg-${stat.color}-900/20 flex items-center justify-center`">
                  <component :is="stat.icon" class="w-5 h-5" :class="`text-${stat.color}-600 dark:text-${stat.color}-400`" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Filters and View Controls -->
        <Card>
          <CardContent class="p-3 sm:p-4">
            <div class="space-y-4">
              <!-- Top Row: View Mode and Date -->
              <div class="flex flex-col space-y-3 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-col space-y-3 sm:space-y-0 sm:flex-row sm:items-center sm:space-x-4">
                  <!-- View Mode Selector -->
                  <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-foreground whitespace-nowrap">View:</label>
                    <Select v-model="filters.view" @update:modelValue="updateFilters">
                      <SelectTrigger class="w-28 sm:w-32">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="day">Day</SelectItem>
                        <SelectItem value="week">Week</SelectItem>
                        <SelectItem value="list">List</SelectItem>
                        <SelectItem value="month">Month</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Date Picker -->
                  <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-foreground whitespace-nowrap">Date:</label>
                    <Input
                      type="date"
                      v-model="filters.date"
                      @change="updateFilters"
                      class="w-36 sm:w-40"
                    />
                  </div>
                </div>

                <!-- Navigation Controls (Mobile) -->
                <div class="flex items-center justify-center space-x-2 sm:hidden">
                  <Button variant="outline" size="sm" @click="navigateDate(-1)">
                    <ChevronLeftIcon class="w-4 h-4" />
                  </Button>
                  <Button variant="outline" size="sm" @click="goToToday">
                    Today
                  </Button>
                  <Button variant="outline" size="sm" @click="navigateDate(1)">
                    <ChevronRightIcon class="w-4 h-4" />
                  </Button>
                </div>
              </div>

              <!-- Bottom Row: Additional Filters -->
              <div class="flex flex-col space-y-2 sm:space-y-0 sm:flex-row sm:items-center sm:space-x-2">
                <Select v-model="filters.subject" @update:modelValue="updateFilters">
                  <SelectTrigger class="w-full sm:w-40">
                    <SelectValue placeholder="All Subjects" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Subjects</SelectItem>
                    <SelectItem v-for="subject in filterOptions.subjects" :key="subject.id" :value="subject.code">
                      {{ subject.code }}
                    </SelectItem>
                  </SelectContent>
                </Select>

                <Select v-model="filters.room" @update:modelValue="updateFilters">
                  <SelectTrigger class="w-full sm:w-40">
                    <SelectValue placeholder="All Rooms" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Rooms</SelectItem>
                    <SelectItem v-for="room in filterOptions.rooms" :key="room.id" :value="room.name">
                      {{ room.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Schedule Views -->
        <div class="space-y-6">
          <!-- Today's Schedule (Mobile Only) -->
          <div class="block lg:hidden">
            <Card>
              <CardHeader class="pb-3">
                <div class="flex items-center justify-between">
                  <CardTitle class="text-base">Today's Classes</CardTitle>
                  <span class="text-xs text-muted-foreground">{{ getCurrentDateFormatted() }}</span>
                </div>
              </CardHeader>
              <CardContent>
                <div v-if="todaysSchedule.length === 0" class="text-center py-6">
                  <CalendarIcon class="mx-auto h-8 w-8 text-muted-foreground" />
                  <h3 class="mt-2 text-sm font-medium text-foreground">No classes today</h3>
                  <p class="mt-1 text-xs text-muted-foreground">Enjoy your free day!</p>
                </div>
                <div v-else class="space-y-2">
                  <div
                    v-for="schedule in todaysSchedule.slice(0, 3)"
                    :key="schedule.id"
                    class="p-2 rounded-lg border border-border hover:bg-accent transition-colors cursor-pointer"
                    @click="viewScheduleDetails(schedule)"
                  >
                    <div class="flex items-center justify-between mb-1">
                      <h4 class="font-medium text-xs">{{ schedule.subject_code }}</h4>
                      <Badge :variant="getStatusVariant(schedule.status)" class="text-xs">
                        {{ getStatusLabel(schedule.status) }}
                      </Badge>
                    </div>
                    <p class="text-xs text-muted-foreground">
                      {{ schedule.start_time }} - {{ schedule.end_time }} • Room {{ schedule.room }}
                    </p>
                  </div>
                  <div v-if="todaysSchedule.length > 3" class="text-center pt-2">
                    <Button variant="ghost" size="sm" class="text-xs">
                      View {{ todaysSchedule.length - 3 }} more
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- Main Content Grid -->
          <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Today's Schedule Sidebar (Desktop Only) -->
            <div class="hidden lg:block lg:col-span-1">
              <Card>
                <CardHeader>
                  <CardTitle class="text-lg">Today's Classes</CardTitle>
                  <CardDescription>{{ getCurrentDateFormatted() }}</CardDescription>
                </CardHeader>
                <CardContent>
                  <div v-if="todaysSchedule.length === 0" class="text-center py-8">
                    <CalendarIcon class="mx-auto h-12 w-12 text-muted-foreground" />
                    <h3 class="mt-2 text-sm font-medium text-foreground">No classes today</h3>
                    <p class="mt-1 text-sm text-muted-foreground">Enjoy your free day!</p>
                  </div>
                  <div v-else class="space-y-3">
                    <div
                      v-for="schedule in todaysSchedule"
                      :key="schedule.id"
                      class="p-3 rounded-lg border border-border hover:bg-accent transition-colors cursor-pointer"
                      @click="viewScheduleDetails(schedule)"
                    >
                      <div class="flex items-center justify-between mb-2">
                        <h4 class="font-semibold text-sm">{{ schedule.subject_code }}</h4>
                        <Badge :variant="getStatusVariant(schedule.status)" class="text-xs">
                          {{ getStatusLabel(schedule.status) }}
                        </Badge>
                      </div>
                      <p class="text-xs text-muted-foreground">
                        {{ schedule.start_time }} - {{ schedule.end_time }}
                      </p>
                      <p class="text-xs text-muted-foreground">
                        Room {{ schedule.room }} • Sec {{ schedule.section }}
                      </p>
                      <div class="flex items-center mt-2">
                        <div :class="`w-3 h-3 rounded-full bg-${schedule.color} mr-2`"></div>
                        <span class="text-xs text-muted-foreground">{{ schedule.duration }}</span>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <!-- Schedule Overview -->
              <Card class="mt-6">
                <CardHeader>
                  <CardTitle class="text-lg">Overview</CardTitle>
                </CardHeader>
                <CardContent>
                  <div class="space-y-4">
                    <div class="flex justify-between items-center">
                      <span class="text-sm text-muted-foreground">Total Classes</span>
                      <span class="font-semibold">{{ scheduleOverview.total_classes || 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                      <span class="text-sm text-muted-foreground">Total Hours</span>
                      <span class="font-semibold">{{ scheduleOverview.total_hours || 0 }}h</span>
                    </div>
                    <div class="flex justify-between items-center">
                      <span class="text-sm text-muted-foreground">Days with Classes</span>
                      <span class="font-semibold">{{ scheduleOverview.days_with_classes || 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                      <span class="text-sm text-muted-foreground">Busiest Day</span>
                      <span class="font-semibold">{{ scheduleOverview.busiest_day || 'N/A' }}</span>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>

            <!-- Main Schedule View -->
            <div class="lg:col-span-3">
              <Card>
                <CardHeader>
                  <div class="flex flex-col space-y-2 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
                    <CardTitle class="text-base sm:text-lg">
                      {{ getViewTitle() }}
                    </CardTitle>
                    <div class="hidden sm:flex items-center space-x-2">
                      <Button variant="outline" size="sm" @click="navigateDate(-1)">
                        <ChevronLeftIcon class="w-4 h-4" />
                      </Button>
                      <Button variant="outline" size="sm" @click="goToToday">
                        Today
                      </Button>
                      <Button variant="outline" size="sm" @click="navigateDate(1)">
                        <ChevronRightIcon class="w-4 h-4" />
                      </Button>
                    </div>
                  </div>
                </CardHeader>
                <CardContent class="p-3 sm:p-6">
                  <!-- Week View -->
                  <div v-if="filters.view === 'week'" class="overflow-x-auto">
                    <WeeklyScheduleView
                      :weekly-schedule="weeklySchedule"
                      :current-date="filters.date"
                      @schedule-click="viewScheduleDetails"
                    />
                  </div>

                  <!-- Day View -->
                  <div v-else-if="filters.view === 'day'" class="space-y-4">
                    <DailyScheduleView
                      :daily-schedule="getDailySchedule()"
                      :current-date="filters.date"
                      @schedule-click="viewScheduleDetails"
                    />
                  </div>

                  <!-- List View -->
                  <div v-else-if="filters.view === 'list'" class="space-y-4">
                    <ListView
                      :weekly-schedule="weeklySchedule"
                      :current-date="filters.date"
                      @schedule-click="viewScheduleDetails"
                    />
                  </div>

                  <!-- Month View -->
                  <div v-else-if="filters.view === 'month'" class="space-y-4">
                    <MonthlyScheduleView
                      :monthly-schedule="getMonthlySchedule()"
                      :current-date="filters.date"
                      @schedule-click="viewScheduleDetails"
                      @date-click="selectDate"
                    />
                  </div>
                </CardContent>
              </Card>
            </div>
          </div>
        </div>
      </div>
    </template>
    <!-- Export Dialog -->
    <Dialog :open="exportState.showDialog" @update:open="exportState.showDialog = $event">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Export Schedule</DialogTitle>
          <DialogDescription>
            Choose the format and period for your schedule export
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium">Format</label>
            <Select v-model="exportState.options.format">
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="pdf">PDF</SelectItem>
                <SelectItem value="excel">Excel</SelectItem>
                <SelectItem value="csv">CSV</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div>
            <label class="text-sm font-medium">Period</label>
            <Select v-model="exportState.options.period">
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="week">Current Week</SelectItem>
                <SelectItem value="month">Current Month</SelectItem>
                <SelectItem value="semester">Current Semester</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
        <DialogFooter>
          <Button
            variant="outline"
            @click="closeExportDialog"
            :disabled="exportState.isExporting"
          >
            Cancel
          </Button>
          <Button
            @click="handleExport"
            :disabled="exportState.isExporting"
          >
            {{ exportState.isExporting ? 'Generating...' : 'Export' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>



    <!-- Schedule Details Modal -->
    <ScheduleDetailsModal
      :open="showDetailsModal"
      :schedule="selectedSchedule"
      @update:open="showDetailsModal = $event"
    />

    <!-- Sonner Toast Notifications -->
    <Toaster
      position="top-right"
      :duration="4000"
      :close-button="true"
      :rich-colors="true"
    />
  </FacultyLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { toast, Toaster } from 'vue-sonner'
import axios from 'axios'

// Route helper function
const route = (name, params = {}) => {
  const routes = {
    'faculty.schedule.index': '/faculty/schedule',
    'faculty.schedule.export': '/faculty/schedule/export'
  }
  return routes[name] || name
}
import FacultyLayout from '@/Layouts/FacultyLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Button } from '@/Components/shadcn/ui/button'
import { Input } from '@/Components/shadcn/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/shadcn/ui/select'
import { Badge } from '@/Components/shadcn/ui/badge'
import { Alert, AlertDescription, AlertTitle } from '@/Components/shadcn/ui/alert'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/shadcn/ui/dialog'
import {
  CalendarIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ArrowDownTrayIcon,
  ArrowPathIcon,
  ExclamationTriangleIcon,
  ClockIcon,
  AcademicCapIcon,
  UsersIcon
} from '@heroicons/vue/24/outline'

import WeeklyScheduleView from '@/Components/Faculty/Schedule/WeeklyScheduleView.vue'
import DailyScheduleView from '@/Components/Faculty/Schedule/DailyScheduleView.vue'
import MonthlyScheduleView from '@/Components/Faculty/Schedule/MonthlyScheduleView.vue'
import ListView from '@/Components/Faculty/Schedule/ListView.vue'
import ScheduleDetailsModal from '@/Components/Faculty/Schedule/ScheduleDetailsModal.vue'

const props = defineProps({
  weeklySchedule: {
    type: Object,
    default: () => ({})
  },
  todaysSchedule: {
    type: Array,
    default: () => []
  },
  scheduleOverview: {
    type: Object,
    default: () => ({})
  },
  scheduleStats: {
    type: Object,
    default: () => ({})
  },
  filterOptions: {
    type: Object,
    default: () => ({ subjects: [], rooms: [] })
  },
  currentSemester: {
    type: String,
    default: '1'
  },
  schoolYear: {
    type: String,
    default: '2024-2025'
  },
  error: {
    type: String,
    default: null
  }
})

// Reactive state
const filters = ref({
  view: 'week',
  date: new Date().toISOString().split('T')[0],
  subject: 'all',
  room: 'all'
})

const showDetailsModal = ref(false)
const selectedSchedule = ref(null)

// Export state management
const exportState = ref({
  showDialog: false,
  isExporting: false,
  options: {
    format: 'pdf',
    period: 'week'
  }
})

// Computed properties
const hasError = computed(() => !!props.error)

const scheduleStatsDisplay = computed(() => {
  const stats = props.scheduleStats || {}
  return {
    totalClasses: {
      label: 'Total Classes',
      value: stats.total_classes || 0,
      icon: AcademicCapIcon,
      color: 'blue'
    },
    totalStudents: {
      label: 'Total Students',
      value: stats.total_students || 0,
      icon: UsersIcon,
      color: 'green'
    },
    totalHours: {
      label: 'Total Hours',
      value: `${props.scheduleOverview.total_hours || 0}h`,
      icon: ClockIcon,
      color: 'purple'
    },
    daysWithClasses: {
      label: 'Active Days',
      value: props.scheduleOverview.days_with_classes || 0,
      icon: CalendarIcon,
      color: 'orange'
    }
  }
})

// Methods
const updateFilters = () => {
  router.get(route('faculty.schedule.index'), filters.value, {
    preserveState: true,
    preserveScroll: true
  })
}

const refreshSchedule = () => {
  router.reload({
    only: ['weeklySchedule', 'todaysSchedule', 'scheduleOverview', 'scheduleStats']
  })
}

const viewScheduleDetails = (schedule) => {
  selectedSchedule.value = schedule
  showDetailsModal.value = true
}

// ===== EXPORT FUNCTIONALITY =====
const openExportDialog = () => {
  exportState.value.showDialog = true
}

const closeExportDialog = () => {
  if (!exportState.value.isExporting) {
    exportState.value.showDialog = false
  }
}

const handleExport = async () => {
  if (exportState.value.isExporting) return

  exportState.value.isExporting = true

  try {
    console.log('Starting export with options:', exportState.value.options)

    // Use Axios which handles CSRF automatically
    const response = await axios.post(route('faculty.schedule.export'), {
      format: exportState.value.options.format,
      period: exportState.value.options.period
    }, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    console.log('Export response:', response.data)

    if (!response.data.success) {
      throw new Error(response.data.error || 'Export failed')
    }

    // Close dialog
    exportState.value.showDialog = false

    // Show success toast with download action
    toast.success('Export Completed Successfully', {
      description: `Your ${exportState.value.options.format.toUpperCase()} file has been generated`,
      action: {
        label: 'Download',
        onClick: () => {
          window.open(response.data.download_url, '_blank')
        }
      },
      duration: 10000 // Keep toast longer since it has an action
    })

  } catch (error) {
    console.error('Export failed:', error)

    let errorMessage = 'An unexpected error occurred while generating your export'

    if (error.response) {
      // Server responded with error status
      if (error.response.status === 419) {
        errorMessage = 'Session expired. Please refresh the page and try again.'
      } else if (error.response.data?.error) {
        errorMessage = error.response.data.error
      } else {
        errorMessage = `Server error: ${error.response.status}`
      }
    } else if (error.request) {
      // Request was made but no response received
      errorMessage = 'Network error. Please check your connection and try again.'
    } else {
      // Something else happened
      errorMessage = error.message || errorMessage
    }

    // Show error toast
    toast.error('Export Failed', {
      description: errorMessage
    })
  } finally {
    exportState.value.isExporting = false
  }
}

const getCurrentDateFormatted = () => {
  return new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

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

const getViewTitle = () => {
  const date = new Date(filters.value.date)
  const options = { year: 'numeric', month: 'long', day: 'numeric' }

  switch (filters.value.view) {
    case 'day':
      return `Day View - ${date.toLocaleDateString('en-US', options)}`
    case 'week':
      return `Week View - ${getWeekRange(date)}`
    case 'list':
      return `List View - ${getWeekRange(date)}`
    case 'month':
      return `Month View - ${date.toLocaleDateString('en-US', { year: 'numeric', month: 'long' })}`
    default:
      return 'Schedule View'
  }
}

const getWeekRange = (date) => {
  const startOfWeek = new Date(date)
  const day = startOfWeek.getDay()
  const diff = startOfWeek.getDate() - day + (day === 0 ? -6 : 1)
  startOfWeek.setDate(diff)

  const endOfWeek = new Date(startOfWeek)
  endOfWeek.setDate(startOfWeek.getDate() + 6)

  const options = { month: 'short', day: 'numeric' }
  return `${startOfWeek.toLocaleDateString('en-US', options)} - ${endOfWeek.toLocaleDateString('en-US', options)}`
}

const navigateDate = (direction) => {
  const currentDate = new Date(filters.value.date)

  switch (filters.value.view) {
    case 'day':
      currentDate.setDate(currentDate.getDate() + direction)
      break
    case 'week':
    case 'list':
      currentDate.setDate(currentDate.getDate() + (direction * 7))
      break
    case 'month':
      currentDate.setMonth(currentDate.getMonth() + direction)
      break
  }

  filters.value.date = currentDate.toISOString().split('T')[0]
  updateFilters()
}

const goToToday = () => {
  filters.value.date = new Date().toISOString().split('T')[0]
  updateFilters()
}

const selectDate = (date) => {
  filters.value.date = date
  filters.value.view = 'day'
  updateFilters()
}

const getDailySchedule = () => {
  const dayName = new Date(filters.value.date).toLocaleDateString('en-US', { weekday: 'long' })
  return props.weeklySchedule[dayName] || []
}

const getMonthlySchedule = () => {
  const monthly = {}
  Object.entries(props.weeklySchedule).forEach(([day, schedules]) => {
    schedules.forEach(schedule => {
      const date = schedule.date || filters.value.date
      if (!monthly[date]) monthly[date] = []
      monthly[date].push(schedule)
    })
  })
  return monthly
}
</script>
