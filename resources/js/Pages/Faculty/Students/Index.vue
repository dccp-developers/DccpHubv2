<template>
  <FacultyLayout>
    <div class="container mx-auto py-6 px-4">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold tracking-tight">My Students</h1>
            <p class="text-muted-foreground mt-2">
              Manage and view students in your classes for {{ schoolYear }} - Semester {{ currentSemester }}
            </p>
          </div>
          <div class="flex items-center space-x-2">
            <Badge variant="outline" class="text-sm">
              {{ students.total }} Total Students
            </Badge>
          </div>
        </div>
      </div>

      <!-- Error Alert -->
      <Alert v-if="error" variant="destructive" class="mb-6">
        <AlertTriangle class="h-4 w-4" />
        <AlertTitle>Error</AlertTitle>
        <AlertDescription>{{ error }}</AlertDescription>
      </Alert>

      <!-- Filters Card -->
      <Card class="mb-6">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Filter class="h-5 w-5" />
            Filter Students
          </CardTitle>
          <CardDescription>
            Use the filters below to find specific students
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search -->
            <div class="space-y-2">
              <Label for="search">Search Students</Label>
              <div class="relative">
                <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                <Input
                  id="search"
                  v-model="searchForm.search"
                  type="text"
                  placeholder="Name, Student ID, or Email"
                  class="pl-10"
                  @input="debouncedSearch"
                />
              </div>
            </div>

            <!-- Course Filter -->
            <div class="space-y-2">
              <Label for="course">Course</Label>
              <Select v-model="searchForm.course_id" @update:model-value="applyFilters">
                <SelectTrigger>
                  <SelectValue placeholder="All Courses" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Courses</SelectItem>
                  <SelectItem
                    v-for="course in filterOptions.courses"
                    :key="course.id"
                    :value="course.id.toString()"
                  >
                    {{ course.code }} - {{ course.title }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <!-- Academic Year Filter -->
            <div class="space-y-2">
              <Label for="academic-year">Academic Year</Label>
              <Select v-model="searchForm.academic_year" @update:model-value="applyFilters">
                <SelectTrigger>
                  <SelectValue placeholder="All Years" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Years</SelectItem>
                  <SelectItem
                    v-for="year in filterOptions.academicYears"
                    :key="year"
                    :value="year.toString()"
                  >
                    Year {{ year }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <!-- Class Filter -->
            <div class="space-y-2">
              <Label for="class">Class</Label>
              <Select v-model="searchForm.class_id" @update:model-value="applyFilters">
                <SelectTrigger>
                  <SelectValue placeholder="All Classes" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Classes</SelectItem>
                  <SelectItem
                    v-for="classItem in filterOptions.classes"
                    :key="classItem.id"
                    :value="classItem.id.toString()"
                  >
                    {{ classItem.subject_code }} - {{ classItem.section }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <!-- Clear Filters -->
          <div class="mt-6 flex justify-end">
            <Button variant="outline" @click="clearFilters" class="gap-2">
              <X class="h-4 w-4" />
              Clear Filters
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Students Table -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle class="flex items-center gap-2">
                <Users class="h-5 w-5" />
                Students List
              </CardTitle>
              <CardDescription>
                Showing {{ students.data.length }} of {{ students.total }} students
              </CardDescription>
            </div>
            <div class="text-sm text-muted-foreground">
              Page {{ students.current_page }} of {{ students.last_page }}
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <!-- Table -->
          <div class="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead class="w-[300px]">Student</TableHead>
                  <TableHead>Course</TableHead>
                  <TableHead>Year</TableHead>
                  <TableHead>Classes</TableHead>
                  <TableHead>Performance</TableHead>
                  <TableHead class="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="student in students.data" :key="student.id">
                  <TableCell>
                    <div class="flex items-center space-x-3">
                      <Avatar class="h-10 w-10">
                        <AvatarImage
                          :src="student.picture_1x1 || '/default-avatar.png'"
                          :alt="student.full_name"
                        />
                        <AvatarFallback>
                          {{ getStudentInitials(student) }}
                        </AvatarFallback>
                      </Avatar>
                      <div>
                        <div class="font-medium">{{ student.full_name }}</div>
                        <div class="text-sm text-muted-foreground">
                          ID: {{ student.student_id || 'N/A' }}
                        </div>
                        <div class="text-sm text-muted-foreground">
                          {{ student.email }}
                        </div>
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div>
                      <div class="font-medium">{{ student.course?.code }}</div>
                      <div class="text-sm text-muted-foreground">
                        {{ student.course?.title }}
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge variant="secondary">
                      Year {{ student.academic_year }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div>
                      <div class="font-medium">
                        {{ student.class_enrollments.length }} {{ student.class_enrollments.length === 1 ? 'class' : 'classes' }}
                      </div>
                      <div class="text-sm text-muted-foreground">
                        <span v-for="(enrollment, index) in student.class_enrollments.slice(0, 2)" :key="enrollment.id">
                          {{ enrollment.subject_code }}{{ index < Math.min(student.class_enrollments.length, 2) - 1 ? ', ' : '' }}
                        </span>
                        <span v-if="student.class_enrollments.length > 2">
                          +{{ student.class_enrollments.length - 2 }} more
                        </span>
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div>
                      <div class="font-medium">
                        {{ getAverageGrade(student.class_enrollments) }}%
                      </div>
                      <div class="text-sm text-muted-foreground">
                        {{ getPassingCount(student.class_enrollments) }}/{{ student.class_enrollments.length }} passing
                      </div>
                    </div>
                  </TableCell>
                  <TableCell class="text-right">
                    <Button variant="ghost" size="sm" asChild>
                      <Link :href="route('faculty.students.show', student.id)">
                        <Eye class="h-4 w-4 mr-2" />
                        View Details
                      </Link>
                    </Button>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>

          <!-- Empty State -->
          <div v-if="students.data.length === 0" class="text-center py-12">
            <div class="flex flex-col items-center justify-center space-y-4">
              <div class="rounded-full bg-muted p-4">
                <Users class="h-8 w-8 text-muted-foreground" />
              </div>
              <div class="space-y-2">
                <h3 class="text-lg font-semibold">No students found</h3>
                <p class="text-sm text-muted-foreground max-w-sm">
                  {{ Object.keys(filters).length > 0 ? 'Try adjusting your filters to find students.' : 'No students are enrolled in your classes yet.' }}
                </p>
              </div>
              <Button v-if="Object.keys(filters).length > 0" variant="outline" @click="clearFilters">
                Clear Filters
              </Button>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="students.last_page > 1" class="mt-6 pt-6 border-t">
            <div class="flex items-center justify-between">
              <!-- Mobile Pagination -->
              <div class="flex justify-between sm:hidden w-full">
                <Button
                  v-if="students.current_page > 1"
                  variant="outline"
                  size="sm"
                  asChild
                >
                  <Link :href="getPaginationUrl(students.current_page - 1)">
                    <ChevronLeft class="h-4 w-4 mr-2" />
                    Previous
                  </Link>
                </Button>
                <Button
                  v-if="students.current_page < students.last_page"
                  variant="outline"
                  size="sm"
                  asChild
                >
                  <Link :href="getPaginationUrl(students.current_page + 1)">
                    Next
                    <ChevronRight class="h-4 w-4 ml-2" />
                  </Link>
                </Button>
              </div>

              <!-- Desktop Pagination -->
              <div class="hidden sm:flex sm:items-center sm:justify-between w-full">
                <div>
                  <p class="text-sm text-muted-foreground">
                    Showing {{ ((students.current_page - 1) * students.per_page) + 1 }} to
                    {{ Math.min(students.current_page * students.per_page, students.total) }} of
                    {{ students.total }} results
                  </p>
                </div>
                <div class="flex items-center space-x-2">
                  <Button
                    v-if="students.current_page > 1"
                    variant="outline"
                    size="sm"
                    asChild
                  >
                    <Link :href="getPaginationUrl(students.current_page - 1)">
                      <ChevronLeft class="h-4 w-4 mr-2" />
                      Previous
                    </Link>
                  </Button>
                  <Button
                    v-if="students.current_page < students.last_page"
                    variant="outline"
                    size="sm"
                    asChild
                  >
                    <Link :href="getPaginationUrl(students.current_page + 1)">
                      Next
                      <ChevronRight class="h-4 w-4 ml-2" />
                    </Link>
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </FacultyLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import FacultyLayout from '@/Layouts/FacultyLayout.vue'

// Shadcn UI Components
import { Badge } from '@/Components/shadcn/ui/badge'
import { Button } from '@/Components/shadcn/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Input } from '@/Components/shadcn/ui/input'
import { Label } from '@/Components/shadcn/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/shadcn/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/shadcn/ui/table'
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/shadcn/ui/avatar'
import { Alert, AlertDescription, AlertTitle } from '@/Components/shadcn/ui/alert'

// Icons
import {
  AlertTriangle,
  ChevronLeft,
  ChevronRight,
  Eye,
  Filter,
  Search,
  Users,
  X
} from 'lucide-vue-next'

// Props from the controller
const props = defineProps({
  students: Object,
  filters: Object,
  filterOptions: Object,
  faculty: Object,
  currentSemester: String,
  schoolYear: String,
  availableSemesters: Object,
  availableSchoolYears: Object,
  error: String
})

// Reactive data
const searchForm = ref({
  search: props.filters.search || '',
  course_id: props.filters.course_id || 'all',
  academic_year: props.filters.academic_year || 'all',
  class_id: props.filters.class_id || 'all',
})

// Methods
const applyFilters = () => {
  const params = {}

  Object.keys(searchForm.value).forEach(key => {
    if (searchForm.value[key] && searchForm.value[key] !== 'all') {
      params[key] = searchForm.value[key]
    }
  })

  router.get(route('faculty.students.index'), params, {
    preserveState: true,
    preserveScroll: true,
  })
}

const debouncedSearch = debounce(() => {
  applyFilters()
}, 500)

const clearFilters = () => {
  searchForm.value = {
    search: '',
    course_id: 'all',
    academic_year: 'all',
    class_id: 'all',
  }

  router.get(route('faculty.students.index'), {}, {
    preserveState: true,
    preserveScroll: true,
  })
}

const getPaginationUrl = (page) => {
  const params = { ...searchForm.value, page }

  // Remove empty values and 'all' values
  Object.keys(params).forEach(key => {
    if (!params[key] || params[key] === 'all') {
      delete params[key]
    }
  })

  return route('faculty.students.index', params)
}

const getAverageGrade = (enrollments) => {
  const gradesWithValues = enrollments.filter(e => e.total_average)
  if (gradesWithValues.length === 0) return 'N/A'
  
  const sum = gradesWithValues.reduce((acc, e) => acc + e.total_average, 0)
  return Math.round(sum / gradesWithValues.length)
}

const getPassingCount = (enrollments) => {
  return enrollments.filter(e => e.total_average && e.total_average >= 75).length
}

const getStudentInitials = (student) => {
  if (!student) return '??'

  // Try full_name first
  if (student.full_name && typeof student.full_name === 'string') {
    const names = student.full_name.trim().split(' ').filter(n => n.length > 0)
    if (names.length > 0) {
      return names.map(n => n[0].toUpperCase()).join('').substring(0, 2)
    }
  }

  // Fallback to first_name and last_name
  const firstName = student.first_name || ''
  const lastName = student.last_name || ''

  if (firstName || lastName) {
    return (firstName[0] || '').toUpperCase() + (lastName[0] || '').toUpperCase()
  }

  // Final fallback
  return '??'
}
</script>
