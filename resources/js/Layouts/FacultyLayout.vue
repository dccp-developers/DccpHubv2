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
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
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

// Navigation items
const navigation = ref([
  { name: 'Dashboard', href: route('faculty.dashboard'), icon: HomeIcon, current: route().current('faculty.dashboard') },
  { name: 'My Classes', href: '#', icon: AcademicCapIcon, current: false },
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

// Methods
const logout = () => {
  router.post(route('logout'))
}
</script>
