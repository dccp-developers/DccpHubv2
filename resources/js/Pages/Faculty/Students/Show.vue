<template>
  <FacultyLayout>
    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex items-center justify-between">
            <div>
              <Link
                :href="route('faculty.students.index')"
                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium mb-2 inline-block"
              >
                ← Back to Students
              </Link>
              <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ student.full_name }}</h1>
              <p class="mt-2 text-gray-600 dark:text-gray-400">
                Student Details - {{ schoolYear }} Semester {{ currentSemester }}
              </p>
            </div>
            <div class="flex-shrink-0">
              <img
                :src="student.profile_photo || '/default-avatar.png'"
                :alt="student.full_name"
                class="h-20 w-20 rounded-full object-cover border-4 border-white dark:border-gray-700 shadow-lg"
              />
            </div>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
            </div>
            <div class="ml-3">
              <p class="text-sm text-red-800 dark:text-red-200">{{ error }}</p>
            </div>
          </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6">
          <nav class="flex space-x-8">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                'py-2 px-1 border-b-2 font-medium text-sm',
                activeTab === tab.id
                  ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
              ]"
            >
              {{ tab.name }}
            </button>
          </nav>
        </div>

        <!-- Tab Content -->
        <div class="space-y-6">
          <!-- Overview Tab -->
          <div v-if="activeTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Basic Information -->
            <div class="lg:col-span-2">
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Basic Information</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Student ID</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ student.student_id }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ student.email }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Gender</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ student.gender }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Age</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ student.age }} years old</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Birth Date</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(student.birth_date) }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Academic Year</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">Year {{ student.academic_year }}</dd>
                  </div>
                  <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Address</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ student.address }}</dd>
                  </div>
                  <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Contact Information</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ student.contacts }}</dd>
                  </div>
                </dl>
              </div>
            </div>

            <!-- Performance Summary -->
            <div>
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Performance Summary</h3>
                <div class="space-y-4">
                  <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                      {{ performance.average_grade }}%
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Overall Average</div>
                  </div>
                  
                  <div class="grid grid-cols-2 gap-4 text-center">
                    <div>
                      <div class="text-xl font-semibold text-green-600 dark:text-green-400">
                        {{ performance.passing_classes }}
                      </div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">Passing</div>
                    </div>
                    <div>
                      <div class="text-xl font-semibold text-red-600 dark:text-red-400">
                        {{ performance.failing_classes }}
                      </div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">Failing</div>
                    </div>
                  </div>

                  <div>
                    <div class="flex justify-between text-sm mb-1">
                      <span class="text-gray-600 dark:text-gray-400">Passing Rate</span>
                      <span class="font-medium text-gray-900 dark:text-white">{{ performance.passing_rate }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                      <div
                        class="bg-green-500 h-2 rounded-full"
                        :style="{ width: performance.passing_rate + '%' }"
                      ></div>
                    </div>
                  </div>

                  <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Grade Distribution</h4>
                    <div class="space-y-1">
                      <div v-for="(count, grade) in performance.grade_distribution" :key="grade" class="flex justify-between text-xs">
                        <span class="text-gray-600 dark:text-gray-400">{{ grade }}</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ count }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Classes Tab -->
          <div v-if="activeTab === 'classes'">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Class Enrollments</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                  Classes the student is enrolled in with you
                </p>
              </div>
              
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                  <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Subject
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Section
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Room
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Grades
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Status
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="enrollment in classEnrollments" :key="enrollment.id">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                          {{ enrollment.subject_title }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                          {{ enrollment.subject_code }}
                        </div>
                        <div class="text-xs text-gray-400 dark:text-gray-500">
                          {{ enrollment.classification.toUpperCase() }}
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                        {{ enrollment.section }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                        {{ enrollment.room }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900 dark:text-white">
                          <div class="grid grid-cols-3 gap-2 text-xs">
                            <div>
                              <span class="text-gray-500 dark:text-gray-400">P:</span>
                              {{ enrollment.prelim_grade || 'N/A' }}
                            </div>
                            <div>
                              <span class="text-gray-500 dark:text-gray-400">M:</span>
                              {{ enrollment.midterm_grade || 'N/A' }}
                            </div>
                            <div>
                              <span class="text-gray-500 dark:text-gray-400">F:</span>
                              {{ enrollment.finals_grade || 'N/A' }}
                            </div>
                          </div>
                          <div class="mt-1 font-medium">
                            Avg: {{ enrollment.total_average || 'N/A' }}
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span
                          :class="[
                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                            enrollment.grade_status === 'Passing'
                              ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                              : enrollment.grade_status === 'Failing'
                              ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                              : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                          ]"
                        >
                          {{ enrollment.grade_status || 'No Grade' }}
                        </span>
                        <div class="mt-1">
                          <span
                            :class="[
                              'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                              enrollment.status === 'enrolled'
                                ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                                : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                            ]"
                          >
                            {{ enrollment.status }}
                          </span>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Empty State -->
              <div v-if="classEnrollments.length === 0" class="text-center py-12">
                <AcademicCapIcon class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No class enrollments</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                  This student is not enrolled in any of your classes.
                </p>
              </div>
            </div>
          </div>

          <!-- Personal Info Tab -->
          <div v-if="activeTab === 'personal'">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- Course Information -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Course Information</h3>
                <dl class="space-y-4">
                  <div v-if="student.course">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Course</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ student.course.name }} ({{ student.course.code }})
                    </dd>
                  </div>
                  <div v-if="student.course?.description">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ student.course.description }}</dd>
                  </div>
                </dl>
              </div>

              <!-- Personal Information -->
              <div v-if="student.personal_info" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Personal Information</h3>
                <dl class="space-y-4">
                  <div v-if="student.personal_info.nationality">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nationality</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ student.personal_info.nationality }}</dd>
                  </div>
                  <div v-if="student.personal_info.religion">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Religion</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ student.personal_info.religion }}</dd>
                  </div>
                  <div v-if="student.personal_info.civil_status">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Civil Status</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ student.personal_info.civil_status }}</dd>
                  </div>
                  <div v-if="student.personal_info.place_of_birth">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Place of Birth</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ student.personal_info.place_of_birth }}</dd>
                  </div>
                </dl>
              </div>

              <!-- Contact Information -->
              <div v-if="student.contact_info" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Contact Information</h3>
                <dl class="space-y-4">
                  <div v-if="student.contact_info.personal_contact">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Personal Contact</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ student.contact_info.personal_contact }}</dd>
                  </div>
                  <div v-if="student.contact_info.facebook_contact">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Facebook Contact</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ student.contact_info.facebook_contact }}</dd>
                  </div>
                  <div v-if="student.contact_info.emergency_contact_name">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Emergency Contact</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ student.contact_info.emergency_contact_name }}
                      <div v-if="student.contact_info.emergency_contact_phone" class="text-xs text-gray-500 dark:text-gray-400">
                        Phone: {{ student.contact_info.emergency_contact_phone }}
                      </div>
                      <div v-if="student.contact_info.emergency_contact_address" class="text-xs text-gray-500 dark:text-gray-400">
                        Address: {{ student.contact_info.emergency_contact_address }}
                      </div>
                    </dd>
                  </div>
                </dl>
              </div>

              <!-- Parent Information -->
              <div v-if="student.parent_info" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Parent/Guardian Information</h3>
                <dl class="space-y-4">
                  <div v-if="student.parent_info.father_name">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Father</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ student.parent_info.father_name }}
                    </dd>
                  </div>
                  <div v-if="student.parent_info.mother_name">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Mother</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ student.parent_info.mother_name }}
                    </dd>
                  </div>
                </dl>
              </div>
            </div>
          </div>

          <!-- Education Tab -->
          <div v-if="activeTab === 'education' && student.education_info">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Educational Background</h3>
              <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-if="student.education_info.elementary_school">
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Elementary School</dt>
                  <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ student.education_info.elementary_school }}
                    <div v-if="student.education_info.elementary_year_graduated" class="text-xs text-gray-500 dark:text-gray-400">
                      Graduated: {{ student.education_info.elementary_year_graduated }}
                    </div>
                  </dd>
                </div>
                <div v-if="student.education_info.junior_high_school">
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Junior High School</dt>
                  <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ student.education_info.junior_high_school }}
                    <div v-if="student.education_info.junior_high_year_graduated" class="text-xs text-gray-500 dark:text-gray-400">
                      Graduated: {{ student.education_info.junior_high_year_graduated }}
                    </div>
                    <div v-if="student.education_info.junior_high_school_address" class="text-xs text-gray-500 dark:text-gray-400">
                      Address: {{ student.education_info.junior_high_school_address }}
                    </div>
                  </dd>
                </div>
                <div v-if="student.education_info.senior_high_school">
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Senior High School</dt>
                  <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ student.education_info.senior_high_school }}
                    <div v-if="student.education_info.senior_high_year_graduated" class="text-xs text-gray-500 dark:text-gray-400">
                      Graduated: {{ student.education_info.senior_high_year_graduated }}
                    </div>
                    <div v-if="student.education_info.senior_high_school_address" class="text-xs text-gray-500 dark:text-gray-400">
                      Address: {{ student.education_info.senior_high_school_address }}
                    </div>
                  </dd>
                </div>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>
  </FacultyLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import FacultyLayout from '@/Layouts/FacultyLayout.vue'
import { ExclamationTriangleIcon, AcademicCapIcon } from '@heroicons/vue/24/outline'

// Props from the controller
const props = defineProps({
  student: Object,
  classEnrollments: Array,
  performance: Object,
  faculty: Object,
  currentSemester: String,
  schoolYear: String,
  availableSemesters: Object,
  availableSchoolYears: Object,
  error: String
})

// Reactive data
const activeTab = ref('overview')

const tabs = [
  { id: 'overview', name: 'Overview' },
  { id: 'classes', name: 'Classes' },
  { id: 'personal', name: 'Personal Info' },
  { id: 'education', name: 'Education' },
]

// Methods
const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}
</script>
