<template>
  <div>
    <Sonner position="top-center" rich-colors close-button expand />

    <!-- Development Modal -->
    <DevelopmentModal
      :open="showDevelopmentModal"
      @update:open="showDevelopmentModal = $event"
    />

    <SidebarProvider>
      <!-- Mobile Top Bar -->
      <div class="fixed inset-x-0 top-0 z-40 h-14 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 border-b px-4 md:hidden flex items-center justify-between pt-[env(safe-area-inset-top)]">
        <Link :href="route('faculty.dashboard')" class="flex items-center gap-2">
          <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
            <Command class="size-4" />
          </div>
          <div class="grid text-left text-sm leading-tight">
            <span class="truncate font-semibold">Faculty Portal</span>
            <span class="truncate text-xs text-muted-foreground">Teaching Hub</span>
          </div>
        </Link>
        <div class="flex items-center gap-2">
          <NotificationDropdown ref="notificationDropdown" />
          <Button
            variant="ghost"
            size="icon"
            aria-label="Toggle theme"
            class="size-8"
            @click="toggleTheme"
          >
            <Sun v-if="isDark" class="h-4 w-4" />
            <Moon v-else class="h-4 w-4" />
            <span class="sr-only">Toggle theme</span>
          </Button>
          <SidebarTrigger class="size-8" aria-label="Open menu" />
        </div>
      </div>

      <Sidebar v-bind="sidebarProps">
        <SidebarHeader>
          <SidebarMenu>
            <SidebarMenuItem>
              <SidebarMenuButton size="lg" as-child>
                <Link :href="route('faculty.dashboard')">
                  <div
                    class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground"
                  >
                    <Command class="size-4" />
                  </div>
                  <div class="grid flex-1 text-left text-sm leading-tight">
                    <span class="truncate font-medium">Faculty Portal</span>
                    <span class="truncate text-xs">Teaching Hub</span>
                  </div>
                </Link>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
          <!-- Mobile-friendly sidebar content -->
          <div class="md:hidden">
            <!-- Profile summary -->
            <div class="flex items-center gap-3 px-3 py-4 border-b">
              <div class="flex aspect-square size-10 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                <Command class="size-5" />
              </div>
              <div class="min-w-0">
                <div class="font-semibold truncate">{{ facultyUser.name }}</div>
                <div class="text-xs text-muted-foreground truncate">{{ facultyUser.email }}</div>
              </div>
            </div>

            <!-- Quick Access -->
            <SidebarGroup>
              <SidebarGroupLabel>Quick Access</SidebarGroupLabel>
              <SidebarMenu>
                <SidebarMenuItem>
                  <SidebarMenuButton as-child size="lg">
                    <Link :href="route('faculty.dashboard')" preserve-scroll preserve-state :class="route().current('faculty.dashboard') ? 'text-primary' : ''">
                      <LayoutDashboard />
                      <span>Home</span>
                    </Link>
                  </SidebarMenuButton>
                </SidebarMenuItem>

                <SidebarMenuItem>
                  <SidebarMenuButton as-child size="lg">
                    <Link :href="route('faculty.classes.index')" preserve-scroll preserve-state :class="route().current('faculty.classes.*') ? 'text-primary' : ''">
                      <GraduationCap />
                      <span>My Classes</span>
                    </Link>
                  </SidebarMenuButton>
                </SidebarMenuItem>

                <SidebarMenuItem>
                  <SidebarMenuButton as-child size="lg">
                    <Link :href="route('faculty.students.index')" preserve-scroll preserve-state :class="route().current('faculty.students.*') ? 'text-primary' : ''">
                      <Users />
                      <span>Students</span>
                    </Link>
                  </SidebarMenuButton>
                </SidebarMenuItem>

                <SidebarMenuItem>
                  <SidebarMenuButton as-child size="lg">
                    <Link :href="route('faculty.schedule.index')" preserve-scroll preserve-state :class="route().current('faculty.schedule.*') ? 'text-primary' : ''">
                      <Calendar />
                      <span>Schedule</span>
                    </Link>
                  </SidebarMenuButton>
                </SidebarMenuItem>

                <SidebarMenuItem>
                  <SidebarMenuButton as-child size="lg">
                    <Link :href="route('faculty.attendance.index')" preserve-scroll preserve-state :class="route().current('faculty.attendance.*') ? 'text-primary' : ''">
                      <BookOpen />
                      <span>Attendance</span>
                    </Link>
                  </SidebarMenuButton>
                </SidebarMenuItem>

                <SidebarMenuItem>
                  <SidebarMenuButton as-child size="lg">
                    <Link :href="route('faculty.attendance.reports')" preserve-scroll preserve-state :class="route().current('faculty.attendance.reports') ? 'text-primary' : ''">
                      <FileText />
                      <span>Reports</span>
                    </Link>
                  </SidebarMenuButton>
                </SidebarMenuItem>

                <SidebarMenuItem>
                  <SidebarMenuButton as-child size="lg">
                    <Link :href="route('profile.show')" preserve-scroll preserve-state :class="route().current('profile.*') ? 'text-primary' : ''">
                      <Settings />
                      <span>Settings</span>
                    </Link>
                  </SidebarMenuButton>
                </SidebarMenuItem>
              </SidebarMenu>
            </SidebarGroup>

            <!-- Help -->
            <SidebarGroup>
              <SidebarGroupLabel>Help</SidebarGroupLabel>
              <SidebarMenu>
                <SidebarMenuItem>
                  <SidebarMenuButton as-child size="lg">
                    <a href="https://github.com/yukazakiri/DccpHubv2/issues" target="_blank" rel="noopener noreferrer">
                      <LifeBuoy />
                      <span>Support</span>
                    </a>
                  </SidebarMenuButton>
                </SidebarMenuItem>
              </SidebarMenu>
            </SidebarGroup>
          </div>

          <!-- Desktop sidebar content -->
          <div class="hidden md:block">
            <NavMain :items="sidebarData.navMain" />
            <NavSecondary :items="sidebarData.navSecondary" class="mt-auto" />
          </div>
        </SidebarContent>

        <SidebarFooter>
          <!-- Footer Navigation Menu -->
          <SidebarMenu class="mb-3">
            <SidebarMenuItem>
              <SidebarMenuButton as-child>
                <Link
                  :href="route('faculty.changelog')"
                  :class="[
                    'w-full justify-start text-sm',
                    route().current('faculty.changelog') ? 'bg-sidebar-accent text-sidebar-accent-foreground' : ''
                  ]"
                >
                  <ScrollText class="h-4 w-4" />
                  <span>Changelog</span>
                </Link>
              </SidebarMenuButton>
            </SidebarMenuItem>
            <SidebarMenuItem>
              <SidebarMenuButton as-child>
                <a
                  href="https://github.com/yukazakiri/DccpHubv2/issues"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="w-full justify-start text-sm"
                >
                  <LifeBuoy class="h-4 w-4" />
                  <span>Support</span>
                </a>
              </SidebarMenuButton>
            </SidebarMenuItem>
            <SidebarMenuItem>
              <SidebarMenuButton
                disabled
                class="w-full justify-start text-sm opacity-50 cursor-not-allowed"
              >
                <FileText class="h-4 w-4" />
                <span>Documentation</span>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </SidebarMenu>

          <!-- Separator -->
          <Separator class="mb-3" />

          <!-- Faculty User Profile -->
          <FacultyUserProfile :user="facultyUser" />
        </SidebarFooter>
      </Sidebar>

      <SidebarInset>
        <!-- Desktop Header -->
        <header class="hidden md:flex h-16 shrink-0 items-center gap-2 border-b px-4">
          <SidebarTrigger class="-ml-1" />
          <Separator orientation="vertical" class="mr-2 h-4" />
          <Breadcrumb>
            <BreadcrumbList>
              <BreadcrumbItem class="hidden md:block">
                <BreadcrumbLink :href="route('faculty.dashboard')">
                  Faculty Portal
                </BreadcrumbLink>
              </BreadcrumbItem>
              <BreadcrumbSeparator class="hidden md:block" />
              <BreadcrumbItem>
                <BreadcrumbPage>{{ $slots.header ? 'Faculty' : 'Dashboard' }}</BreadcrumbPage>
              </BreadcrumbItem>
            </BreadcrumbList>
          </Breadcrumb>
          <div class="ml-auto flex items-center gap-2">
            <SemesterSchoolYearSelector />
            <Button
              variant="ghost"
              size="icon"
              aria-label="Toggle theme"
              class="size-8"
              @click="toggleTheme"
            >
              <Sun v-if="isDark" class="h-4 w-4" />
              <Moon v-else class="h-4 w-4" />
              <span class="sr-only">Toggle theme</span>
            </Button>
            <NotificationDropdown ref="notificationDropdown" />
          </div>
        </header>

        <div class="flex flex-1 flex-col gap-4 p-4 relative mt-[calc(56px+env(safe-area-inset-top))] md:mt-0 pb-[calc(56px+env(safe-area-inset-bottom))] md:pb-0">
          <div class="flex-1 rounded-xl bg-muted/50 relative">
            <!-- Loading Screen for Content Area Only -->
            <LoadingScreen
              :show="isLoading"
              :title="loadingTitle"
              :description="loadingDescription"
              :show-progress="showLoadingProgress"
              :progress="loadingProgress"
              :content-only="true"
            />

            <main class="p-4 sm:p-6">
              <slot />
            </main>
          </div>
        </div>

        <!-- Mobile Bottom Navigation -->
        <nav class="fixed inset-x-0 bottom-0 z-40 border-t bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 md:hidden pb-[env(safe-area-inset-bottom)]">
          <div class="grid grid-cols-4">
            <Link :href="route('faculty.dashboard')" class="flex flex-col items-center justify-center py-2 text-xs" :class="route().current('faculty.dashboard') ? 'text-primary' : 'text-muted-foreground'">
              <LayoutDashboard class="h-5 w-5" />
              <span>Home</span>
            </Link>
            <Link :href="route('faculty.classes.index')" class="flex flex-col items-center justify-center py-2 text-xs" :class="route().current('faculty.classes.*') ? 'text-primary' : 'text-muted-foreground'">
              <GraduationCap class="h-5 w-5" />
              <span>Classes</span>
            </Link>
            <Link :href="route('faculty.students.index')" class="flex flex-col items-center justify-center py-2 text-xs" :class="route().current('faculty.students.*') ? 'text-primary' : 'text-muted-foreground'">
              <Users class="h-5 w-5" />
              <span>Students</span>
            </Link>
            <Link :href="route('faculty.schedule.index')" class="flex flex-col items-center justify-center py-2 text-xs" :class="route().current('faculty.schedule.*') ? 'text-primary' : 'text-muted-foreground'">
              <Calendar class="h-5 w-5" />
              <span>Schedule</span>
            </Link>
          </div>
        </nav>
      </SidebarInset>
    </SidebarProvider>
  </div>
</template>

<script setup>
import FacultyUserProfile from "@/Components/Faculty/FacultyUserProfile.vue"
import NotificationDropdown from '@/Components/Notifications/NotificationDropdown.vue'
import SemesterSchoolYearSelector from "@/Components/SemesterSchoolYearSelector.vue"
import NavMain from "@/Components/shadcn/NavMain.vue"
import NavSecondary from "@/Components/shadcn/NavSecondary.vue"
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/Components/shadcn/ui/breadcrumb"
import Button from "@/Components/shadcn/ui/button/Button.vue"
import { Separator } from "@/Components/shadcn/ui/separator"
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarInset,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarProvider,
  SidebarTrigger
} from "@/Components/shadcn/ui/sidebar"
import Sonner from "@/Components/shadcn/ui/sonner/Sonner.vue"
import DevelopmentModal from '@/Components/ui/DevelopmentModal.vue'
import LoadingScreen from '@/Components/ui/LoadingScreen.vue'
import { useTheme } from '@/composables/useTheme.js'
import { Link, router, usePage } from '@inertiajs/vue3'
import {
  BookOpen,
  Calendar,
  Command,
  FileText,
  GraduationCap,
  LayoutDashboard,
  LifeBuoy,
  Moon,
  Settings,
  Sun,
  Users
} from "lucide-vue-next"
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { toast } from "vue-sonner"

// Reactive state
const showDevelopmentModal = ref(false)
const notificationDropdown = ref(null)

// Loading state management
const isLoading = ref(false)
const loadingTitle = ref('Loading...')
const loadingDescription = ref('Please wait while we load your content.')
const showLoadingProgress = ref(false)
const loadingProgress = ref(0)
let loadingStartTime = null

// Page and user data
const page = usePage();

// Route helper function
const route = window.route;

// Sidebar props to match AppSidebar
const sidebarProps = {
  side: "left",
  variant: "inset",
  collapsible: "icon",
  class: null,
};

// Theme
const { isDark, toggleTheme } = useTheme()

// Faculty user data for the footer
const facultyUser = computed(() => ({
  name: page.props.auth?.user?.name || "Faculty User",
  email: page.props.auth?.user?.email || "faculty@example.com",
  avatar: "/avatars/faculty.jpg",
}));

// Enhanced sidebar data structure
const sidebarData = {
  navMain: [
    {
      title: "Dashboard",
      url: route("faculty.dashboard"),
      icon: LayoutDashboard,
      isActive: route().current("faculty.dashboard"),
    },
    {
      title: "Teaching",
      url: "#",
      icon: GraduationCap,
      items: [
        {
          title: "My Classes",
          url: route("faculty.classes.index"),
        },
        {
          title: "Students",
          url: route("faculty.students.index"),
        },
        {
          title: "Schedule",
          url: route("faculty.schedule.index"),
        },
      ],
    },
    {
      title: "Academic Tools",
      url: "#",
      icon: BookOpen,
      items: [
        {
          title: "Attendance",
          url: route("faculty.attendance.index"),
        },
        {
          title: "Reports",
          url: route("faculty.attendance.reports"),
        },
        {
          title: "Analytics",
          url: route("faculty.attendance.analytics"),
        },
        {
          title: "Grades",
          url: "#",
          disabled: true,
        },
        {
          title: "Assignments",
          url: "#",
          disabled: true,
        },
        {
          title: "Resources",
          url: "#",
          disabled: true,
        },
      ],
    },
    {
      title: "Settings",
      url: route("profile.show"),
      icon: Settings,
      isActive: route().current("profile.*"),
    },
  ],
  navSecondary: [
    // Footer items moved to SidebarFooter
  ],
};



// Handle flash messages
const handleFlashMessages = () => {
    // Safely check if flash exists in page props
    if (!page.props.flash) return;

    try {
        if (page.props.flash.success) {
            toast.success(page.props.flash.success);
        }

        if (page.props.flash.error) {
            toast.error(page.props.flash.error);
        }

        if (page.props.flash.message) {
            toast.info(page.props.flash.message);
        }
    } catch (error) {
        console.error('Error displaying toast notification:', error);
    }
};

// Setup Inertia.js progress events for loading screen
const setupInertiaProgress = () => {
  try {
    // Show loading screen when navigation starts
    router.on('start', () => {
      loadingStartTime = Date.now()
      isLoading.value = true
      loadingTitle.value = 'Loading...'
      loadingDescription.value = 'Navigating to your page.'
      showLoadingProgress.value = true
      loadingProgress.value = 0
    })

    // Update progress during navigation
    router.on('progress', (event) => {
      if (event.detail && event.detail.progress) {
        loadingProgress.value = Math.round(event.detail.progress.percentage || 0)
      }
    })

    // Hide loading screen when navigation finishes successfully
    router.on('finish', () => {
      const loadingDuration = Date.now() - loadingStartTime
      const minLoadingTime = 200 // Minimum 200ms to prevent flashing
      const remainingTime = Math.max(0, minLoadingTime - loadingDuration)

      setTimeout(() => {
        isLoading.value = false
        loadingProgress.value = 100
      }, remainingTime)
    })

    // Handle navigation errors
    router.on('error', () => {
      setTimeout(() => {
        isLoading.value = false
        loadingTitle.value = 'Error'
        loadingDescription.value = 'Something went wrong while loading the page.'
      }, 100)
    })

    // Handle navigation cancellation (if supported)
    if (typeof router.on === 'function') {
      router.on('cancel', () => {
        isLoading.value = false
        loadingProgress.value = 0
      })
    }
  } catch (error) {
    console.warn('Failed to setup Inertia progress tracking:', error)
    // Fallback: just hide loading after a short delay
    setTimeout(() => {
      isLoading.value = false
    }, 1000)
  }
}

onMounted(() => {
    // Handle flash messages on initial load
    handleFlashMessages();

    // Setup Inertia.js progress tracking
    setupInertiaProgress();
});

onBeforeUnmount(() => {
    // Reset loading state on component unmount
    isLoading.value = false
    loadingProgress.value = 0
});

// Watch for flash messages when page changes
watch(() => page.props.flash, () => {
    handleFlashMessages();
}, { deep: true });


</script>
