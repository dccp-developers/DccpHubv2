<template>
  <div>
    <Sonner position="top-center" rich-colors close-button expand />

    <!-- Mobile Top Navigation Bar -->
    <div
      class="fixed top-0 left-0 right-0 h-14 border-b bg-background z-50 md:hidden flex items-center justify-between px-4"
    >
      <div class="flex items-center space-x-2">
        <ApplicationLogo class="w-7 h-7" />
        <span class="font-bold">Faculty Portal</span>
      </div>

      <div class="flex items-center gap-2">
        <SemesterSchoolYearSelector />
        <div class="font-medium text-sm">{{ $slots.header ? 'Faculty' : 'Dashboard' }}</div>
      </div>

      <div class="flex items-center gap-2">
        <!-- Notifications for authenticated users -->
        <NotificationDropdown ref="notificationDropdown" class="md:hidden" />

        <Sheet v-model:open="isMobileMenuOpen">
          <SheetTrigger asChild>
            <Button variant="ghost" size="icon" class="md:hidden">
              <Icon icon="lucide:menu" class="h-5 w-5" />
            </Button>
          </SheetTrigger>
          <SheetContent side="right" class="w-[85vw] max-w-sm p-0">
            <!-- Header with user profile -->
            <div class="border-b p-4 bg-secondary/30">
              <FacultyUserProfile />
            </div>

            <!-- Main menu content -->
            <div class="overflow-y-auto h-[calc(100vh-180px)]">
              <!-- Quick Actions Grid -->
              <div class="p-4 border-b">
                <h3 class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-3">
                  Quick Actions
                </h3>
                <div class="grid grid-cols-2 gap-2">
                  <component
                    v-for="item in mobileNavConfig.quickActions"
                    :key="item.name"
                    :is="item.disabled ? 'div' : Link"
                    v-bind="item.disabled ? { 'aria-disabled': true, tabindex: -1 } : { href: route(item.route) }"
                    class="flex flex-col items-center p-4 rounded-lg transition-colors"
                    :class="[
                      route().current(item.route)
                        ? 'bg-secondary text-primary'
                        : 'hover:bg-secondary/80',
                      item.disabled ? 'opacity-50 pointer-events-none cursor-not-allowed' : ''
                    ]"
                  >
                    <Icon :icon="item.icon" class="h-6 w-6 mb-2" />
                    <span class="text-sm font-medium">{{ item.name }}</span>
                  </component>
                </div>
              </div>

              <!-- Main Menu List -->
              <div class="p-4">
                <h3 class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-3">
                  Menu
                </h3>
                <div class="space-y-1">
                  <component
                    v-for="item in mobileNavConfig.mainMenu"
                    :key="item.name"
                    v-bind="renderLink(item)"
                    :is="item.disabled ? 'button' : (item.external ? 'a' : Link)"
                    class="flex items-center w-full"
                    :class="[
                      'flex items-center px-4 py-3 text-sm font-medium rounded-md transition-colors',
                      !item.external && route().current(item.route)
                        ? 'bg-secondary text-primary'
                        : 'hover:bg-secondary/50',
                      item.disabled ? 'opacity-50 pointer-events-none cursor-not-allowed' : ''
                    ]"
                    @click="item.disabled && $event.preventDefault()"
                  >
                    <Icon :icon="item.icon" class="mr-3 h-5 w-5" />
                    {{ item.name }}
                  </component>

                  <!-- Theme Toggle -->
                  <button
                    class="w-full flex items-center px-4 py-3 text-sm font-medium rounded-md hover:bg-secondary/50 transition-colors"
                    @click="mode = isDarkMode ? 'light' : 'dark'"
                  >
                    <Icon :icon="isDarkMode ? 'lucide:moon' : 'lucide:sun'" class="mr-3 h-5 w-5" />
                    {{ isDarkMode ? "Dark" : "Light" }} Mode
                  </button>

                  <!-- Test Notification Button (Development) -->
                  <button
                    v-if="$page.props.app?.env === 'local'"
                    class="w-full flex items-center px-4 py-3 text-sm font-medium rounded-md hover:bg-secondary/50 transition-colors"
                    @click="sendTestNotificationHandler"
                  >
                    <Icon icon="lucide:bell" class="mr-3 h-5 w-5" />
                    Test Notification
                  </button>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="border-t p-4 bg-background/80 backdrop-blur-sm">
              <div class="flex items-center justify-between text-sm text-muted-foreground">
                <span>&copy; {{ new Date().getFullYear() }} Faculty Portal</span>
                <div class="flex items-center gap-2">
                  <Icon icon="logos:laravel" class="h-4 w-4" />
                  <Icon icon="logos:vue" class="h-4 w-4" />
                </div>
              </div>
            </div>
          </SheetContent>
        </Sheet>
      </div>
    </div>

    <SidebarProvider>
      <Sidebar variant="inset">
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
          <NavMain :items="sidebarData.navMain" />
          <NavSecondary :items="sidebarData.navSecondary" class="mt-auto" />
        </SidebarContent>

        <SidebarFooter>
          <SidebarMenu>
            <SidebarMenuItem>
              <FacultyUserProfile />
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarFooter>
      </Sidebar>

      <SidebarInset>
        <header class="flex h-16 shrink-0 items-center gap-2 hidden md:flex">
          <div class="flex items-center gap-2 px-4">
            <SidebarTrigger class="-ml-1" />
            <Separator
              orientation="vertical"
              class="mr-2 data-[orientation=vertical]:h-4"
            />
            <Breadcrumb>
              <BreadcrumbList>
                <BreadcrumbItem class="hidden md:block">
                  <BreadcrumbLink :href="route('faculty.dashboard')">
                    Faculty Portal
                  </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator class="hidden md:block" />
                <BreadcrumbItem>
                  <BreadcrumbPage>
                    <slot name="header">Dashboard</slot>
                  </BreadcrumbPage>
                </BreadcrumbItem>
              </BreadcrumbList>
            </Breadcrumb>
          </div>
          <div class="ml-auto flex items-center gap-2 px-4">
            <!-- Notifications for authenticated users -->
            <NotificationDropdown ref="notificationDropdown" class="hidden md:block" />
            <SemesterSchoolYearSelector />

            <!-- Test Notification Button (Development) -->
            <Button
              v-if="$page.props.app?.env === 'local'"
              variant="ghost"
              size="icon"
              @click="sendTestNotificationHandler"
              title="Send Test Notification"
            >
              <Icon icon="lucide:beaker" class="h-5 w-5" />
            </Button>
          </div>
        </header>

        <main class="flex flex-1 flex-col gap-4 p-4 pt-0 mt-14 md:mt-0">
          <slot />
        </main>

        <!-- Compact Footer -->
        <footer class="py-3 px-4 border-t mb-14 md:mb-0">
          <div class="container mx-auto flex flex-wrap items-center justify-between gap-y-4">
            <!-- Copyright -->
            <div class="text-center md:text-left">
              <p class="text-sm text-muted-foreground">
                &copy; {{ new Date().getFullYear() }} Faculty Portal. All rights reserved.
              </p>
            </div>

            <!-- Social Links -->
            <div class="flex items-center gap-3">
              <Link
                href="https://github.com/yukazakiri"
                class="text-muted-foreground hover:text-primary transition-colors"
                target="_blank"
              >
                <Icon icon="lucide:github" class="h-5 w-5" />
              </Link>
              <Link
                href="#"
                class="text-muted-foreground hover:text-primary transition-colors"
                target="_blank"
              >
                <Icon icon="lucide:twitter" class="h-5 w-5" />
              </Link>
            </div>

            <!-- Tech Stack -->
            <div class="flex items-center gap-2 text-muted-foreground text-sm">
              <Icon icon="logos:laravel" class="h-4 w-4" />
              <Icon icon="logos:vue" class="h-4 w-4" />
              <Icon icon="logos:inertiajs-icon" class="h-4 w-4" />
              <Icon icon="logos:tailwindcss-icon" class="h-4 w-4" />
              <Icon icon="logos:vitejs" class="h-4 w-4" />
            </div>
          </div>
        </footer>
      </SidebarInset>
    </SidebarProvider>

    <!-- Redesigned Mobile Bottom Navigation -->
    <div class="fixed bottom-0 left-0 right-0 border-t bg-background/80 backdrop-blur-sm z-50 md:hidden">
      <div class="grid grid-cols-5 px-1 py-1">
        <Link
          v-for="item in mobileNavConfig.quickActions.slice(0, 4)"
          :key="item.name"
          :href="route(item.route)"
          class="flex flex-col items-center py-2 px-1 rounded-lg transition-colors"
          :class="
            route().current(item.route)
              ? 'text-primary bg-secondary/50'
              : 'text-muted-foreground hover:bg-secondary/30'
          "
          prefetch
        >
          <Icon :icon="item.icon" class="h-5 w-5 mb-1" />
          <span class="text-xs">{{ item.name }}</span>
        </Link>
        <button
          class="flex flex-col items-center py-2 px-1 text-muted-foreground rounded-lg hover:bg-secondary/30 transition-colors"
          @click="isMobileMenuOpen = true"
        >
          <Icon icon="lucide:menu" class="h-5 w-5 mb-1" />
          <span class="text-xs">Menu</span>
        </button>
      </div>
    </div>

    <!-- Development Modal -->
    <DevelopmentModal
      :open="showDevelopmentModal"
      @update:open="showDevelopmentModal = $event"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, inject, watch } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import { Button } from '@/Components/shadcn/ui/button'
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/Components/shadcn/ui/breadcrumb";
import { Separator } from "@/Components/shadcn/ui/separator";
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarInset,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarProvider,
  SidebarTrigger,
} from "@/Components/shadcn/ui/sidebar";
import {
  Sheet,
  SheetContent,
  SheetTrigger,
} from "@/Components/shadcn/ui/sheet";
import Sonner from "@/Components/shadcn/ui/sonner/Sonner.vue";
import NotificationDropdown from '@/Components/Notifications/NotificationDropdown.vue'
import { useRealtimeNotifications } from '@/Composables/useRealtimeNotifications'
import { useNotifications } from '@/Composables/useNotifications'
import { toast } from "vue-sonner";
import NavMain from "@/Components/shadcn/NavMain.vue";
import NavSecondary from "@/Components/shadcn/NavSecondary.vue";
import DevelopmentModal from '@/Components/ui/DevelopmentModal.vue'
import SemesterSchoolYearSelector from "@/Components/SemesterSchoolYearSelector.vue";
import FacultyUserProfile from "@/Components/Faculty/FacultyUserProfile.vue";
import { useColorMode } from "@vueuse/core";
import { Icon } from "@iconify/vue";
import {
  LayoutDashboard,
  Users,
  Calendar,
  GraduationCap,
  ClipboardList,
  BarChart,
  FileText,
  BookOpen,
  Settings,
  LifeBuoy,
  Command,
} from "lucide-vue-next";

// Reactive state
const isMobileMenuOpen = ref(false)
const showDevelopmentModal = ref(false)
const notificationDropdown = ref(null)

// Page and user data
const page = usePage();
const user = computed(() => page.props.auth?.user);

// Notification composables
const { sendTestNotification } = useNotifications()

// Initialize real-time notifications
useRealtimeNotifications()

// Route helper function
const route = inject("route");

// Theme management
const mode = useColorMode({
    attribute: "class",
    modes: { light: "", dark: "dark" },
    initialValue: "light",
});
const isDarkMode = computed(() => mode.value === "dark");

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
    {
      title: "Support",
      url: "https://github.com/yukazakiri/DccpHubv2/issues",
      icon: LifeBuoy,
    },
    {
      title: "Documentation",
      url: "#",
      disabled: true,
      icon: FileText,
    },
  ],
};

// Mobile navigation configuration
const mobileNavConfig = computed(() => ({
    quickActions: [
        {
            name: "Dashboard",
            icon: "lucide:layout-dashboard",
            route: "faculty.dashboard",
        },
        {
            name: "Classes",
            icon: "lucide:graduation-cap",
            route: "faculty.classes.index",
        },
        {
            name: "Attendance",
            icon: "lucide:clipboard-list",
            route: "faculty.attendance.index",
        },
        {
            name: "Schedule",
            icon: "lucide:calendar",
            route: "faculty.schedule.index",
        },
    ],
    mainMenu: [
        {
            name: "Attendance Reports",
            icon: "lucide:file-bar-chart",
            route: "faculty.attendance.reports",
        },
        {
            name: "Settings",
            icon: "lucide:settings",
            route: "profile.show",
        },
        {
            name: "Support",
            icon: "lucide:life-buoy",
            href: "https://github.com/yukazakiri/DccpHubv2/issues",
            external: true,
        },
        {
            name: "Documentation",
            icon: "lucide:file-text",
            route: "#",
            disabled: true,
        },
    ]
}));

// Helper functions
function renderLink(item) {
    if (item.external) {
        return {
            is: "a",
            href: item.href || route(item.route),
            target: "_blank",
        };
    }
    return {
        is: Link,
        href: route(item.route),
    };
}

function logout() {
    router.post(route("logout"));
}

// Send test notification
const sendTestNotificationHandler = async () => {
    try {
        await sendTestNotification();
        // Refresh the notification dropdown
        if (notificationDropdown.value) {
            notificationDropdown.value.refresh();
        }
    } catch (error) {
        console.error('Failed to send test notification:', error);
        toast.error('Failed to send test notification');
    }
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

onMounted(() => {
    // Handle flash messages on initial load
    handleFlashMessages();
});

// Watch for flash messages when page changes
watch(() => page.props.flash, () => {
    handleFlashMessages();
}, { deep: true });


</script>
