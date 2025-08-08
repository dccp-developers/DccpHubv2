<template>
  <FacultyLayout title="Attendance Management">
    <template #header>
      <div class="flex flex-col space-y-4 sm:space-y-0 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0 flex-1">
          <h1 class="text-2xl font-bold tracking-tight">
            Attendance Management
          </h1>
          <p class="text-muted-foreground mt-2 max-w-2xl">
            Streamlined attendance tracking for all your classes. Take attendance, monitor patterns, and generate reports in one place.
          </p>
        </div>
        <div class="flex items-center space-x-3 flex-shrink-0">
          <Button @click="router.visit('/faculty/attendance/reports')" variant="outline">
            <Icon icon="heroicons:document-chart-bar" class="w-4 h-4 mr-2" />
            View Reports
          </Button>
          <Button @click="router.visit('/faculty/attendance/analytics')" variant="outline">
            <Icon icon="heroicons:chart-pie" class="w-4 h-4 mr-2" />
            Analytics
          </Button>
        </div>
      </div>
    </template>

    <div class="space-y-6">
      <!-- Quick Actions & Today's Overview -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Today's Workflow -->
        <div class="lg:col-span-2">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Icon icon="heroicons:calendar-days" class="w-5 h-5" />
                Today's Attendance Workflow
              </CardTitle>
              <CardDescription>
                Streamlined process for managing daily attendance across all classes
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex items-center gap-2">
                  <Label for="workflow-date" class="text-sm font-medium">Date</Label>
                  <Input id="workflow-date" type="date" v-model="workflowDate" class="w-40" />
                </div>
                <div class="flex-1">
                  <Input v-model="searchQuery" placeholder="Search classes by code, title, or section..." />
                </div>
                <Button @click="refreshData" variant="outline" size="sm">
                  <Icon icon="heroicons:arrow-path" class="w-4 h-4 mr-2" />
                  Refresh
                </Button>
              </div>

              <!-- Quick Stats for Today -->
              <div class="grid grid-cols-3 gap-4 p-4 bg-muted/30 rounded-lg">
                <div class="text-center">
                  <div class="text-2xl font-bold text-primary">{{ todayStats.completed }}</div>
                  <div class="text-xs text-muted-foreground">Completed</div>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-orange-600">{{ todayStats.pending }}</div>
                  <div class="text-xs text-muted-foreground">Pending</div>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-muted-foreground">{{ todayStats.total }}</div>
                  <div class="text-xs text-muted-foreground">Total Classes</div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Quick Actions -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Icon icon="heroicons:bolt" class="w-5 h-5" />
              Quick Actions
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <Button @click="openBulkAttendance" class="w-full justify-start" variant="outline">
              <Icon icon="heroicons:clipboard-document-list" class="w-4 h-4 mr-2" />
              Bulk Take Attendance
            </Button>
            <Button @click="router.visit('/faculty/attendance/reports')" class="w-full justify-start" variant="outline">
              <Icon icon="heroicons:chart-bar" class="w-4 h-4 mr-2" />
              Generate Reports
            </Button>
            <Button @click="exportTodayData" class="w-full justify-start" variant="outline">
              <Icon icon="heroicons:arrow-down-tray" class="w-4 h-4 mr-2" />
              Export Today's Data
            </Button>
            <Button @click="openSettings" class="w-full justify-start" variant="outline">
              <Icon icon="heroicons:cog-6-tooth" class="w-4 h-4 mr-2" />
              Attendance Settings
            </Button>
          </CardContent>
        </Card>
      </div>
<Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Icon icon="heroicons:academic-cap" class="w-5 h-5" />
            Your Classes - {{ formatDate(workflowDate) }}
          </CardTitle>
          <CardDescription>
            Efficiently manage attendance for all your classes. Click "Take Attendance" to start.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <!-- Filter Tabs -->
            <Tabs :value="activeFilter" @update:value="activeFilter = $event">
              <TabsList class="grid w-full grid-cols-4">
                <TabsTrigger value="all">All Classes</TabsTrigger>
                <TabsTrigger value="pending">Pending Today</TabsTrigger>
                <TabsTrigger value="completed">Completed</TabsTrigger>
                <TabsTrigger value="attention">Need Attention</TabsTrigger>
              </TabsList>
            </Tabs>

            <!-- Class Cards -->
            <div class="space-y-3">
              <div v-for="classData in filteredClasses" :key="classData.class.id"
                   class="group border rounded-lg p-4 hover:bg-muted/30 transition-colors">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-4">
                    <!-- Status Indicator -->
                    <div class="flex items-center gap-2">
                      <div :class="getStatusIndicatorClass(classData)" class="w-3 h-3 rounded-full"></div>
                      <div class="font-medium">{{ classData.class.subject_code }}</div>
                    </div>

                    <!-- Class Info -->
                    <div class="hidden sm:flex items-center gap-6 text-sm text-muted-foreground">
                      <span>{{ classData.class.section }}</span>
                      <span>{{ classData.enrollment_count }} students</span>
                      <span class="font-medium" :class="getAttendanceRateColor(classData.attendance_stats?.attendance_rate ?? 0)">
                        {{ classData.attendance_stats.attendance_rate }}% attendance
                      </span>
                    </div>
                  </div>

                  <!-- Actions -->
                  <div class="flex items-center gap-2">
                    <Badge v-if="classData.needs_attention" variant="destructive" class="text-xs">
                      Needs Attention
                    </Badge>
                    <Button @click="openQuickManage(classData.class)" size="sm"
                            :variant="getActionButtonVariant(classData)">
                      <Icon :icon="getActionButtonIcon(classData)" class="w-4 h-4 mr-2" />
                      {{ getActionButtonText(classData) }}
                    </Button>
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="sm">
                          <Icon icon="heroicons:ellipsis-horizontal" class="w-4 h-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem @click="goToClassAttendance(classData.class.id)">
                          <Icon icon="heroicons:clipboard-document-list" class="w-4 h-4 mr-2" />
                          Full Attendance Page
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="viewClassReports(classData.class.id)">
                          <Icon icon="heroicons:chart-bar" class="w-4 h-4 mr-2" />
                          View Reports
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="exportClassData(classData.class.id)">
                          <Icon icon="heroicons:arrow-down-tray" class="w-4 h-4 mr-2" />
                          Export Data
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredClasses.length === 0" class="text-center py-12">
              <Icon icon="heroicons:academic-cap" class="w-12 h-12 mx-auto text-muted-foreground mb-4" />
              <h3 class="text-lg font-medium mb-2">No classes found</h3>
              <p class="text-muted-foreground">
                {{ activeFilter === 'all' ? 'You have no classes assigned.' : `No classes match the "${activeFilter}" filter.` }}
              </p>
            </div>
          </div>
        </CardContent>
      </Card>
      <!-- Analytics Dashboard -->
      <div class="space-y-6">
        <!-- Daily Absences by Class Chart -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Icon icon="heroicons:chart-line" class="w-5 h-5" />
              Daily Absences by Class
            </CardTitle>
            <CardDescription>
              Shows classes with the most absences for each day over the past week
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div v-if="dailyAbsencesData.length > 0">
              <LineChart
                :data="dailyAbsencesData"
                :categories="classCategories"
                index="date"
                :colors="chartColors"
                :show-legend="true"
                :show-grid-line="true"
                :y-formatter="(value) => `${value} absent`"
                class="h-[300px]"
              />
            </div>
            <div v-else class="h-[300px] flex items-center justify-center">
              <div class="text-center">
                <Icon icon="heroicons:chart-line" class="w-12 h-12 mx-auto text-muted-foreground mb-4" />
                <h3 class="text-lg font-medium mb-2">No Absence Data Available</h3>
                <p class="text-muted-foreground">
                  Start taking attendance to see daily absence patterns
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Classes Performance Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Classes Needing Attention -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Icon icon="heroicons:exclamation-triangle" class="w-5 h-5 text-orange-600" />
                Classes Needing Attention
              </CardTitle>
              <CardDescription>
                Classes with attendance rates below 75%
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="classesNeedingAttention.length > 0" class="space-y-3">
                <div v-for="classData in classesNeedingAttention" :key="classData.class.id"
                     class="flex items-center justify-between p-3 border rounded-lg">
                  <div>
                    <div class="font-medium">{{ classData.class.subject_code }}</div>
                    <div class="text-sm text-muted-foreground">{{ classData.class.section }}</div>
                  </div>
                  <div class="text-right">
                    <div class="text-lg font-bold text-orange-600">{{ classData.attendance_stats?.attendance_rate ?? 0 }}%</div>
                    <div class="text-xs text-muted-foreground">{{ classData.enrollment_count }} students</div>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8">
                <Icon icon="heroicons:check-circle" class="w-12 h-12 mx-auto text-green-600 mb-4" />
                <h3 class="text-lg font-medium mb-2">All Classes Performing Well</h3>
                <p class="text-muted-foreground">
                  No classes need immediate attention
                </p>
              </div>
            </CardContent>
          </Card>

          <!-- Overall Statistics -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Icon icon="heroicons:chart-pie" class="w-5 h-5" />
              Overall Statistics
            </CardTitle>
            <CardDescription>
              Summary of your attendance management
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-6">
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <div class="text-2xl font-bold">{{ summary.total_classes || 0 }}</div>
                <div class="text-sm text-muted-foreground">Total Classes</div>
              </div>
              <div class="space-y-2">
                <div class="text-2xl font-bold">{{ summary.total_students || 0 }}</div>
                <div class="text-sm text-muted-foreground">Total Students</div>
              </div>
              <div class="space-y-2">
                <div class="text-2xl font-bold text-green-600">{{ summary.overall_stats?.attendance_rate || 0 }}%</div>
                <div class="text-sm text-muted-foreground">Overall Attendance</div>
              </div>
              <div class="space-y-2">
                <div class="text-2xl font-bold text-orange-600">{{ summary.classes_needing_attention || 0 }}</div>
                <div class="text-sm text-muted-foreground">Need Attention</div>
              </div>
            </div>

            <!-- Progress indicators -->
            <div class="space-y-3">
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span>Attendance Rate</span>
                  <span>{{ summary.overall_stats?.attendance_rate || 0 }}%</span>
                </div>
                <Progress :value="summary.overall_stats?.attendance_rate || 0" class="h-2" />
              </div>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span>Classes Completed Today</span>
                  <span>{{ Math.round((todayStats.completed / todayStats.total) * 100) || 0 }}%</span>
                </div>
                <Progress :value="Math.round((todayStats.completed / todayStats.total) * 100) || 0" class="h-2" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Class Management Table -->
      
    </div>

    <!-- Quick Manage Attendance Sheet -->
    <Toaster richColors position="top-right" />

    <Sheet :open="manage.open" @update:open="val => (manage.open = val)">
      <SheetContent side="right" class="sm:max-w-3xl">
        <SheetHeader>
          <SheetTitle class="flex items-center gap-2">
            <Icon icon="heroicons:clipboard-document-list" class="w-5 h-5" />
            Manage Attendance — {{ manage.class?.subject_code }} ({{ manage.class?.section }})
          </SheetTitle>
          <SheetDescription>
            Mark attendance and adjust settings without leaving this page
          </SheetDescription>
        </SheetHeader>

        <div class="space-y-6 mt-4">
          <!-- Date + Actions -->
          <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <Label for="att-date" class="text-sm">Date</Label>
              <Input id="att-date" type="date" v-model="manage.date" class="w-44" />
              <Button size="sm" variant="outline" @click="loadRoster(manage.class?.id)">
                Reload
              </Button>
            </div>
            <div class="flex items-center gap-2">
              <Input v-model="manage.search" placeholder="Search students..." class="w-64" />
              <Button size="sm" variant="outline" @click="markAll('present')">Mark All Present</Button>
              <Button size="sm" variant="outline" @click="markAll('absent')">Mark All Absent</Button>
              <Button size="sm" variant="ghost" @click="exportCsv">Export CSV</Button>
              <Button size="sm" variant="ghost" @click="exportPdf">Export PDF</Button>
              <Button size="sm" :disabled="manage.loadingSave" @click="saveAttendance">
                <span v-if="manage.loadingSave" class="flex items-center gap-2">
                  <div class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></div>
                  Saving...
                </span>
                <span v-else>Save</span>
              </Button>
            </div>
          </div>

          <!-- Roster Table -->
          <div class="border rounded-lg overflow-hidden">
            <div class="max-h-[50vh] overflow-y-auto">
              <table class="w-full">
                <thead class="bg-muted/50 sticky top-0">
                  <tr>
                    <th class="text-left p-3 text-sm font-medium">Student</th>
                    <th class="text-left p-3 text-sm font-medium">Status</th>
                    <th class="text-left p-3 text-sm font-medium">Remarks</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in filteredRoster" :key="row.student_id" class="border-t">
                    <td class="p-3">
                      <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                          <span class="text-xs font-medium">{{ row.name?.charAt(0) }}</span>
                        </div>
                        <div>
                          <div class="font-medium">{{ row.name }}</div>
                          <div class="text-xs text-muted-foreground">{{ row.student_id }}</div>
                        </div>
                      </div>
                    </td>
                    <td class="p-3">
                      <Select v-model="row.status">
                        <SelectTrigger class="w-40">
                          <SelectValue placeholder="Select status" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="present">Present</SelectItem>

  <!-- Add Quick Manage button per class -->
                          <SelectItem value="absent">Absent</SelectItem>
                          <SelectItem value="late">Late</SelectItem>
                          <SelectItem value="excused">Excused</SelectItem>
                          <SelectItem value="partial">Partial</SelectItem>
                        </SelectContent>
                      </Select>
                    </td>
                    <td class="p-3">
                      <Input v-model="row.remarks" placeholder="Optional remarks" />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Settings -->
          <div class="border rounded-lg p-4">
            <div class="flex items-center justify-between">
              <div>
                <h4 class="font-medium">Attendance Settings</h4>
                <p class="text-sm text-muted-foreground">Configure method and policies for this class</p>
              </div>
              <Button size="sm" variant="outline" @click="saveSettings">Save Settings</Button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
              <div class="space-y-2">
                <Label>Method</Label>
                <Select v-model="manage.settings.attendance_method">
                  <SelectTrigger>
                    <SelectValue placeholder="Select method" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="manual">Manual Roll Call</SelectItem>
                    <SelectItem value="qr_code" disabled>QR Code Scanning</SelectItem>
                    <SelectItem value="attendance_code" disabled>Attendance Code</SelectItem>
                    <SelectItem value="self_checkin" disabled>Student Self Check-in</SelectItem>
                    <SelectItem value="hybrid" disabled>Hybrid (Manual + Student)</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-2">
                <Label>Policy</Label>
                <Select v-model="manage.settings.attendance_policy">
                  <SelectTrigger>
                    <SelectValue placeholder="Select policy" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="strict">Strict</SelectItem>
                    <SelectItem value="standard">Standard</SelectItem>
                    <SelectItem value="lenient">Lenient</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div class="flex items-center justify-between border rounded-lg p-3">
                <div>
                  <div class="font-medium">Allow late check-in</div>
                  <div class="text-xs text-muted-foreground">Students can be marked late after start time</div>
                </div>
                <Switch v-model:checked="manage.settings.allow_late_checkin" />
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div class="space-y-2">
                  <Label>Grace period (mins)</Label>
                  <Input type="number" min="0" max="60" v-model.number="manage.settings.grace_period_minutes" />
                </div>
                <div class="space-y-2">
                  <Label>Auto-absent (mins)</Label>
                  <Input type="number" min="0" v-model.number="manage.settings.auto_mark_absent_minutes" />
                </div>
              </div>

              <div class="flex items-center justify-between border rounded-lg p-3 md:col-span-2">
                <div>
                  <div class="font-medium">Notify absentees</div>
                  <div class="text-xs text-muted-foreground">Send notification to absent students</div>
                </div>
                <Switch v-model:checked="manage.settings.notify_absent_students" />
              </div>
            </div>
          </div>
        </div>
      </SheetContent>
    </Sheet>
    </div>

  </FacultyLayout>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import FacultyLayout from '@/Layouts/FacultyLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Button } from '@/Components/shadcn/ui/button'
import { Badge } from '@/Components/shadcn/ui/badge'
import { Progress } from '@/Components/shadcn/ui/progress'
import { Tabs, TabsList, TabsTrigger } from '@/Components/shadcn/ui/tabs'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger
} from '@/Components/shadcn/ui/dropdown-menu'
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/Components/shadcn/ui/sheet'
import { Input } from '@/Components/shadcn/ui/input'
import { Label } from '@/Components/shadcn/ui/label'
import { Toaster } from '@/Components/shadcn/ui/sonner'
import { toast } from 'vue-sonner'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue
} from '@/Components/shadcn/ui/select'
import { Switch } from '@/Components/shadcn/ui/switch'
import { LineChart } from '@/Components/shadcn/ui/chart-line'
import { Icon } from '@iconify/vue'

const props = defineProps({
  faculty: Object,
  classes: Array,
  summary: Object,
  attendance_statuses: Array,
  daily_absences: Object,
})

// Derived roster with search filter
const filteredRoster = computed(() => {
  const q = (manage.value.search || '').toLowerCase().trim()
  if (!q) return manage.value.roster
  return manage.value.roster.filter(r =>
    (r.name || '').toLowerCase().includes(q) || (r.student_id || '').toLowerCase().includes(q)
  )
})


// Workflow state
const workflowDate = ref(new Date().toISOString().split('T')[0])
const searchQuery = ref('')
const activeFilter = ref('all')

// Computed data for charts and workflow
const dailyAbsencesData = computed(() => {
  // Consider only classes that actually have at least one attendance session
  // Prefer backend-provided series for accuracy
  const series = props.daily_absences?.data || []
  return series
})

const classCategories = computed(() => {
  // Use categories from backend daily_absences so they match the data series keys
  const categories = props.daily_absences?.categories || []
  return categories
})

const chartColors = computed(() => {
  // Theme-aware colors: use CSS variables that adapt to light/dark
  const colors = [
    'hsl(var(--destructive))',   // red
    'hsl(var(--chart-1))',       // chart palette 1
    'hsl(var(--chart-2))',
    'hsl(var(--chart-3))',
    'hsl(var(--chart-5))',
  ]
  return colors.slice(0, classCategories.value.length)
})

const todayStats = computed(() => {
  const total = props.classes?.length || 0
  const completed = (props.classes || []).filter(c => c.last_session?.date === workflowDate.value).length
  const pending = total - completed
  return { total, completed, pending }
})

const classesNeedingAttention = computed(() => {
  return (props.classes || [])
    .filter(classData => classData.needs_attention)
    .slice(0, 5) // Show top 5 classes needing attention
})

const filteredClasses = computed(() => {
  let filtered = props.classes || []

  // Apply search filter
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(classData =>
      classData.class.subject_code?.toLowerCase().includes(query) ||
      classData.class.section?.toLowerCase().includes(query) ||
      classData.class.Subject?.title?.toLowerCase().includes(query) ||
      classData.class.ShsSubject?.title?.toLowerCase().includes(query)
    )
  }

  // Apply tab filter
  switch (activeFilter.value) {
    case 'pending':
      filtered = filtered.filter(c => !c.last_session || c.last_session.date !== workflowDate.value)
      break
    case 'completed':
      filtered = filtered.filter(c => c.last_session?.date === workflowDate.value)
      break
    case 'attention':
      filtered = filtered.filter(c => c.needs_attention)
      break
  }

  return filtered
})

// Quick Manage state
const manage = ref({
  open: false,
  class: null,
  date: new Date().toISOString().split('T')[0],
  roster: [],
  settings: {
    is_enabled: true,
    attendance_method: 'manual',
    attendance_policy: 'standard',
    allow_late_checkin: false,
    grace_period_minutes: 0,
    auto_mark_absent_minutes: 0,
    notify_absent_students: false,
  }
  ,
  methodData: null,
  search: '',
  loading: false,
  loadingSave: false,
  loadingSettings: false,

})

function openQuickManage(cls) {
  manage.value.class = cls
  manage.value.open = true
  manage.value.date = workflowDate.value
  loadRoster(cls.id)
}

function loadRoster(classId) {
  manage.value.loading = true
  const params = new URLSearchParams()
  if (manage.value.date) params.set('date', manage.value.date)

  console.log('Loading roster for class:', classId, 'date:', manage.value.date)

  fetch(`/faculty/classes/${classId}/attendance/data?` + params.toString(), {
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
    .then(r => {
      console.log('Response status:', r.status)
      if (!r.ok) {
        throw new Error(`HTTP ${r.status}: ${r.statusText}`)
      }
      return r.json()
    })
    .then(json => {
      console.log('Response data:', json)
      if (json.success && json.data) {
        // Map roster to simplified rows for UI
        const roster = (json.data.roster || []).map(item => ({
          student_id: item.student_id,
          name: item.student?.name ?? '',
          status: item.attendance?.status ?? 'present',
          remarks: item.attendance?.remarks ?? ''
        }))
        manage.value.roster = roster
        // Load settings
        if (json.data.settings) {
          manage.value.settings = {
            is_enabled: !!json.data.settings.is_enabled,
            attendance_method: json.data.settings.attendance_method ?? 'manual',
            attendance_policy: json.data.settings.attendance_policy ?? 'standard',
            allow_late_checkin: !!json.data.settings.allow_late_checkin,
            grace_period_minutes: json.data.settings.grace_period_minutes ?? 0,
            auto_mark_absent_minutes: json.data.settings.auto_mark_absent_minutes ?? 0,
            notify_absent_students: !!json.data.settings.notify_absent_students,
          }
        }
        // Load method widgets (qr/code)
        manage.value.methodData = json.data.method ?? null
        toast.success('Attendance data loaded')
      } else {
        manage.value.roster = []
        toast.error(json.message || 'Failed to load data')
      }
    })
    .catch(err => {
      console.error('Load roster error:', err)
      toast.error(`Failed to load data: ${err.message}`)
      manage.value.roster = []
    })
    .finally(() => { manage.value.loading = false })
}

function markAll(status) {
  manage.value.roster = manage.value.roster.map(r => ({ ...r, status }))
}

function saveAttendance() {
  const classId = manage.value.class?.id
  if (!classId) return
  const payload = {
    attendance_data: manage.value.roster.map(r => ({
      student_id: r.student_id,
      status: r.status,
      remarks: r.remarks || null
    })),
    date: manage.value.date
  }
  manage.value.loadingSave = true
  fetch(`/faculty/classes/${classId}/attendance/bulk-update`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify(payload)
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        toast.success(res.message || 'Attendance saved')
        loadRoster(classId)
      } else {
        toast.error(res.message || 'Failed to save attendance')
      }
    })
    .catch(() => toast.error('Failed to save attendance'))
    .finally(() => { manage.value.loadingSave = false })
}

function saveSettings() {
  const classId = manage.value.class?.id
  if (!classId) return
  const payload = {
    is_enabled: manage.value.settings.is_enabled,
    attendance_method: manage.value.settings.attendance_method,
    attendance_policy: manage.value.settings.attendance_policy,
    allow_late_checkin: manage.value.settings.allow_late_checkin,
    grace_period_minutes: manage.value.settings.grace_period_minutes,
    auto_mark_absent_minutes: manage.value.settings.auto_mark_absent_minutes,
    notify_absent_students: manage.value.settings.notify_absent_students,
    start_date: manage.value.date ?? null,
    end_date: manage.value.date ?? null,
  }
  manage.value.loadingSettings = true
  fetch(`/faculty/classes/${classId}/attendance/setup`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify(payload)
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        toast.success(res.message || 'Settings saved')
      } else {
        toast.error(res.message || 'Failed to save settings')
      }
    })
    .catch(() => toast.error('Failed to save settings'))
    .finally(() => { manage.value.loadingSettings = false })
}

// Export helpers
function exportCsv() {
  const headers = ['Student ID','Name','Status','Remarks']
  const rows = (filteredRoster.value || []).map(r => [r.student_id, r.name, r.status, r.remarks || ''])
  const csv = [headers, ...rows].map(r => r.map(c => '"' + String(c ?? '').replace(/"/g,'""') + '"').join(',')).join('\n')
  const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  const filename = `${manage.value.class?.subject_code || 'class'}_${manage.value.date || ''}_attendance.csv`
  a.href = url; a.download = filename; document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url)
}

function exportPdf() {
  const title = `${manage.value.class?.subject_code || 'Class'} (${manage.value.class?.section || ''}) — ${manage.value.date}`
  const win = window.open('', '_blank')
  if (!win) return
  const style = `
    <style> body{font-family: ui-sans-serif,system-ui,Segoe UI,Roboto,Arial; padding:24px;} h1{font-size:18px;margin:0 0 8px;} p{margin:0 0 16px;color:#666;} table{width:100%;border-collapse:collapse;font-size:12px;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f7f7f7;} </style>
  `
  const rows = (filteredRoster.value || []).map(r => `
    <tr>
      <td>${r.student_id}</td>
      <td>${r.name}</td>
      <td>${r.status}</td>
      <td>${r.remarks || ''}</td>
    </tr>`).join('')
  const html = `
    <html><head><title>${title}</title>${style}</head><body>
    <h1>Attendance</h1><p>${title}</p>
    <table><thead><tr><th>Student ID</th><th>Name</th><th>Status</th><th>Remarks</th></tr></thead>
    <tbody>${rows}</tbody></table>
    </body></html>`
  win.document.open(); win.document.write(html); win.document.close(); win.focus(); win.print()
}

// Keyboard shortcuts for quick status cycling
function handleKey(e) {
  if (!manage.value.open) return
  const keys = { p: 'present', a: 'absent', l: 'late', e: 'excused' }
  const k = e.key?.toLowerCase()
  if (!keys[k]) return
  // apply to selected row or to all filtered
  manage.value.roster = (filteredRoster.value || []).map(r => ({ ...r, status: keys[k] }))
  toast.info(`Marked ${keys[k]} for filtered students`)
}

// New workflow methods
function refreshData() {
  router.reload({ only: ['classes', 'summary'] })
}

function openBulkAttendance() {
  // Open bulk attendance modal or navigate to bulk page
  toast.info('Bulk attendance feature coming soon')
}

function exportTodayData() {
  const todayClasses = filteredClasses.value.filter(c => c.last_session?.date === workflowDate.value)
  if (todayClasses.length === 0) {
    toast.error('No attendance data for today')
    return
  }

  const csvData = todayClasses.map(c => [
    c.class.subject_code,
    c.class.section,
    c.enrollment_count,
    c.attendance_stats.attendance_rate + '%',
    c.last_session?.date || 'Never'
  ])

  const headers = ['Subject Code', 'Section', 'Students', 'Attendance Rate', 'Last Session']
  const csv = [headers, ...csvData].map(row => row.map(cell => `"${cell}"`).join(',')).join('\n')

  const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `attendance_summary_${workflowDate.value}.csv`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)

  toast.success('Data exported successfully')
}

function openSettings() {
  toast.info('Global attendance settings coming soon')
}

function getStatusIndicatorClass(classData) {
  if (classData.last_session?.date === workflowDate.value) {
    return 'bg-green-500' // Completed today
  } else if (classData.needs_attention) {
    return 'bg-red-500' // Needs attention
  } else {
    return 'bg-yellow-500' // Pending
  }
}

function getActionButtonVariant(classData) {
  if (classData.last_session?.date === workflowDate.value) {
    return 'outline' // Already completed
  } else {
    return 'default' // Primary action
  }
}

function getActionButtonIcon(classData) {
  if (classData.last_session?.date === workflowDate.value) {
    return 'heroicons:pencil-square' // Edit/Update
  } else {
    return 'heroicons:play' // Start/Take attendance
  }
}

function getActionButtonText(classData) {
  if (classData.last_session?.date === workflowDate.value) {
    return 'Update Attendance'
  } else {
    return 'Take Attendance'
  }
}

function viewClassReports(classId) {
  router.visit(`/faculty/classes/${classId}/attendance/reports`)
}

function exportClassData(classId) {
  toast.info('Class-specific export coming soon')
}

onMounted(() => window.addEventListener('keydown', handleKey))
onBeforeUnmount(() => window.removeEventListener('keydown', handleKey))


const getAttendanceRateColor = (rate) => {
  if (rate >= 90) return 'text-green-600'
  if (rate >= 75) return 'text-yellow-600'
  return 'text-red-600'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const goToClassAttendance = (classId) => {
  router.visit(`/faculty/attendance/class/${classId}`)
}
</script>
