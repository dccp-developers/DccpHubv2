<template>
  <FacultyLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-foreground">My Classes</h1>
          <p class="mt-1 text-sm text-muted-foreground">
            Manage your classes for {{ currentSemester }} {{ schoolYear }}
          </p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center space-x-3">
          <Button variant="outline" size="sm">
            <DocumentArrowDownIcon class="w-4 h-4 mr-2" />
            Export List
          </Button>
          <Button size="sm">
            <PlusIcon class="w-4 h-4 mr-2" />
            Add Class
          </Button>
        </div>
      </div>

      <!-- Error Message -->
      <div v-if="props.error" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
        <div class="flex items-center space-x-2">
          <svg class="w-5 h-5 text-destructive" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <p class="text-destructive font-medium">{{ props.error }}</p>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center space-x-3">
              <div class="p-2 bg-blue-100 rounded-lg">
                <BookOpenIcon class="w-5 h-5 text-blue-600" />
              </div>
              <div>
                <p class="text-2xl font-bold text-foreground">{{ classes.length }}</p>
                <p class="text-sm text-muted-foreground">Total Classes</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center space-x-3">
              <div class="p-2 bg-green-100 rounded-lg">
                <UsersIcon class="w-5 h-5 text-green-600" />
              </div>
              <div>
                <p class="text-2xl font-bold text-foreground">{{ totalStudents }}</p>
                <p class="text-sm text-muted-foreground">Total Students</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center space-x-3">
              <div class="p-2 bg-purple-100 rounded-lg">
                <AcademicCapIcon class="w-5 h-5 text-purple-600" />
              </div>
              <div>
                <p class="text-2xl font-bold text-foreground">{{ subjects.length }}</p>
                <p class="text-sm text-muted-foreground">Subjects</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center space-x-3">
              <div class="p-2 bg-yellow-100 rounded-lg">
                <CalendarIcon class="w-5 h-5 text-yellow-600" />
              </div>
              <div>
                <p class="text-2xl font-bold text-foreground">{{ weeklySchedules }}</p>
                <p class="text-sm text-muted-foreground">Weekly Schedules</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Filters and Search -->
      <div class="bg-card rounded-lg border border-border p-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
          <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
            <div class="relative">
              <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search classes..."
                class="pl-10 pr-4 py-2 border border-border rounded-md bg-background text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              />
            </div>
            <select
              v-model="selectedClassification"
              class="px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            >
              <option value="">All Classifications</option>
              <option value="college">College</option>
              <option value="shs">SHS</option>
            </select>
          </div>
          <div class="flex items-center space-x-2">
            <Button variant="outline" size="sm" @click="toggleView">
              <component :is="viewMode === 'grid' ? ListBulletIcon : Squares2X2Icon" class="w-4 h-4 mr-2" />
              {{ viewMode === 'grid' ? 'List View' : 'Grid View' }}
            </Button>
          </div>
        </div>
      </div>

      <!-- Classes Display -->
      <div v-if="filteredClasses.length === 0" class="text-center py-12">
        <div class="max-w-md mx-auto">
          <BookOpenIcon class="w-12 h-12 text-muted-foreground mx-auto mb-4" />
          <h3 class="text-lg font-medium text-foreground mb-2">
            {{ searchQuery || selectedClassification ? 'No classes match your filters' : 'No classes found' }}
          </h3>
          <p class="text-muted-foreground mb-6">
            {{ searchQuery || selectedClassification
              ? 'Try adjusting your search or filter criteria to find the classes you\'re looking for.'
              : 'You don\'t have any classes assigned for this semester yet. Contact your administrator to get classes assigned.'
            }}
          </p>
          <div class="flex flex-col sm:flex-row items-center justify-center space-y-2 sm:space-y-0 sm:space-x-3">
            <Button v-if="searchQuery || selectedClassification" variant="outline" @click="clearFilters">
              Clear Filters
            </Button>
            <Button v-if="!searchQuery && !selectedClassification" variant="outline">
              <PlusIcon class="w-4 h-4 mr-2" />
              Request Class Assignment
            </Button>
          </div>
        </div>
      </div>

      <!-- Grid View -->
      <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <Card 
          v-for="classItem in filteredClasses" 
          :key="classItem.id"
          class="cursor-pointer hover:shadow-md transition-shadow duration-200"
          @click="viewClass(classItem)"
        >
          <CardContent class="p-6">
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <div class="flex items-center space-x-2 mb-1">
                  <h3 class="text-lg font-semibold text-foreground">{{ classItem.subject_code }}</h3>
                  <Badge variant="secondary" class="text-xs">
                    Sec {{ classItem.section }}
                  </Badge>
                </div>
                <p class="text-sm text-muted-foreground mb-2">{{ classItem.subject_title }}</p>
                <Badge 
                  :variant="classItem.classification === 'college' ? 'default' : 'outline'" 
                  class="text-xs"
                >
                  {{ classItem.classification === 'college' ? 'College' : 'SHS' }}
                </Badge>
              </div>
            </div>
            
            <div class="space-y-2 text-sm text-muted-foreground">
              <div class="flex items-center space-x-2">
                <MapPinIcon class="w-4 h-4" />
                <span>{{ classItem.room || 'TBA' }}</span>
              </div>
              <div class="flex items-center space-x-2">
                <UsersIcon class="w-4 h-4" />
                <span>{{ classItem.student_count || 0 }} students</span>
              </div>
              <div class="flex items-center space-x-2">
                <ClockIcon class="w-4 h-4" />
                <span>{{ classItem.units || 3 }} units</span>
              </div>
            </div>

            <div class="mt-4 pt-4 border-t border-border">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <Button variant="ghost" size="sm" @click.stop="viewClass(classItem)">
                    <EyeIcon class="w-3 h-3 mr-1" />
                    View
                  </Button>
                  <Button variant="ghost" size="sm" @click.stop="editClass(classItem)">
                    <PencilIcon class="w-3 h-3 mr-1" />
                    Edit
                  </Button>
                </div>
                <ChevronRightIcon class="w-4 h-4 text-muted-foreground" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- List View -->
      <div v-else class="bg-card rounded-lg border border-border overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-border">
            <thead class="bg-muted/50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Class
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Classification
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Room
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Students
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Units
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-card divide-y divide-border">
              <tr 
                v-for="classItem in filteredClasses" 
                :key="classItem.id"
                class="hover:bg-muted/50 cursor-pointer"
                @click="viewClass(classItem)"
              >
                <td class="px-6 py-4 whitespace-nowrap">
                  <div>
                    <div class="flex items-center space-x-2">
                      <div class="text-sm font-medium text-foreground">{{ classItem.subject_code }}</div>
                      <Badge variant="secondary" class="text-xs">
                        Sec {{ classItem.section }}
                      </Badge>
                    </div>
                    <div class="text-sm text-muted-foreground">{{ classItem.subject_title }}</div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <Badge 
                    :variant="classItem.classification === 'college' ? 'default' : 'outline'" 
                    class="text-xs"
                  >
                    {{ classItem.classification === 'college' ? 'College' : 'SHS' }}
                  </Badge>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                  {{ classItem.room || 'TBA' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                  {{ classItem.student_count || 0 }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                  {{ classItem.units || 3 }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <div class="flex items-center space-x-2">
                    <Button variant="ghost" size="sm" @click.stop="viewClass(classItem)">
                      <EyeIcon class="w-4 h-4" />
                    </Button>
                    <Button variant="ghost" size="sm" @click.stop="editClass(classItem)">
                      <PencilIcon class="w-4 h-4" />
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </FacultyLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import FacultyLayout from '@/Layouts/FacultyLayout.vue'
import { Card, CardContent } from '@/Components/ui/card.js'
import { Button } from '@/Components/ui/button.js'
import { Badge } from '@/Components/ui/badge.js'
import {
  BookOpenIcon,
  UsersIcon,
  AcademicCapIcon,
  CalendarIcon,
  DocumentArrowDownIcon,
  PlusIcon,
  MagnifyingGlassIcon,
  ListBulletIcon,
  Squares2X2Icon,
  MapPinIcon,
  ClockIcon,
  ChevronRightIcon,
  EyeIcon,
  PencilIcon
} from '@heroicons/vue/24/outline'

// Props from the controller
const props = defineProps({
  classes: Array,
  stats: Array,
  subjects: Array,
  faculty: Object,
  currentSemester: String,
  schoolYear: String,
  availableSemesters: Object,
  availableSchoolYears: Object,
  error: String
})

// Reactive data
const searchQuery = ref('')
const selectedClassification = ref('')
const viewMode = ref('grid')

// Computed properties
const classes = computed(() => props.classes || [])
const subjects = computed(() => props.subjects || [])
const stats = computed(() => props.stats || [])

const totalStudents = computed(() => {
  return classes.value.reduce((total, classItem) => total + (classItem.student_count || 0), 0)
})

const weeklySchedules = computed(() => {
  // Count unique days that have schedules
  const uniqueDays = new Set()
  classes.value.forEach(classItem => {
    if (classItem.schedules && classItem.schedules.length > 0) {
      classItem.schedules.forEach(schedule => {
        uniqueDays.add(schedule.day_of_week)
      })
    }
  })
  return uniqueDays.size
})

const filteredClasses = computed(() => {
  let filtered = classes.value

  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(classItem => 
      classItem.subject_code.toLowerCase().includes(query) ||
      classItem.subject_title.toLowerCase().includes(query) ||
      classItem.section.toLowerCase().includes(query)
    )
  }

  // Filter by classification
  if (selectedClassification.value) {
    filtered = filtered.filter(classItem => 
      classItem.classification === selectedClassification.value
    )
  }

  return filtered
})

// Methods
const toggleView = () => {
  viewMode.value = viewMode.value === 'grid' ? 'list' : 'grid'
}

const viewClass = (classItem) => {
  router.visit(route('faculty.classes.show', { class: classItem.id }))
}

const editClass = (classItem) => {
  console.log('Edit class:', classItem)
  // TODO: Implement edit functionality
}

const clearFilters = () => {
  searchQuery.value = ''
  selectedClassification.value = ''
}
</script>
