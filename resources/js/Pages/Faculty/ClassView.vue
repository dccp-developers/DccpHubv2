<template>
  <FacultyLayout>
    <div class="space-y-6">
      <!-- Breadcrumb -->
      <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
          <li>
            <div>
              <button @click="goBack" class="text-muted-foreground hover:text-foreground">
                Dashboard
              </button>
            </div>
          </li>
          <li>
            <div class="flex items-center">
              <svg class="flex-shrink-0 h-5 w-5 text-muted-foreground" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
              <span class="ml-4 text-sm font-medium text-foreground">{{ classData.subject_code }}</span>
            </div>
          </li>
        </ol>
      </nav>

      <!-- Error Message -->
      <div v-if="props.error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
        <div class="flex items-center space-x-2">
          <svg class="w-5 h-5 text-destructive" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <p class="text-destructive font-medium">{{ props.error }}</p>
        </div>
      </div>

      <!-- Class Header -->
      <div class="bg-card rounded-lg border border-border p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-start justify-between space-y-4 md:space-y-0">
          <div class="flex-1">
            <div class="flex flex-wrap items-center gap-2 mb-2">
              <h1 class="text-xl md:text-2xl font-bold text-foreground">{{ classData.subject_code || 'Unknown Class' }}</h1>
              <Badge variant="secondary" class="text-sm">
                Sec {{ classData.section || 'N/A' }}
              </Badge>
              <Badge
                :variant="classData.classification === 'college' ? 'default' : 'outline'"
                class="text-sm"
              >
                {{ classData.classification === 'college' ? 'College' : 'SHS' }}
              </Badge>
            </div>
            <h2 class="text-base md:text-lg text-muted-foreground mb-4">{{ classData.subject_title || 'No title available' }}</h2>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
              <div class="flex items-center space-x-2">
                <MapPinIcon class="w-4 h-4 text-muted-foreground" />
                <span>{{ classData.room || 'TBA' }}</span>
              </div>
              <div class="flex items-center space-x-2">
                <UsersIcon class="w-4 h-4 text-muted-foreground" />
                <span>{{ classData.student_count || 0 }} students</span>
              </div>
              <div class="flex items-center space-x-2">
                <ClockIcon class="w-4 h-4 text-muted-foreground" />
                <span>{{ classData.units || 3 }} units</span>
              </div>
              <div class="flex items-center space-x-2">
                <CalendarIcon class="w-4 h-4 text-muted-foreground" />
                <span>{{ currentSemester }} {{ schoolYear }}</span>
              </div>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <Button variant="outline" size="sm" @click="goBack" class="w-full sm:w-auto">
              <ArrowLeftIcon class="w-4 h-4 mr-2" />
              <span class="hidden sm:inline">Back to Dashboard</span>
              <span class="sm:hidden">Back</span>
            </Button>
          </div>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center space-x-2">
              <div class="p-2 bg-blue-100 rounded-lg">
                <UsersIcon class="w-5 h-5 text-blue-600" />
              </div>
              <div>
                <p class="text-2xl font-bold text-foreground">{{ stats.total_students }}</p>
                <p class="text-sm text-muted-foreground">Total Students</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center space-x-2">
              <div class="p-2 bg-green-100 rounded-lg">
                <CheckCircleIcon class="w-5 h-5 text-green-600" />
              </div>
              <div>
                <p class="text-2xl font-bold text-foreground">{{ stats.attendance_rate }}%</p>
                <p class="text-sm text-muted-foreground">Attendance Rate</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center space-x-2">
              <div class="p-2 bg-yellow-100 rounded-lg">
                <AcademicCapIcon class="w-5 h-5 text-yellow-600" />
              </div>
              <div>
                <p class="text-2xl font-bold text-foreground">{{ stats.average_grade }}</p>
                <p class="text-sm text-muted-foreground">Average Grade</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center space-x-2">
              <div class="p-2 bg-purple-100 rounded-lg">
                <ChartBarIcon class="w-5 h-5 text-purple-600" />
              </div>
              <div>
                <p class="text-2xl font-bold text-foreground">{{ stats.passing_rate }}%</p>
                <p class="text-sm text-muted-foreground">Passing Rate</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Main Content Tabs -->
      <div class="bg-card rounded-lg border border-border">
        <div class="border-b border-border">
          <nav class="flex space-x-8 px-6" aria-label="Tabs">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                activeTab === tab.id
                  ? 'border-primary text-primary'
                  : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border',
                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
              ]"
            >
              <component :is="tab.icon" class="w-4 h-4 mr-2 inline" />
              {{ tab.name }}
            </button>
          </nav>
        </div>

        <div class="p-6">
          <!-- Students Tab -->
          <div v-if="activeTab === 'students'" class="space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-foreground">Enrolled Students</h3>
              <div class="flex items-center space-x-2">
                <Button variant="outline" size="sm">
                  <UserPlusIcon class="w-4 h-4 mr-2" />
                  Add Student
                </Button>
                <Button variant="outline" size="sm">
                  <DocumentArrowDownIcon class="w-4 h-4 mr-2" />
                  Export List
                </Button>
              </div>
            </div>

            <div class="border border-border rounded-lg overflow-hidden">
              <table class="min-w-full divide-y divide-border">
                <thead class="bg-muted/50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                      Student
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                      Student Number
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                      Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                      Grade
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-card divide-y divide-border">
                  <tr v-if="!classData.students || classData.students.length === 0">
                    <td colspan="5" class="px-6 py-8 text-center text-muted-foreground">
                      No students enrolled in this class yet.
                    </td>
                  </tr>
                  <tr v-for="student in classData.students" :key="student.id" class="hover:bg-muted/50">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="flex-shrink-0 h-8 w-8">
                          <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center">
                            <span class="text-sm font-medium text-primary">
                              {{ student.name.charAt(0) }}
                            </span>
                          </div>
                        </div>
                        <div class="ml-4">
                          <div class="text-sm font-medium text-foreground">{{ student.name }}</div>
                          <div class="text-sm text-muted-foreground">{{ student.email }}</div>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                      {{ student.student_number }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <Badge :variant="student.status === 'active' ? 'default' : 'secondary'" class="text-xs">
                        {{ student.status }}
                      </Badge>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                      {{ student.grade || 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <Button variant="ghost" size="sm">
                        <EyeIcon class="w-4 h-4" />
                      </Button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Schedule Tab -->
          <div v-if="activeTab === 'schedule'" class="space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-foreground">Class Schedule</h3>
              <Button variant="outline" size="sm">
                <PlusIcon class="w-4 h-4 mr-2" />
                Add Schedule
              </Button>
            </div>

            <div class="grid gap-4">
              <Card v-if="!schedules || schedules.length === 0">
                <CardContent class="p-8 text-center">
                  <CalendarIcon class="w-12 h-12 text-muted-foreground mx-auto mb-4" />
                  <p class="text-muted-foreground">No schedule set for this class yet.</p>
                </CardContent>
              </Card>

              <Card v-for="schedule in schedules" :key="schedule.id">
                <CardContent class="p-4">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                      <div class="p-2 bg-blue-100 rounded-lg">
                        <CalendarIcon class="w-5 h-5 text-blue-600" />
                      </div>
                      <div>
                        <p class="font-medium text-foreground">{{ schedule.day_of_week }}</p>
                        <p class="text-sm text-muted-foreground">
                          {{ schedule.start_time }} - {{ schedule.end_time }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                          {{ schedule.room }} • {{ schedule.duration }}
                        </p>
                      </div>
                    </div>
                    <div class="flex items-center space-x-2">
                      <Button variant="ghost" size="sm">
                        <PencilIcon class="w-4 h-4" />
                      </Button>
                      <Button variant="ghost" size="sm">
                        <TrashIcon class="w-4 h-4" />
                      </Button>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </div>

          <!-- Grades Tab -->
          <div v-if="activeTab === 'grades'" class="space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-foreground">Grade Management</h3>
              <div class="flex items-center space-x-2">
                <Button variant="outline" size="sm" @click="showImportDialog = true">
                  <DocumentArrowUpIcon class="w-4 h-4 mr-2" />
                  Import Grades
                </Button>
                <Button variant="outline" size="sm" @click="exportGrades">
                  <DocumentArrowDownIcon class="w-4 h-4 mr-2" />
                  Export Grades
                </Button>
              </div>
            </div>

            <!-- Import Info Dialog -->
            <Dialog :open="showImportDialog" @update:open="showImportDialog = $event">
              <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                  <DialogTitle>Import Grades (CSV)</DialogTitle>
                  <DialogDescription>
                    Expected columns: student_id, prelim, midterm, finals. Values must be 0-100. You can also use prelim_grade, midterm_grade, finals_grade as column names.
                  </DialogDescription>
                </DialogHeader>
                <div class="space-y-3">
                  <input type="file" @change="handleFileChange" accept=".csv,text/csv" />
                </div>
                <DialogFooter>
                  <Button variant="outline" @click="showImportDialog = false">Cancel</Button>
                  <Button :disabled="!importFile" @click="submitImport">Upload</Button>
                </DialogFooter>
              </DialogContent>
            </Dialog>

            <!-- Grade Distribution Chart -->
            <Card>
              <CardContent class="p-6">
                <h4 class="text-lg font-semibold text-foreground mb-4">Grade Distribution</h4>
                <div class="grid grid-cols-4 gap-4">
                  <div v-for="(count, range) in stats.grade_distribution" :key="range" class="text-center">
                    <div class="text-2xl font-bold text-foreground">{{ count }}</div>
                    <div class="text-sm text-muted-foreground">Range {{ range }}</div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Grades Table -->
            <div class="border border-border rounded-lg overflow-hidden">
              <table class="min-w-full divide-y divide-border">
                <thead class="bg-muted/50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                      Student
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                      Prelim (%)
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                      Midterm (%)
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                      Finals (%)
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                      Total Average (%)
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-card divide-y divide-border">
                  <tr v-if="!classData.students || classData.students.length === 0">
                    <td colspan="5" class="px-6 py-8 text-center text-muted-foreground">
                      No students enrolled in this class yet.
                    </td>
                  </tr>
                  <tr v-for="student in classData.students" :key="student.id" class="hover:bg-muted/50">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-foreground">{{ student.name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                      <input type="number" min="0" max="100" step="0.01" class="w-24 border border-border rounded px-2 py-1 bg-background"
                        :value="gradeEdits[student.student_id]?.prelim_grade ?? ''"
                        @input="updateGradeEdit(student.student_id, 'prelim_grade', $event.target.value)"
                        @change="saveSingleGrade(student.student_id)"
                        :placeholder="student.prelim_grade ?? '—'" />
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                      <input type="number" min="0" max="100" step="0.01" class="w-24 border border-border rounded px-2 py-1 bg-background"
                        :value="gradeEdits[student.student_id]?.midterm_grade ?? ''"
                        @input="updateGradeEdit(student.student_id, 'midterm_grade', $event.target.value)"
                        @change="saveSingleGrade(student.student_id)"
                        :placeholder="student.midterm_grade ?? '—'" />
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                      <input type="number" min="0" max="100" step="0.01" class="w-24 border border-border rounded px-2 py-1 bg-background"
                        :value="gradeEdits[student.student_id]?.finals_grade ?? ''"
                        @input="updateGradeEdit(student.student_id, 'finals_grade', $event.target.value)"
                        @change="saveSingleGrade(student.student_id)"
                        :placeholder="student.finals_grade ?? '—'" />
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                      {{ computeAverage(gradeEdits[student.student_id], student) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Attendance Tab -->
          <div v-if="activeTab === 'attendance'" class="space-y-6">
            <!-- Attendance Setup Section -->
            <div v-if="!attendance.is_setup" class="space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-foreground">Setup Attendance Tracking</h3>
              </div>

              <Card>
                <CardContent class="p-6">
                  <div class="text-center mb-6">
                    <ClipboardDocumentListIcon class="w-12 h-12 text-muted-foreground mx-auto mb-4" />
                    <h4 class="text-lg font-semibold mb-2">Configure Attendance for This Class</h4>
                    <p class="text-muted-foreground">Set up attendance tracking to start recording student attendance for each class session.</p>
                  </div>

                  <AttendanceSetupForm
                    :class-data="classData"
                    :methods="attendance.methods"
                    :policies="attendance.policies"
                    @setup-complete="handleAttendanceSetup"
                  />
                </CardContent>
              </Card>
            </div>

            <!-- Attendance Management Section -->
            <div v-else class="space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-foreground">Attendance Management</h3>
                <div class="flex items-center space-x-2">
                  <Button
                    size="sm"
                    @click="initializeAttendance"
                    :disabled="attendanceLoading"
                  >
                    <ClipboardDocumentListIcon class="w-4 h-4 mr-2" />
                    {{ attendanceData?.session_stats ? 'Manage Attendance' : 'Start Attendance' }}
                  </Button>
                  <Button variant="outline" size="sm" @click="exportAttendance">
                    <DocumentArrowDownIcon class="w-4 h-4 mr-2" />
                    Export Report
                  </Button>
                  <Button variant="outline" size="sm" @click="showAttendanceSettings = true">
                    <CogIcon class="w-4 h-4 mr-2" />
                    Settings
                  </Button>
                  <Button variant="destructive" size="sm" @click="showResetConfirmation = true">
                    <Icon icon="heroicons:arrow-path" class="w-4 h-4 mr-2" />
                    Reset Attendance
                  </Button>
                </div>
              </div>

              <!-- Attendance Date Selector -->
              <Card>
                <CardContent class="p-4">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                      <div>
                        <label class="text-sm font-medium text-foreground">Date:</label>
                        <input
                          type="date"
                          v-model="selectedDate"
                          @change="loadAttendanceData"
                          class="ml-2 px-3 py-1 border border-border rounded-md text-sm"
                        />
                      </div>
                      <div v-if="attendanceData?.session_stats" class="text-sm text-muted-foreground">
                        {{ attendanceData.session_stats.present + attendanceData.session_stats.late }}/{{ attendanceData.session_stats.total }} Present
                        ({{ attendanceData.session_stats.attendance_rate }}%)
                      </div>
                    </div>
                    <div v-if="attendanceData?.settings?.method" class="text-sm text-muted-foreground">
                      Method: {{ attendanceData.settings.method.label }}
                    </div>
                  </div>
                </CardContent>
              </Card>

              <!-- Method-specific Display -->
              <AttendanceMethodDisplay
                v-if="attendanceData?.session_data"
                :session-data="attendanceData.session_data"
                :method-settings="attendanceData.settings"
                @refresh-method="refreshAttendanceMethod"
              />

              <!-- Simple Attendance Table -->
              <div v-if="attendanceData?.roster?.length" class="space-y-4">
                <!-- Quick Stats -->
                <div class="grid grid-cols-4 gap-4">
                  <Card>
                    <CardContent class="p-4 text-center">
                      <div class="text-2xl font-bold">{{ attendanceData.session_stats?.total || 0 }}</div>
                      <div class="text-sm text-muted-foreground">Total</div>
                    </CardContent>
                  </Card>
                  <Card>
                    <CardContent class="p-4 text-center">
                      <div class="text-2xl font-bold text-green-600">{{ attendanceData.session_stats?.present || 0 }}</div>
                      <div class="text-sm text-muted-foreground">Present</div>
                    </CardContent>
                  </Card>
                  <Card>
                    <CardContent class="p-4 text-center">
                      <div class="text-2xl font-bold text-red-600">{{ attendanceData.session_stats?.absent || 0 }}</div>
                      <div class="text-sm text-muted-foreground">Absent</div>
                    </CardContent>
                  </Card>
                  <Card>
                    <CardContent class="p-4 text-center">
                      <div class="text-2xl font-bold text-blue-600">{{ attendanceData.session_stats?.attendance_rate || 0 }}%</div>
                      <div class="text-sm text-muted-foreground">Rate</div>
                    </CardContent>
                  </Card>
                </div>

                <!-- Quick Actions -->
                <Card>
                  <CardContent class="p-4">
                    <div class="flex items-center justify-between">
                      <div>
                        <h3 class="font-medium">Quick Actions</h3>
                        <p class="text-sm text-muted-foreground">Mark all students at once</p>
                      </div>
                      <div class="flex gap-2">
                        <Button @click="markAllPresent" variant="default" size="sm">
                          <CheckIcon class="w-4 h-4 mr-2" />
                          All Present
                        </Button>
                        <Button @click="markAllAbsent" variant="outline" size="sm">
                          <XMarkIcon class="w-4 h-4 mr-2" />
                          All Absent
                        </Button>
                      </div>
                    </div>
                  </CardContent>
                </Card>

                <!-- Simple Attendance Table -->
                <Card>
                  <CardContent class="p-0">
                    <div class="overflow-x-auto">
                      <table class="w-full">
                        <thead>
                          <tr class="border-b">
                            <th class="text-left p-4 font-medium">Student</th>
                            <th class="text-center p-4 font-medium">Status</th>
                            <th class="text-center p-4 font-medium">Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr
                            v-for="student in attendanceData.roster"
                            :key="student.student_id"
                            class="border-b hover:bg-muted/25"
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
                                    class="w-10 h-10 rounded-full bg-muted flex items-center justify-center"
                                  >
                                    <UserIcon class="w-5 h-5 text-muted-foreground" />
                                  </div>
                                </div>
                                <div>
                                  <div class="font-medium">{{ student.student.name }}</div>
                                  <div class="text-sm text-muted-foreground">{{ student.student_id }}</div>
                                </div>
                              </div>
                            </td>

                            <!-- Current Status -->
                            <td class="p-4 text-center">
                              <Badge
                                :variant="getStatusVariant(student.attendance.status)"
                                class="capitalize"
                              >
                                {{ student.attendance.status }}
                              </Badge>
                            </td>

                            <!-- Action Buttons -->
                            <td class="p-4">
                              <div class="flex justify-center space-x-2">
                                <Button
                                  @click="updateStudentStatus(student.student_id, 'present')"
                                  :variant="student.attendance.status === 'present' ? 'default' : 'outline'"
                                  size="sm"
                                  class="min-w-[80px]"
                                >
                                  <CheckIcon class="w-4 h-4 mr-1" />
                                  Present
                                </Button>
                                <Button
                                  @click="updateStudentStatus(student.student_id, 'absent')"
                                  :variant="student.attendance.status === 'absent' ? 'destructive' : 'outline'"
                                  size="sm"
                                  class="min-w-[80px]"
                                >
                                  <XMarkIcon class="w-4 h-4 mr-1" />
                                  Absent
                                </Button>
                                <Button
                                  @click="updateStudentStatus(student.student_id, 'late')"
                                  :variant="student.attendance.status === 'late' ? 'secondary' : 'outline'"
                                  size="sm"
                                  class="min-w-[70px]"
                                >
                                  Late
                                </Button>
                                <Button
                                  @click="updateStudentStatus(student.student_id, 'excused')"
                                  :variant="student.attendance.status === 'excused' ? 'secondary' : 'outline'"
                                  size="sm"
                                  class="min-w-[80px]"
                                >
                                  Excused
                                </Button>
                              </div>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </CardContent>
                </Card>

                <!-- Save Button -->
                <div class="flex justify-center">
                  <Button @click="saveAllAttendance" size="lg" class="px-8">
                    <CloudArrowUpIcon class="w-5 h-5 mr-2" />
                    Save Attendance
                  </Button>
                </div>
              </div>

              <!-- No Data State -->
              <Card v-else>
                <CardContent class="p-8 text-center">
                  <ClipboardDocumentListIcon class="w-12 h-12 text-muted-foreground mx-auto mb-4" />
                  <p class="text-muted-foreground mb-4">No attendance data for the selected date.</p>
                  <Button @click="initializeAttendance" :disabled="attendanceLoading">
                    Initialize Attendance Session
                  </Button>
                </CardContent>
              </Card>
            </div>

            <!-- Attendance Settings Modal -->
            <AttendanceSettingsModal
              v-if="showAttendanceSettings"
              :class-data="classData"
              :current-settings="attendance.settings"
              :methods="attendance.methods"
              :policies="attendance.policies"
              @close="showAttendanceSettings = false"
              @settings-updated="handleSettingsUpdate"
            />

            <!-- Reset Attendance Confirmation Dialog -->
            <Dialog :open="showResetConfirmation" @update:open="showResetConfirmation = $event">
              <DialogContent class="sm:max-w-md">
                <DialogHeader>
                  <DialogTitle class="flex items-center gap-2">
                    <Icon icon="heroicons:exclamation-triangle" class="w-5 h-5 text-destructive" />
                    Reset Attendance Data
                  </DialogTitle>
                  <DialogDescription>
                    This action will permanently delete all attendance records for this class. This cannot be undone.
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
                  <Button variant="outline" @click="showResetConfirmation = false">
                    Cancel
                  </Button>
                  <Button
                    variant="destructive"
                    @click="resetAttendanceData"
                    :disabled="resetConfirmationText !== 'RESET' || isResetting"
                  >
                    <Icon v-if="isResetting" icon="heroicons:arrow-path" class="w-4 h-4 mr-2 animate-spin" />
                    <Icon v-else icon="heroicons:trash" class="w-4 h-4 mr-2" />
                    {{ isResetting ? 'Resetting...' : 'Reset Attendance' }}
                  </Button>
                </DialogFooter>
              </DialogContent>
            </Dialog>
          </div>
        </div>
      </div>
    </div>
  </FacultyLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import FacultyLayout from '@/Layouts/FacultyLayout.vue'
import { Card, CardContent } from '@/Components/ui/card.js'
import { Button } from '@/Components/ui/button.js'
import { Badge } from '@/Components/ui/badge.js'
import { Input } from '@/Components/shadcn/ui/input'
import { Label } from '@/Components/shadcn/ui/label'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/shadcn/ui/dialog'
import { Icon } from '@iconify/vue'
import AttendanceSetupForm from '@/Components/Faculty/AttendanceSetupForm.vue'
import AttendanceSettingsModal from '@/Components/Faculty/AttendanceSettingsModal.vue'
import AttendanceMethodDisplay from '@/Components/Faculty/AttendanceMethodDisplay.vue'
import {
  MapPinIcon,
  UsersIcon,
  ClockIcon,
  CalendarIcon,
  ArrowLeftIcon,
  CogIcon,
  CheckCircleIcon,
  AcademicCapIcon,
  ChartBarIcon,
  UserPlusIcon,
  DocumentArrowDownIcon,
  DocumentArrowUpIcon,
  EyeIcon,
  PlusIcon,
  PencilIcon,
  TrashIcon,
  ClipboardDocumentListIcon,
  CheckIcon,
  XMarkIcon,
  UserIcon,
  CloudArrowUpIcon
} from '@heroicons/vue/24/outline'
import axios from 'axios'
import { toast } from 'vue-sonner'
import { route } from 'ziggy-js'

// Props from the controller
const props = defineProps({
  classData: Object,
  schedules: Array,
  stats: Object,
  performance: Object,
  attendance: Object,
  faculty: Object,
  currentSemester: String,
  schoolYear: String,
  availableSemesters: Object,
  availableSchoolYears: Object,
  error: String
})

// Reactive data
const activeTab = ref('students')
const selectedDate = ref(new Date().toISOString().split('T')[0])
const attendanceData = ref(props.attendance?.data || null)
const attendanceLoading = ref(false)
const showAttendanceSettings = ref(false)
const showResetConfirmation = ref(false)
const resetConfirmationText = ref('')
const isResetting = ref(false)

// Computed properties
const classData = computed(() => props.classData || {})
const schedules = computed(() => props.schedules || [])
const stats = computed(() => props.stats || {})

// Grades editing state
const showImportDialog = ref(false)
const importFile = ref(null)
const gradeEdits = ref({})

// Initialize gradeEdits with existing values using computed
const initializeGradeEdits = () => {
  if (props.classData?.students) {
    props.classData.students.forEach(s => {
      if (!gradeEdits.value[s.student_id]) {
        gradeEdits.value[s.student_id] = {
          prelim_grade: s.prelim_grade ?? null,
          midterm_grade: s.midterm_grade ?? null,
          finals_grade: s.finals_grade ?? null,
        }
      }
    })
  }
}

// Initialize on mount and when data changes
initializeGradeEdits()

// Watch for changes in classData and reinitialize
watch(() => props.classData?.students, () => {
  initializeGradeEdits()
}, { deep: true })

// Tab configuration
const tabs = [
  { id: 'students', name: 'Students', icon: UsersIcon },
  { id: 'schedule', name: 'Schedule', icon: CalendarIcon },
  { id: 'grades', name: 'Grades', icon: AcademicCapIcon },
  { id: 'attendance', name: 'Attendance', icon: ClipboardDocumentListIcon }
]

// Methods
const goBack = () => {
  router.visit(route('faculty.dashboard'))
}

// Attendance Management Methods
const handleAttendanceSetup = async (settings) => {
  try {
    const response = await axios.post(route('faculty.classes.attendance.setup', props.classData.id), settings)

    if (response.data.success) {
      // Update attendance data
      props.attendance.is_setup = true
      props.attendance.settings = response.data.settings

      // Show success message
      toast.success('Attendance tracking has been set up successfully!')

      // Initialize first session
      await initializeAttendance()
    }
  } catch (error) {
    console.error('Failed to setup attendance:', error)
    toast.error('Failed to setup attendance tracking. Please try again.')
  }
}

const loadAttendanceData = async () => {
  if (!props.attendance?.is_setup) return

  attendanceLoading.value = true

  try {
    const response = await axios.get(route('faculty.classes.attendance.data', props.classData.id), {
      params: { date: selectedDate.value }
    })

    if (response.data.success) {
      attendanceData.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to load attendance data:', error)
    toast.error('Failed to load attendance data.')
  } finally {
    attendanceLoading.value = false
  }
}

const initializeAttendance = async () => {
  attendanceLoading.value = true

  try {
    const response = await axios.post(route('faculty.classes.attendance.initialize', props.classData.id), {
      date: selectedDate.value
    })

    if (response.data.success) {
      attendanceData.value = response.data.data
      toast.success('Attendance session initialized successfully!')
    }
  } catch (error) {
    console.error('Failed to initialize attendance:', error)
    toast.error(error.response?.data?.message || 'Failed to initialize attendance session.')
  } finally {
    attendanceLoading.value = false
  }
}

const updateStudentAttendance = async (studentId, status, remarks = null) => {
  try {
    const response = await axios.post(route('faculty.classes.attendance.update', props.classData.id), {
      student_id: studentId,
      status: status,
      date: selectedDate.value,
      remarks: remarks
    })

    if (response.data.success) {
      // Update local data
      const studentIndex = attendanceData.value.roster.findIndex(s => s.student_id === studentId)
      if (studentIndex !== -1) {
        attendanceData.value.roster[studentIndex].attendance = response.data.data.attendance
        attendanceData.value.session_stats = response.data.data.session_stats
      }

      toast.success('Attendance updated successfully!')
    }
  } catch (error) {
    console.error('Failed to update attendance:', error)
    toast.error('Failed to update attendance.')
  }
}

const bulkUpdateAttendance = async (attendanceUpdates) => {
  try {
    const response = await axios.post(route('faculty.classes.attendance.bulk-update', props.classData.id), {
      attendance_data: attendanceUpdates,
      date: selectedDate.value
    })

    if (response.data.success) {
      // Reload attendance data
      await loadAttendanceData()
      toast.success(response.data.message)
    }
  } catch (error) {
    console.error('Failed to bulk update attendance:', error)
    toast.error('Failed to update attendance.')
  }
}

const exportAttendance = () => {
  // TODO: Implement attendance export
  toast.info('Export functionality coming soon!')
}

const handleSettingsUpdate = (newSettings) => {
  props.attendance.settings = newSettings
  showAttendanceSettings.value = false
  toast.success('Attendance settings updated successfully!')
}

const refreshAttendanceMethod = async () => {
  try {
    // Re-initialize the attendance session to refresh codes/QR
    await initializeAttendance()
    toast.success('Attendance method refreshed successfully!')
  } catch (error) {
    console.error('Failed to refresh attendance method:', error)
    toast.error('Failed to refresh attendance method.')
  }
}

// New User-Friendly Attendance Methods
const updateStudentStatus = async (studentId, status) => {
  try {
    // Find the student in the roster and update locally first for immediate feedback
    const student = attendanceData.value.roster.find(s => s.student_id === studentId)
    if (student) {
      student.attendance.status = status
    }

    // Update on server
    const response = await axios.post(route('faculty.classes.attendance.update', props.classData.id), {
      student_id: studentId,
      status: status,
      date: selectedDate.value
    })

    if (response.data.success) {
      // Refresh attendance data to get updated stats
      await loadAttendanceData()
      toast.success(`Student marked as ${status}`)
    }
  } catch (error) {
    console.error('Failed to update attendance:', error)
    toast.error('Failed to update attendance')
    // Revert local change on error
    await loadAttendanceData()
  }
}

const markAllPresent = async () => {
  try {
    const updates = attendanceData.value.roster.map(student => ({
      student_id: student.student_id,
      status: 'present'
    }))

    const response = await axios.post(route('faculty.classes.attendance.bulk-update', props.classData.id), {
      attendance_data: updates,
      date: selectedDate.value
    })

    if (response.data.success) {
      await loadAttendanceData()
      toast.success('All students marked as present')
    }
  } catch (error) {
    console.error('Failed to mark all present:', error)
    toast.error('Failed to mark all students as present')
  }
}

const markAllAbsent = async () => {
  try {
    const updates = attendanceData.value.roster.map(student => ({
      student_id: student.student_id,
      status: 'absent'
    }))

    const response = await axios.post(route('faculty.classes.attendance.bulk-update', props.classData.id), {
      attendance_data: updates,
      date: selectedDate.value
    })

    if (response.data.success) {
      await loadAttendanceData()
      toast.success('All students marked as absent')
    }
  } catch (error) {
    console.error('Failed to mark all absent:', error)
    toast.error('Failed to mark all students as absent')
  }
}

const saveAllAttendance = async () => {
  try {
    const updates = attendanceData.value.roster.map(student => ({
      student_id: student.student_id,
      status: student.attendance.status,
      remarks: student.attendance.remarks || null
    }))

    const response = await axios.post(route('faculty.classes.attendance.bulk-update', props.classData.id), {
      attendance_data: updates,
      date: selectedDate.value
    })

    if (response.data.success) {
      await loadAttendanceData()
      toast.success('Attendance saved successfully!')
    }
  } catch (error) {
    console.error('Failed to save attendance:', error)
    toast.error('Failed to save attendance')
  }
}

// Helper function for badge variants
const getStatusVariant = (status) => {
  switch (status) {
    case 'present':
      return 'default'
    case 'absent':
      return 'destructive'
    case 'late':
      return 'secondary'
    case 'excused':
      return 'outline'
    default:
      return 'secondary'
  }
}

const resetAttendanceData = async () => {
  if (resetConfirmationText.value !== 'RESET') {
    toast.error('Please type "RESET" to confirm')
    return
  }

  isResetting.value = true

  try {
    const response = await axios.delete(route('faculty.classes.attendance.reset', props.classData.id))

    if (response.data.success) {
      // Close dialog first so toast is visible above overlay
      showResetConfirmation.value = false
      toast.success('Attendance data has been reset successfully')

      // Reset local state immediately (no full reload)
      attendanceData.value = null
      resetConfirmationText.value = ''

      // Update attendance setup status so UI returns to setup state
      if (props.attendance) {
        props.attendance.is_setup = false
        props.attendance.settings = null
        props.attendance.data = null
      }
    } else {
      toast.error(response.data.message || 'Failed to reset attendance data')
    }
  } catch (error) {
    console.error('Failed to reset attendance:', error)
    toast.error('Failed to reset attendance data. Please try again.')
  } finally {
    isResetting.value = false
  }
}

// Grades helpers and actions
const updateGradeEdit = (studentId, field, value) => {
  if (!gradeEdits.value[studentId]) {
    gradeEdits.value[studentId] = {}
  }
  gradeEdits.value[studentId][field] = value === '' ? null : Number(value)
}

const handleFileChange = (e) => {
  importFile.value = e.target.files?.[0] || null
}

const submitImport = async () => {
  if (!importFile.value) return
  try {
    const form = new FormData()
    form.append('file', importFile.value)
    await axios.post(route('faculty.classes.grades.import', props.classData.id), form, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    toast.success('Grades imported successfully')
    showImportDialog.value = false
    importFile.value = null
    // Refresh page data to reflect imports
    router.reload({ only: ['classData', 'stats'] })
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to import grades')
  }
}

const exportGrades = async () => {
  try {
    window.location.href = route('faculty.classes.grades.export', props.classData.id)
  } catch (err) {
    toast.error('Failed to export grades')
  }
}

const computeAverage = (editRow, student) => {
  const prelim = editRow?.prelim_grade ?? student.prelim_grade ?? null
  const midterm = editRow?.midterm_grade ?? student.midterm_grade ?? null
  const finals = editRow?.finals_grade ?? student.finals_grade ?? null
  const parts = [prelim, midterm, finals].filter(v => v !== null && v !== undefined)
  if (!parts.length) return '—'
  const avg = parts.reduce((a, b) => a + Number(b), 0) / parts.length
  return avg.toFixed(2)
}

const saveSingleGrade = async (studentId) => {
  try {
    const payload = { student_id: studentId }
    const row = gradeEdits.value[studentId] || {}

    // Only include grades that have been modified
    ;['prelim_grade', 'midterm_grade', 'finals_grade'].forEach(k => {
      if (row[k] !== undefined && row[k] !== null && row[k] !== '') {
        payload[k] = Number(row[k])
      }
    })

    // Don't send empty payload
    if (Object.keys(payload).length === 1) {
      toast.info('No changes to save')
      return
    }

    const { data } = await axios.post(route('faculty.classes.grades.update', props.classData.id), payload)
    if (data.success) {
      toast.success('Grade saved successfully')
      // Update in-memory classData student
      const s = props.classData.students.find(s => s.student_id === studentId)
      if (s) {
        s.prelim_grade = data.data.prelim_grade
        s.midterm_grade = data.data.midterm_grade
        s.finals_grade = data.data.finals_grade
        s.total_average = data.data.total_average
      }
      // Update stats card after save
      router.reload({ only: ['stats'] })
    }
  } catch (err) {
    const errorMsg = err.response?.data?.message || err.response?.data?.error || 'Failed to save grade'
    toast.error(errorMsg)
    console.error('Grade save error:', err.response?.data || err.message)
  }
}

</script>
