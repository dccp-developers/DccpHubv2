<template>
  <FacultyLayout>
    <div class="w-full max-w-full space-y-3 sm:space-y-6 overflow-x-hidden" style="width: 100%; max-width: 100vw;">
      <!-- Mobile Breadcrumb -->
      <nav class="flex items-center space-x-2 sm:space-x-4" aria-label="Breadcrumb">
        <button @click="goBack" class="text-muted-foreground hover:text-foreground text-sm">
          Dashboard
        </button>
        <svg class="flex-shrink-0 h-4 w-4 text-muted-foreground" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="text-sm font-medium text-foreground truncate">{{ classData.subject_code }}</span>
      </nav>

      <!-- Error Message -->
      <div v-if="props.error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-3 sm:p-4">
        <div class="flex items-center space-x-2">
          <svg class="w-4 h-4 sm:w-5 sm:h-5 text-destructive" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <p class="text-destructive font-medium text-sm">{{ props.error }}</p>
        </div>
      </div>

      <!-- Class Header -->
      <div class="bg-card rounded-lg border border-border p-3 sm:p-6 overflow-hidden w-full">
        <div class="flex flex-col space-y-3 sm:space-y-4 w-full">
          <div class="flex flex-col sm:flex-row sm:items-start justify-between space-y-3 sm:space-y-0 w-full">
            <div class="flex-1 min-w-0 w-full sm:w-auto">
              <div class="flex flex-wrap items-center gap-1 sm:gap-2 mb-2">
                <h1 class="text-lg sm:text-2xl font-bold text-foreground truncate max-w-full">{{ classData.subject_code || 'Unknown Class' }}</h1>
                <Badge variant="secondary" class="text-xs sm:text-sm flex-shrink-0">
                  Sec {{ classData.section || 'N/A' }}
                </Badge>
                <Badge
                  :variant="classData.classification === 'college' ? 'default' : 'outline'"
                  class="text-xs sm:text-sm flex-shrink-0"
                >
                  {{ classData.classification === 'college' ? 'College' : 'SHS' }}
                </Badge>
              </div>
              <h2 class="text-sm sm:text-lg text-muted-foreground mb-3 sm:mb-4 truncate max-w-full">{{ classData.subject_title || 'No title available' }}</h2>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-shrink-0">
              <Button variant="outline" size="sm" @click="goBack" class="w-full sm:w-auto">
                <ArrowLeftIcon class="w-4 h-4 mr-2" />
                <span class="hidden sm:inline">Back to Dashboard</span>
                <span class="sm:hidden">Back</span>
              </Button>
            </div>
          </div>

          <!-- Class Info Grid -->
          <div class="grid grid-cols-2 gap-2 sm:gap-4 text-xs sm:text-sm">
            <div class="flex items-center space-x-2 min-w-0">
              <MapPinIcon class="w-3 h-3 sm:w-4 sm:h-4 text-muted-foreground flex-shrink-0" />
              <span class="truncate">{{ classData.room || 'TBA' }}</span>
            </div>
            <div class="flex items-center space-x-2 min-w-0">
              <UsersIcon class="w-3 h-3 sm:w-4 sm:h-4 text-muted-foreground flex-shrink-0" />
              <span class="truncate">{{ classData.student_count || 0 }} students</span>
            </div>
            <div class="flex items-center space-x-2 min-w-0">
              <ClockIcon class="w-3 h-3 sm:w-4 sm:h-4 text-muted-foreground flex-shrink-0" />
              <span class="truncate">{{ classData.units || 3 }} units</span>
            </div>
            <div class="flex items-center space-x-2 min-w-0">
              <CalendarIcon class="w-3 h-3 sm:w-4 sm:h-4 text-muted-foreground flex-shrink-0" />
              <span class="truncate">{{ currentSemester }} {{ schoolYear }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <Card>
          <CardContent class="p-3 sm:p-4">
            <div class="flex items-center space-x-2">
              <div class="p-1.5 sm:p-2 bg-blue-100 rounded-lg">
                <UsersIcon class="w-4 h-4 sm:w-5 sm:h-5 text-blue-600" />
              </div>
              <div class="min-w-0">
                <p class="text-lg sm:text-2xl font-bold text-foreground">{{ stats.total_students }}</p>
                <p class="text-xs sm:text-sm text-muted-foreground">Total Students</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-3 sm:p-4">
            <div class="flex items-center space-x-2">
              <div class="p-1.5 sm:p-2 bg-green-100 rounded-lg">
                <CheckCircleIcon class="w-4 h-4 sm:w-5 sm:h-5 text-green-600" />
              </div>
              <div class="min-w-0">
                <p class="text-lg sm:text-2xl font-bold text-foreground">{{ stats.attendance_rate }}%</p>
                <p class="text-xs sm:text-sm text-muted-foreground">Attendance Rate</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-3 sm:p-4">
            <div class="flex items-center space-x-2">
              <div class="p-1.5 sm:p-2 bg-yellow-100 rounded-lg">
                <AcademicCapIcon class="w-4 h-4 sm:w-5 sm:h-5 text-yellow-600" />
              </div>
              <div class="min-w-0">
                <p class="text-lg sm:text-2xl font-bold text-foreground">{{ stats.average_grade }}</p>
                <p class="text-xs sm:text-sm text-muted-foreground">Average Grade</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-3 sm:p-4">
            <div class="flex items-center space-x-2">
              <div class="p-1.5 sm:p-2 bg-purple-100 rounded-lg">
                <ChartBarIcon class="w-4 h-4 sm:w-5 sm:h-5 text-purple-600" />
              </div>
              <div class="min-w-0">
                <p class="text-lg sm:text-2xl font-bold text-foreground">{{ stats.passing_rate }}%</p>
                <p class="text-xs sm:text-sm text-muted-foreground">Passing Rate</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Main Content Tabs -->
      <div class="bg-card rounded-lg border border-border overflow-hidden w-full">
        <!-- Mobile Tab Navigation -->
        <div class="border-b border-border overflow-x-auto">
          <nav class="flex space-x-2 sm:space-x-8 px-3 sm:px-6 min-w-max" aria-label="Tabs">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                activeTab === tab.id
                  ? 'border-primary text-primary'
                  : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border',
                'whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-medium text-xs sm:text-sm flex items-center'
              ]"
            >
              <component :is="tab.icon" class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" />
              <span class="hidden sm:inline">{{ tab.name }}</span>
              <span class="sm:hidden">{{ tab.name.split(' ')[0] }}</span>
            </button>
          </nav>
        </div>

        <div class="p-3 sm:p-6 overflow-hidden">
          <!-- Students Tab -->
          <div v-if="activeTab === 'students'" class="space-y-3 sm:space-y-4">
            <div class="flex flex-col space-y-2 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
              <h3 class="text-base sm:text-lg font-semibold text-foreground">Enrolled Students</h3>
              <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                <Button variant="outline" size="sm" class="w-full sm:w-auto" @click="showAddStudentModal = true">
                  <UserPlusIcon class="w-4 h-4 mr-2" />
                  Report Missing Student
                </Button>
                <div class="flex gap-2 w-full sm:w-auto">
                  <Button variant="outline" size="sm" class="w-full sm:w-auto" @click="exportStudents('excel')">
                    <DocumentArrowDownIcon class="w-4 h-4 mr-2" />
                    Export Excel
                  </Button>
                  <Button variant="outline" size="sm" class="w-full sm:w-auto" @click="exportStudents('pdf')">
                    <DocumentArrowDownIcon class="w-4 h-4 mr-2" />
                    Export PDF
                  </Button>
                </div>
              </div>
            </div>

            <!-- Mobile Card Layout -->
            <div class="block lg:hidden space-y-3">
              <div v-if="!classData.students || classData.students.length === 0" class="text-center py-8 text-muted-foreground">
                No students enrolled in this class yet.
              </div>
              <div v-for="student in classData.students" :key="student.id" class="border border-border rounded-lg p-3 space-y-2">
                <div class="flex items-center space-x-3">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                      <span class="text-sm font-medium text-primary">
                        {{ student.name.charAt(0) }}
                      </span>
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-foreground truncate">{{ student.name }}</div>
                    <div class="text-xs text-muted-foreground truncate">{{ student.email }}</div>
                  </div>
                  <Button variant="ghost" size="sm">
                    <EyeIcon class="w-4 h-4" />
                  </Button>
                </div>
                <div class="grid grid-cols-3 gap-2 text-xs">
                  <div>
                    <div class="text-muted-foreground">Student #</div>
                    <div class="font-medium truncate">{{ student.student_number }}</div>
                  </div>
                  <div>
                    <div class="text-muted-foreground">Status</div>
                    <Badge :variant="student.status === 'active' ? 'default' : 'secondary'" class="text-xs">
                      {{ student.status }}
                    </Badge>
                  </div>
                  <div>
                    <div class="text-muted-foreground">Grade</div>
                    <div class="font-medium">{{ student.grade || 'N/A' }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Desktop Table Layout -->
            <div class="hidden lg:block border border-border rounded-lg overflow-hidden">
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

          <!-- Add Missing Student Modal -->
          <Dialog :open="showAddStudentModal" @update:open="showAddStudentModal = $event">
            <DialogContent class="sm:max-w-md">
              <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                  <UserPlusIcon class="w-5 h-5 text-primary" />
                  Report Missing Student
                </DialogTitle>
                <DialogDescription>
                  If you have a student in your class who is not listed above, please provide their information below.
                  This will create a request for administrators to add them to the class roster.
                </DialogDescription>
              </DialogHeader>

              <form @submit.prevent="submitMissingStudentRequest" class="space-y-4">
                <div class="space-y-2">
                  <Label for="student-name">Student Full Name *</Label>
                  <Input
                    id="student-name"
                    v-model="missingStudentForm.fullName"
                    type="text"
                    placeholder="Enter student's full name"
                    required
                    class="w-full"
                  />
                </div>

                <div class="space-y-2">
                  <Label for="student-id">Student ID (if known)</Label>
                  <Input
                    id="student-id"
                    v-model="missingStudentForm.studentId"
                    type="text"
                    placeholder="Enter student ID (optional)"
                    class="w-full"
                  />
                </div>

                <div class="space-y-2">
                  <Label for="student-email">Email (if known)</Label>
                  <Input
                    id="student-email"
                    v-model="missingStudentForm.email"
                    type="email"
                    placeholder="Enter student email (optional)"
                    class="w-full"
                  />
                </div>

                <div class="space-y-2">
                  <Label for="additional-notes">Additional Notes</Label>
                  <textarea
                    id="additional-notes"
                    v-model="missingStudentForm.notes"
                    rows="3"
                    placeholder="Any additional information that might help identify the student..."
                    class="w-full px-3 py-2 border border-border rounded-md text-sm bg-background resize-none"
                  ></textarea>
                </div>
              </form>

              <!-- Previous Requests Section -->
              <div v-if="previousRequests.length > 0" class="border-t pt-4 mt-4">
                <h4 class="text-sm font-medium text-foreground mb-3">Your Previous Requests for This Class</h4>
                <div class="space-y-2 max-h-40 overflow-y-auto">
                  <div
                    v-for="request in previousRequests"
                    :key="request.id"
                    class="flex items-center justify-between p-2 bg-muted/50 rounded-md text-sm"
                  >
                    <div class="flex-1 min-w-0">
                      <div class="font-medium truncate">{{ request.full_name }}</div>
                      <div class="text-xs text-muted-foreground">
                        Submitted {{ formatDate(request.submitted_at) }}
                      </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                      <Badge
                        :variant="getRequestStatusVariant(request.status)"
                        class="text-xs"
                      >
                        {{ request.status }}
                      </Badge>
                      <button
                        v-if="request.admin_notes"
                        @click="showRequestDetails(request)"
                        class="text-muted-foreground hover:text-foreground"
                        title="View admin notes"
                      >
                        <Icon icon="heroicons:information-circle" class="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                </div>
                <div class="text-xs text-muted-foreground mt-2">
                  <Icon icon="heroicons:information-circle" class="w-3 h-3 mr-1 inline" />
                  Requests are reviewed by administrators. You'll receive a notification when processed.
                </div>
              </div>

              <DialogFooter class="gap-2">
                <Button variant="outline" @click="closeAddStudentModal">
                  Cancel
                </Button>
                <Button
                  @click="submitMissingStudentRequest"
                  :disabled="!missingStudentForm.fullName.trim() || isSubmittingRequest"
                  class="min-w-[120px]"
                >
                  <Icon v-if="isSubmittingRequest" icon="heroicons:arrow-path" class="w-4 h-4 mr-2 animate-spin" />
                  <UserPlusIcon v-else class="w-4 h-4 mr-2" />
                  {{ isSubmittingRequest ? 'Submitting...' : 'Submit Request' }}
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>

          <!-- Request Details Modal -->
          <Dialog :open="showRequestDetailsModal" @update:open="showRequestDetailsModal = $event">
            <DialogContent class="sm:max-w-md">
              <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                  <Icon icon="heroicons:document-text" class="w-5 h-5 text-primary" />
                  Request Details
                </DialogTitle>
              </DialogHeader>

              <div v-if="selectedRequest" class="space-y-4">
                <div>
                  <Label class="text-sm font-medium">Student Name</Label>
                  <div class="text-sm text-muted-foreground">{{ selectedRequest.full_name }}</div>
                </div>

                <div v-if="selectedRequest.student_id">
                  <Label class="text-sm font-medium">Student ID</Label>
                  <div class="text-sm text-muted-foreground">{{ selectedRequest.student_id }}</div>
                </div>

                <div v-if="selectedRequest.email">
                  <Label class="text-sm font-medium">Email</Label>
                  <div class="text-sm text-muted-foreground">{{ selectedRequest.email }}</div>
                </div>

                <div>
                  <Label class="text-sm font-medium">Status</Label>
                  <div class="mt-1">
                    <Badge :variant="getRequestStatusVariant(selectedRequest.status)">
                      {{ selectedRequest.status }}
                    </Badge>
                  </div>
                </div>

                <div>
                  <Label class="text-sm font-medium">Submitted</Label>
                  <div class="text-sm text-muted-foreground">{{ formatDate(selectedRequest.submitted_at) }}</div>
                </div>

                <div v-if="selectedRequest.processed_at">
                  <Label class="text-sm font-medium">Processed</Label>
                  <div class="text-sm text-muted-foreground">{{ formatDate(selectedRequest.processed_at) }}</div>
                </div>

                <div v-if="selectedRequest.admin_notes">
                  <Label class="text-sm font-medium">Admin Notes</Label>
                  <div class="text-sm text-muted-foreground bg-muted/50 p-3 rounded-md">
                    {{ selectedRequest.admin_notes }}
                  </div>
                </div>

                <div v-if="selectedRequest.notes">
                  <Label class="text-sm font-medium">Your Notes</Label>
                  <div class="text-sm text-muted-foreground bg-muted/50 p-3 rounded-md">
                    {{ selectedRequest.notes }}
                  </div>
                </div>
              </div>

              <DialogFooter>
                <Button variant="outline" @click="showRequestDetailsModal = false">
                  Close
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>

          <!-- Schedule Tab -->
          <div v-if="activeTab === 'schedule'" class="space-y-3 sm:space-y-4">
            <div class="flex flex-col space-y-2 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
              <h3 class="text-base sm:text-lg font-semibold text-foreground">Class Schedule</h3>
            </div>

            <div class="grid gap-3 sm:gap-4">
              <Card v-if="!schedules || schedules.length === 0">
                <CardContent class="p-6 sm:p-8 text-center">
                  <CalendarIcon class="w-8 h-8 sm:w-12 sm:h-12 text-muted-foreground mx-auto mb-4" />
                  <p class="text-muted-foreground text-sm sm:text-base">No schedule set for this class yet.</p>
                </CardContent>
              </Card>

              <Card v-for="schedule in schedules" :key="schedule.id">
                <CardContent class="p-3 sm:p-4">
                  <div class="flex items-center space-x-3 sm:space-x-4">
                    <div class="p-1.5 sm:p-2 bg-blue-100 rounded-lg">
                      <CalendarIcon class="w-4 h-4 sm:w-5 sm:h-5 text-blue-600" />
                    </div>
                    <div class="min-w-0">
                      <p class="font-medium text-foreground text-sm sm:text-base">{{ schedule.day_of_week }}</p>
                      <p class="text-xs sm:text-sm text-muted-foreground">
                        {{ schedule.start_time }} - {{ schedule.end_time }}
                      </p>
                      <p class="text-xs sm:text-sm text-muted-foreground truncate">
                        {{ schedule.room }} • {{ schedule.duration }}
                      </p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </div>

          <!-- Grades Tab -->
          <div v-if="activeTab === 'grades'" class="space-y-3 sm:space-y-4">
            <div class="flex flex-col space-y-2 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
              <h3 class="text-base sm:text-lg font-semibold text-foreground">Grade Management</h3>
              <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                <Button variant="outline" size="sm" @click="showImportDialog = true" class="w-full sm:w-auto">
                  <DocumentArrowUpIcon class="w-4 h-4 mr-2" />
                  Import Grades
                </Button>
                <Button variant="outline" size="sm" @click="exportGrades" class="w-full sm:w-auto">
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
              <CardContent class="p-4 sm:p-6">
                <h4 class="text-base sm:text-lg font-semibold text-foreground mb-3 sm:mb-4">Grade Distribution</h4>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                  <div v-for="(count, range) in stats.grade_distribution" :key="range" class="text-center">
                    <div class="text-lg sm:text-2xl font-bold text-foreground">{{ count }}</div>
                    <div class="text-xs sm:text-sm text-muted-foreground">Range {{ range }}</div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Mobile Grades Cards -->
            <div class="block lg:hidden space-y-3">
              <div v-if="!classData.students || classData.students.length === 0" class="text-center py-8 text-muted-foreground">
                No students enrolled in this class yet.
              </div>
              <Card v-for="student in classData.students" :key="student.id">
                <CardContent class="p-4">
                  <div class="space-y-3">
                    <div class="font-medium text-foreground">{{ student.name }}</div>
                    <div class="grid grid-cols-1 gap-3">
                      <div class="flex items-center justify-between">
                        <label class="text-sm text-muted-foreground">Prelim (%)</label>
                        <input
                          type="number"
                          min="0"
                          max="100"
                          step="0.01"
                          class="w-16 sm:w-20 border border-border rounded px-2 py-1 bg-background text-sm"
                          :value="gradeEdits[student.student_id]?.prelim_grade ?? ''"
                          @input="updateGradeEdit(student.student_id, 'prelim_grade', $event.target.value)"
                          @change="saveSingleGrade(student.student_id)"
                          :placeholder="student.prelim_grade ?? '—'"
                        />
                      </div>
                      <div class="flex items-center justify-between">
                        <label class="text-sm text-muted-foreground">Midterm (%)</label>
                        <input
                          type="number"
                          min="0"
                          max="100"
                          step="0.01"
                          class="w-16 sm:w-20 border border-border rounded px-2 py-1 bg-background text-sm"
                          :value="gradeEdits[student.student_id]?.midterm_grade ?? ''"
                          @input="updateGradeEdit(student.student_id, 'midterm_grade', $event.target.value)"
                          @change="saveSingleGrade(student.student_id)"
                          :placeholder="student.midterm_grade ?? '—'"
                        />
                      </div>
                      <div class="flex items-center justify-between">
                        <label class="text-sm text-muted-foreground">Finals (%)</label>
                        <input
                          type="number"
                          min="0"
                          max="100"
                          step="0.01"
                          class="w-16 sm:w-20 border border-border rounded px-2 py-1 bg-background text-sm"
                          :value="gradeEdits[student.student_id]?.finals_grade ?? ''"
                          @input="updateGradeEdit(student.student_id, 'finals_grade', $event.target.value)"
                          @change="saveSingleGrade(student.student_id)"
                          :placeholder="student.finals_grade ?? '—'"
                        />
                      </div>
                      <div class="flex items-center justify-between border-t pt-2">
                        <label class="text-sm font-medium text-foreground">Total Average</label>
                        <span class="text-sm font-bold">{{ computeAverage(gradeEdits[student.student_id], student) }}%</span>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>

            <!-- Desktop Grades Table -->
            <div class="hidden lg:block border border-border rounded-lg overflow-hidden">
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
          <div v-if="activeTab === 'attendance'" class="space-y-4 sm:space-y-6">
            <!-- Attendance Setup Section -->
            <div v-if="!attendance.is_setup" class="space-y-3 sm:space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-base sm:text-lg font-semibold text-foreground">Setup Attendance Tracking</h3>
              </div>

              <Card>
                <CardContent class="p-4 sm:p-6">
                  <div class="text-center mb-4 sm:mb-6">
                    <ClipboardDocumentListIcon class="w-8 h-8 sm:w-12 sm:h-12 text-muted-foreground mx-auto mb-4" />
                    <h4 class="text-base sm:text-lg font-semibold mb-2">Configure Attendance for This Class</h4>
                    <p class="text-muted-foreground text-sm sm:text-base">Set up attendance tracking to start recording student attendance for each class session.</p>
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
            <div v-else class="space-y-3 sm:space-y-4">
              <div class="flex flex-col space-y-2 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-base sm:text-lg font-semibold text-foreground">Attendance Management</h3>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                  <Button
                    size="sm"
                    @click="initializeAttendance"
                    :disabled="attendanceLoading"
                    class="w-full sm:w-auto"
                  >
                    <ClipboardDocumentListIcon class="w-4 h-4 mr-2" />
                    <span class="hidden sm:inline">{{ attendanceData?.session_stats ? 'Manage Attendance' : 'Start Attendance' }}</span>
                    <span class="sm:hidden">{{ attendanceData?.session_stats ? 'Manage' : 'Start' }}</span>
                  </Button>
                  <Button variant="outline" size="sm" @click="exportAttendance" class="w-full sm:w-auto">
                    <DocumentArrowDownIcon class="w-4 h-4 mr-2" />
                    <span class="hidden sm:inline">Export Report</span>
                    <span class="sm:hidden">Export</span>
                  </Button>
                  <Button variant="outline" size="sm" @click="showAttendanceSettings = true" class="w-full sm:w-auto">
                    <CogIcon class="w-4 h-4 mr-2" />
                    Settings
                  </Button>
                  <Button variant="destructive" size="sm" @click="showResetConfirmation = true" class="w-full sm:w-auto">
                    <Icon icon="heroicons:arrow-path" class="w-4 h-4 mr-2" />
                    <span class="hidden sm:inline">Reset Attendance</span>
                    <span class="sm:hidden">Reset</span>
                  </Button>
                </div>
              </div>

              <!-- Attendance Date Selector -->
              <Card>
                <CardContent class="p-3 sm:p-4">
                  <div class="flex flex-col space-y-3 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
                      <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-foreground">Date:</label>
                        <input
                          type="date"
                          v-model="selectedDate"
                          @change="loadAttendanceData"
                          class="px-2 py-1 border border-border rounded-md text-sm w-auto max-w-full"
                        />
                      </div>
                      <div v-if="attendanceData?.session_stats" class="text-xs sm:text-sm text-muted-foreground">
                        {{ attendanceData.session_stats.present + attendanceData.session_stats.late }}/{{ attendanceData.session_stats.total }} Present
                        ({{ attendanceData.session_stats.attendance_rate }}%)
                      </div>
                    </div>
                    <div v-if="attendanceData?.settings?.method" class="text-xs sm:text-sm text-muted-foreground">
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
              <div v-if="attendanceData?.roster?.length" class="space-y-3 sm:space-y-4">
                <!-- Quick Stats -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                  <Card>
                    <CardContent class="p-3 sm:p-4 text-center">
                      <div class="text-lg sm:text-2xl font-bold">{{ attendanceData.session_stats?.total || 0 }}</div>
                      <div class="text-xs sm:text-sm text-muted-foreground">Total</div>
                    </CardContent>
                  </Card>
                  <Card>
                    <CardContent class="p-3 sm:p-4 text-center">
                      <div class="text-lg sm:text-2xl font-bold text-green-600">{{ attendanceData.session_stats?.present || 0 }}</div>
                      <div class="text-xs sm:text-sm text-muted-foreground">Present</div>
                    </CardContent>
                  </Card>
                  <Card>
                    <CardContent class="p-3 sm:p-4 text-center">
                      <div class="text-lg sm:text-2xl font-bold text-red-600">{{ attendanceData.session_stats?.absent || 0 }}</div>
                      <div class="text-xs sm:text-sm text-muted-foreground">Absent</div>
                    </CardContent>
                  </Card>
                  <Card>
                    <CardContent class="p-3 sm:p-4 text-center">
                      <div class="text-lg sm:text-2xl font-bold text-blue-600">{{ attendanceData.session_stats?.attendance_rate || 0 }}%</div>
                      <div class="text-xs sm:text-sm text-muted-foreground">Rate</div>
                    </CardContent>
                  </Card>
                </div>

                <!-- Quick Actions -->
                <Card>
                  <CardContent class="p-3 sm:p-4">
                    <div class="flex flex-col space-y-3 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
                      <div>
                        <h3 class="font-medium text-sm sm:text-base">Quick Actions</h3>
                        <p class="text-xs sm:text-sm text-muted-foreground">Mark all students at once</p>
                      </div>
                      <div class="flex flex-col sm:flex-row gap-2">
                        <Button @click="markAllPresent" variant="default" size="sm" class="w-full sm:w-auto">
                          <CheckIcon class="w-4 h-4 mr-2" />
                          All Present
                        </Button>
                        <Button @click="markAllAbsent" variant="outline" size="sm" class="w-full sm:w-auto">
                          <XMarkIcon class="w-4 h-4 mr-2" />
                          All Absent
                        </Button>
                      </div>
                    </div>
                  </CardContent>
                </Card>

                <!-- Mobile Attendance Cards -->
                <div class="block lg:hidden space-y-3">
                  <Card v-for="student in attendanceData.roster" :key="student.student_id">
                    <CardContent class="p-4">
                      <div class="space-y-3">
                        <!-- Student Info -->
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
                          <div class="flex-1 min-w-0">
                            <div class="font-medium truncate">{{ student.student.name }}</div>
                            <div class="text-sm text-muted-foreground">{{ student.student_id }}</div>
                          </div>
                          <Badge
                            :variant="getStatusVariant(student.attendance.status)"
                            class="capitalize"
                          >
                            {{ student.attendance.status }}
                          </Badge>
                        </div>

                        <!-- Action Buttons -->
                        <div class="grid grid-cols-2 gap-2">
                          <Button
                            @click="updateStudentStatus(student.student_id, 'present')"
                            :variant="student.attendance.status === 'present' ? 'default' : 'outline'"
                            size="sm"
                            class="w-full"
                          >
                            <CheckIcon class="w-4 h-4 mr-1" />
                            Present
                          </Button>
                          <Button
                            @click="updateStudentStatus(student.student_id, 'absent')"
                            :variant="student.attendance.status === 'absent' ? 'destructive' : 'outline'"
                            size="sm"
                            class="w-full"
                          >
                            <XMarkIcon class="w-4 h-4 mr-1" />
                            Absent
                          </Button>
                          <Button
                            @click="updateStudentStatus(student.student_id, 'late')"
                            :variant="student.attendance.status === 'late' ? 'secondary' : 'outline'"
                            size="sm"
                            class="w-full"
                          >
                            Late
                          </Button>
                          <Button
                            @click="updateStudentStatus(student.student_id, 'excused')"
                            :variant="student.attendance.status === 'excused' ? 'secondary' : 'outline'"
                            size="sm"
                            class="w-full"
                          >
                            Excused
                          </Button>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </div>

                <!-- Desktop Attendance Table -->
                <Card class="hidden lg:block">
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
                  <Button @click="saveAllAttendance" size="lg" class="px-6 sm:px-8 w-full sm:w-auto">
                    <CloudArrowUpIcon class="w-5 h-5 mr-2" />
                    Save Attendance
                  </Button>
                </div>
              </div>

              <!-- No Data State -->
              <Card v-else>
                <CardContent class="p-6 sm:p-8 text-center">
                  <ClipboardDocumentListIcon class="w-8 h-8 sm:w-12 sm:h-12 text-muted-foreground mx-auto mb-4" />
                  <p class="text-muted-foreground mb-4 text-sm sm:text-base">No attendance data for the selected date.</p>
                  <Button @click="initializeAttendance" :disabled="attendanceLoading" class="w-full sm:w-auto">
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

<style scoped>
/* Ensure no element can cause horizontal overflow */
* {
  max-width: 100%;
  box-sizing: border-box;
}

/* Ensure all containers respect viewport width */
.w-full {
  width: 100% !important;
  max-width: 100% !important;
}

/* Force text truncation on all text elements */
h1, h2, h3, h4, h5, h6, p, span, div {
  word-wrap: break-word;
  overflow-wrap: break-word;
}

/* Ensure input fields don't overflow */
input[type="number"], input[type="date"], input[type="text"] {
  max-width: 100%;
  min-width: 0;
}

/* Ensure buttons don't cause overflow */
button {
  max-width: 100%;
  min-width: 0;
}
</style>

<script setup>
import AttendanceMethodDisplay from '@/Components/Faculty/AttendanceMethodDisplay.vue'
import AttendanceSettingsModal from '@/Components/Faculty/AttendanceSettingsModal.vue'
import AttendanceSetupForm from '@/Components/Faculty/AttendanceSetupForm.vue'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/shadcn/ui/dialog'
import { Input } from '@/Components/shadcn/ui/input'
import { Label } from '@/Components/shadcn/ui/label'
import { Badge } from '@/Components/ui/badge.js'
import { Button } from '@/Components/ui/button.js'
import { Card, CardContent } from '@/Components/ui/card.js'
import FacultyLayout from '@/Layouts/FacultyLayout.vue'
import {
  AcademicCapIcon,
  ArrowLeftIcon,
  CalendarIcon,
  ChartBarIcon,
  CheckCircleIcon,
  CheckIcon,
  ClipboardDocumentListIcon,
  ClockIcon,
  CloudArrowUpIcon,
  CogIcon,
  DocumentArrowDownIcon,
  DocumentArrowUpIcon,
  EyeIcon,
  MapPinIcon,
  UserIcon,
  UserPlusIcon,
  UsersIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'
import { Icon } from '@iconify/vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { computed, ref, watch } from 'vue'
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

// Missing Student Modal State
const showAddStudentModal = ref(false)
const isSubmittingRequest = ref(false)
const missingStudentForm = ref({
  fullName: '',
  studentId: '',
  email: '',
  notes: ''
})

// Previous Requests State
const previousRequests = ref([])
const showRequestDetailsModal = ref(false)
const selectedRequest = ref(null)

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

// Watch for modal opening to load previous requests
watch(showAddStudentModal, (isOpen) => {
  if (isOpen) {
    loadPreviousRequests()
  }
})

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

// Missing Student Methods
const closeAddStudentModal = () => {
  showAddStudentModal.value = false
  // Reset form
  missingStudentForm.value = {
    fullName: '',
    studentId: '',
    email: '',
    notes: ''
  }
}

// Export Students
const exportStudents = (format) => {
  const url = route('faculty.classes.students.export', props.classData.id) + `?format=${format}`
  window.open(url, '_blank')
}

const loadPreviousRequests = async () => {
  try {
    const response = await axios.get(route('faculty.classes.missing-student.requests', props.classData.id))
    if (response.data.success) {
      previousRequests.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to load previous requests:', error)
  }
}

const showRequestDetails = (request) => {
  selectedRequest.value = request
  showRequestDetailsModal.value = true
}

const getRequestStatusVariant = (status) => {
  switch (status) {
    case 'pending':
      return 'secondary'
    case 'approved':
      return 'default'
    case 'rejected':
      return 'destructive'
    default:
      return 'secondary'
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const submitMissingStudentRequest = async () => {
  if (!missingStudentForm.value.fullName.trim()) {
    toast.error('Please enter the student\'s full name')
    return
  }

  isSubmittingRequest.value = true

  try {
    const response = await axios.post(route('faculty.classes.missing-student.request', props.classData.id), {
      full_name: missingStudentForm.value.fullName.trim(),
      student_id: missingStudentForm.value.studentId.trim() || null,
      email: missingStudentForm.value.email.trim() || null,
      notes: missingStudentForm.value.notes.trim() || null,
      class_id: props.classData.id,
      faculty_id: props.faculty.id
    })

    if (response.data.success) {
      toast.success('Missing student request submitted successfully! Administrators will review and add the student to your class.')
      closeAddStudentModal()
      // Reload previous requests to show the new one
      await loadPreviousRequests()
    } else {
      toast.error(response.data.message || 'Failed to submit request')
    }
  } catch (error) {
    console.error('Failed to submit missing student request:', error)
    if (error.response?.status === 422) {
      // Validation errors
      const errors = error.response.data.errors
      if (errors) {
        const firstError = Object.values(errors)[0][0]
        toast.error(firstError)
      } else {
        toast.error('Please check your input and try again')
      }
    } else if (error.response?.data?.message) {
      toast.error(error.response.data.message)
    } else {
      toast.error('Failed to submit request. Please try again.')
    }
  } finally {
    isSubmittingRequest.value = false
  }
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
