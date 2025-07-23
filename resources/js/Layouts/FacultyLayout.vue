<template>
  <div class="min-h-screen bg-background">
    <!-- Mobile sidebar overlay -->
    <div v-if="sidebarOpen" class="fixed inset-0 z-40 md:hidden">
      <div class="fixed inset-0 bg-black/50" @click="sidebarOpen = false"></div>
    </div>

    <!-- Mobile sidebar -->
    <div
      :class="[
        'fixed inset-y-0 left-0 z-50 w-72 bg-background border-r border-border transform transition-transform duration-300 ease-in-out md:hidden',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full'
      ]"
    >
      <div class="flex items-center justify-between h-16 px-4 border-b border-border">
        <div class="flex items-center space-x-2">
          <ApplicationLogo class="h-8 w-auto" />
          <span class="text-lg font-semibold text-foreground">Faculty Portal</span>
        </div>
        <Button variant="ghost" size="icon" @click="sidebarOpen = false">
          <XMarkIcon class="h-5 w-5" />
        </Button>
      </div>
      <nav class="mt-4 px-2">
        <SidebarNavigation :navigation="navigation" />
      </nav>
    </div>

    <!-- Desktop sidebar -->
    <div class="hidden md:fixed md:inset-y-0 md:flex md:w-64 md:flex-col">
      <div class="flex flex-col flex-grow bg-background border-r border-border">
        <div class="flex items-center h-16 px-4 border-b border-border">
          <ApplicationLogo class="h-8 w-auto" />
          <span class="ml-2 text-lg font-semibold text-foreground">Faculty Portal</span>
        </div>
        <nav class="mt-4 flex-1 px-2">
          <SidebarNavigation :navigation="navigation" />
        </nav>

        <!-- User profile section -->
        <div class="flex-shrink-0 border-t border-border p-4">
          <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
              <img
                v-if="$page.props.auth.user.profile_photo_url"
                :src="$page.props.auth.user.profile_photo_url"
                :alt="$page.props.auth.user.name"
                class="h-10 w-10 rounded-full object-cover"
              />
              <div
                v-else
                class="h-10 w-10 rounded-full bg-primary flex items-center justify-center"
              >
                <span class="text-sm font-medium text-primary-foreground">
                  {{ $page.props.auth.user.name.charAt(0) }}
                </span>
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-foreground truncate">{{ $page.props.auth.user.name }}</p>
              <p class="text-xs text-muted-foreground">Faculty Member</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <div class="md:pl-64 flex flex-col flex-1">
      <!-- Top navigation -->
      <div class="sticky top-0 z-10 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 border-b border-border">
        <div class="flex items-center justify-between h-16 px-4 sm:px-6">
          <div class="flex items-center space-x-4">
            <Button
              variant="ghost"
              size="icon"
              @click="sidebarOpen = true"
              class="md:hidden"
            >
              <Bars3Icon class="h-5 w-5" />
            </Button>

            <div class="hidden sm:block">
              <h1 v-if="$slots.header" class="text-lg font-semibold text-foreground">
                <slot name="header" />
              </h1>
            </div>
          </div>

          <div class="flex items-center space-x-2">
            <!-- Academic Period Selector -->
            <div class="hidden md:flex items-center space-x-2">
              <div class="flex items-center space-x-1 px-3 py-1.5 bg-muted rounded-md">
                <CalendarIcon class="h-4 w-4 text-muted-foreground" />
                <DropdownMenu>
                  <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="sm" class="h-auto p-1 text-xs font-medium">
                      {{ currentSemesterName }} {{ currentSchoolYearString }}
                      <ChevronDownIcon class="h-3 w-3 ml-1" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end" class="w-64">
                    <DropdownMenuLabel>Academic Period</DropdownMenuLabel>
                    <DropdownMenuSeparator />

                    <!-- Semester Selection -->
                    <div class="px-2 py-1">
                      <label class="text-xs font-medium text-muted-foreground">Semester</label>
                      <div class="mt-1 space-y-1">
                        <button
                          v-for="(name, value) in availableSemesters"
                          :key="value"
                          @click="updateSemester(parseInt(value))"
                          :class="[
                            'w-full text-left px-2 py-1 text-sm rounded hover:bg-accent',
                            currentSemester === parseInt(value) ? 'bg-accent font-medium' : ''
                          ]"
                        >
                          {{ name }}
                        </button>
                      </div>
                    </div>

                    <DropdownMenuSeparator />

                    <!-- School Year Selection -->
                    <div class="px-2 py-1">
                      <label class="text-xs font-medium text-muted-foreground">School Year</label>
                      <div class="mt-1 max-h-32 overflow-y-auto space-y-1">
                        <button
                          v-for="(name, value) in availableSchoolYears"
                          :key="value"
                          @click="updateSchoolYear(parseInt(value))"
                          :class="[
                            'w-full text-left px-2 py-1 text-sm rounded hover:bg-accent',
                            currentSchoolYear === parseInt(value) ? 'bg-accent font-medium' : ''
                          ]"
                        >
                          {{ name }}
                        </button>
                      </div>
                    </div>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            </div>

            <!-- Theme toggle -->
            <ThemeToggle />

            <!-- Notifications -->
            <Button variant="ghost" size="icon" class="relative">
              <BellIcon class="h-5 w-5" />
              <Badge variant="destructive" class="absolute -top-1 -right-1 h-5 w-5 p-0 text-xs">
                3
              </Badge>
            </Button>

            <!-- Quick actions dropdown (desktop only) -->
            <div class="relative hidden sm:block">
              <Button
                variant="ghost"
                size="icon"
                @click="quickActionsOpen = !quickActionsOpen"
              >
                <PlusIcon class="h-5 w-5" />
              </Button>

              <div
                v-if="quickActionsOpen"
                class="absolute right-0 mt-2 w-56 bg-popover border border-border rounded-md shadow-lg py-1 z-50"
                @click.away="quickActionsOpen = false"
              >
                <button
                  v-for="action in quickActions"
                  :key="action.name"
                  @click="action.action"
                  class="flex items-center w-full px-4 py-2 text-sm text-foreground hover:bg-accent hover:text-accent-foreground"
                >
                  <component :is="action.icon" class="h-4 w-4 mr-3 text-muted-foreground" />
                  {{ action.name }}
                </button>
              </div>
            </div>

            <!-- Profile dropdown (desktop only) -->
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button variant="ghost" class="hidden sm:flex items-center space-x-2 h-9">
                  <img
                    v-if="$page.props.auth.user.profile_photo_url"
                    :src="$page.props.auth.user.profile_photo_url"
                    :alt="$page.props.auth.user.name"
                    class="h-7 w-7 rounded-full object-cover"
                  />
                  <div
                    v-else
                    class="h-7 w-7 rounded-full bg-primary flex items-center justify-center"
                  >
                    <span class="text-xs font-medium text-primary-foreground">
                      {{ $page.props.auth.user.name.charAt(0) }}
                    </span>
                  </div>
                  <ChevronDownIcon class="h-4 w-4 text-muted-foreground" />
                </Button>
              </DropdownMenuTrigger>

              <DropdownMenuContent align="end" class="w-48">
                <DropdownMenuLabel>
                  Manage Account
                </DropdownMenuLabel>

                <DropdownMenuSeparator />

                <DropdownMenuItem as-child>
                  <Link :href="route('profile.show')" class="cursor-pointer">
                    Profile
                  </Link>
                </DropdownMenuItem>

                <DropdownMenuSeparator />

                <DropdownMenuItem @click="logout" class="cursor-pointer">
                  Log Out
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>
      </div>

      <!-- Page content -->
      <main class="flex-1 pb-20 md:pb-6">
        <div class="py-4 md:py-6">
          <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <slot />
          </div>
        </div>
      </main>

      <!-- Mobile bottom navigation -->
      <MobileBottomNav />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import axios from 'axios'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import { Button } from '@/Components/ui/button.js'
import { Badge } from '@/Components/ui/badge.js'
import ThemeToggle from '@/Components/ui/theme-toggle.vue'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/shadcn/ui/dropdown-menu'
import SidebarNavigation from '@/Components/Faculty/SidebarNavigation.vue'
import MobileBottomNav from '@/Components/Faculty/MobileBottomNav.vue'
import {
  Bars3Icon,
  XMarkIcon,
  BellIcon,
  PlusIcon,
  ChevronDownIcon,
  HomeIcon,
  AcademicCapIcon,
  UsersIcon,
  CalendarIcon,
  ClipboardDocumentListIcon,
  ChartBarIcon,
  DocumentTextIcon,
  CogIcon,
  BookOpenIcon
} from '@heroicons/vue/24/outline'

// Reactive state
const sidebarOpen = ref(false)
const quickActionsOpen = ref(false)

// Academic period settings
const currentSemester = ref(1)
const currentSchoolYear = ref(new Date().getFullYear())
const availableSemesters = ref({})
const availableSchoolYears = ref({})
const loading = ref(false)

// Navigation items
const navigation = ref([
  { name: 'Dashboard', href: route('faculty.dashboard'), icon: HomeIcon, current: route().current('faculty.dashboard') },
  { name: 'My Classes', href: route('faculty.classes.index'), icon: AcademicCapIcon, current: route().current('faculty.classes.*') },
  { name: 'Students', href: '#', icon: UsersIcon, current: false },
  { name: 'Schedule', href: '#', icon: CalendarIcon, current: false },
  { name: 'Attendance', href: '#', icon: ClipboardDocumentListIcon, current: false },
  { name: 'Grades', href: '#', icon: ChartBarIcon, current: false },
  { name: 'Assignments', href: '#', icon: DocumentTextIcon, current: false },
  { name: 'Resources', href: '#', icon: BookOpenIcon, current: false },
  { name: 'Settings', href: '#', icon: CogIcon, current: false },
])

// Quick actions
const quickActions = ref([
  { name: 'Take Attendance', href: '#', icon: ClipboardDocumentListIcon },
  { name: 'Create Assignment', href: '#', icon: DocumentTextIcon },
  { name: 'Grade Students', href: '#', icon: ChartBarIcon },
  { name: 'Schedule Class', href: '#', icon: CalendarIcon },
])

// Computed properties
const currentSemesterName = computed(() => {
  return availableSemesters.value[currentSemester.value] || '1st Semester'
})

const currentSchoolYearString = computed(() => {
  return availableSchoolYears.value[currentSchoolYear.value] || `${currentSchoolYear.value} - ${currentSchoolYear.value + 1}`
})

// Methods
const loadSettings = async () => {
  try {
    loading.value = true
    const response = await axios.get(route('faculty.settings.index'))

    if (response.data.success) {
      const data = response.data.data
      currentSemester.value = data.current_semester
      currentSchoolYear.value = data.current_school_year
      availableSemesters.value = data.available_semesters
      availableSchoolYears.value = data.available_school_years
    }
  } catch (error) {
    console.error('Failed to load settings:', error)
  } finally {
    loading.value = false
  }
}

const updateSemester = async (semester) => {
  try {
    loading.value = true
    const response = await axios.patch(route('faculty.settings.semester'), {
      semester: semester
    })

    if (response.data.success) {
      currentSemester.value = semester
      // Refresh the current page to reload data with new semester
      router.reload({ only: ['stats', 'classes', 'todaysSchedule', 'weeklySchedule', 'classEnrollments'] })
    }
  } catch (error) {
    console.error('Failed to update semester:', error)
  } finally {
    loading.value = false
  }
}

const updateSchoolYear = async (schoolYear) => {
  try {
    loading.value = true
    const response = await axios.patch(route('faculty.settings.school-year'), {
      school_year: schoolYear
    })

    if (response.data.success) {
      currentSchoolYear.value = schoolYear
      // Refresh the current page to reload data with new school year
      router.reload({ only: ['stats', 'classes', 'todaysSchedule', 'weeklySchedule', 'classEnrollments'] })
    }
  } catch (error) {
    console.error('Failed to update school year:', error)
  } finally {
    loading.value = false
  }
}

const logout = () => {
  router.post(route('logout'))
}

// Load settings on component mount
onMounted(() => {
  loadSettings()
})
</script>
