<script setup>
import AppSidebarContent from "@/Components/AppSidebarContent.vue";
import AppTeamManager from "@/Components/AppTeamManager.vue";
import AppUserManager from "@/Components/AppUserManager.vue";
import Breadcrumb from "@/Components/shadcn/ui/breadcrumb/Breadcrumb.vue";
import BreadcrumbItem from "@/Components/shadcn/ui/breadcrumb/BreadcrumbItem.vue";
import BreadcrumbLink from "@/Components/shadcn/ui/breadcrumb/BreadcrumbLink.vue";
import BreadcrumbList from "@/Components/shadcn/ui/breadcrumb/BreadcrumbList.vue";
import Separator from "@/Components/shadcn/ui/separator/Separator.vue";
import {
    Sidebar,
    SidebarFooter,
    SidebarHeader,
    SidebarInset,
} from "@/Components/shadcn/ui/sidebar";
import SidebarMenu from "@/Components/shadcn/ui/sidebar/SidebarMenu.vue";
import SidebarMenuItem from "@/Components/shadcn/ui/sidebar/SidebarMenuItem.vue";
import SidebarProvider from "@/Components/shadcn/ui/sidebar/SidebarProvider.vue";
import SidebarTrigger from "@/Components/shadcn/ui/sidebar/SidebarTrigger.vue";
import Sonner from "@/Components/shadcn/ui/sonner/Sonner.vue";
import NotificationDropdown from "@/Components/Notifications/NotificationDropdown.vue";
import { useSeoMetaTags } from "@/Composables/useSeoMetaTags.js";
import { useRealtimeNotifications } from "@/Composables/useRealtimeNotifications";
import { useNotifications } from "@/Composables/useNotifications";
import { onMounted, ref, computed, inject, watch } from "vue";
import { Icon } from "@iconify/vue";
import { Link, usePage } from "@inertiajs/vue3";
import { Button } from "@/Components/shadcn/ui/button";
import {
    Sheet,
    SheetContent,
    SheetTrigger,
} from "@/Components/shadcn/ui/sheet";
import { useColorMode } from "@vueuse/core";
import AppLogoIcon from "@/Components/AppLogoIcon.vue";
import SemesterSchoolYearSelector from "@/Components/SemesterSchoolYearSelector.vue";
import { toast } from "vue-sonner";

const props = defineProps({
    title: String,
});

const page = usePage();

useSeoMetaTags({
    title: props.title,
});

// Initialize notification system for authenticated users
const user = computed(() => page.props.auth?.user);
if (user.value) {
    // Initialize real-time notifications
    useRealtimeNotifications();
}

// Notification composables
const { sendTestNotification } = useNotifications();
const notificationDropdown = ref(null);

// Page load time
const loadTime = ref(null);

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

        // Handle toast object for more complex notifications if needed
        if (page.props.flash.toast) {
            const { title, description, type = 'default' } = page.props.flash.toast;
            
            switch (type) {
                case 'success':
                    toast.success(title, { description });
                    break;
                case 'error':
                    toast.error(title, { description });
                    break;
                case 'info':
                    toast.info(title, { description });
                    break;
                case 'warning':
                    toast.warning(title, { description });
                    break;
                default:
                    toast(title, { description });
                    break;
            }
        }
    } catch (error) {
        console.error('Error displaying toast notification:', error);
    }
};

onMounted(() => {
    // Ensure this runs only in the browser
    if (typeof window !== "undefined" && window.performance) {
        const timing = window.performance.timing;
        loadTime.value = (timing.loadEventEnd - timing.navigationStart) / 1000; // in seconds
    }
    
    // Handle flash messages on initial load
    handleFlashMessages();
});

// Watch for flash messages when page changes
watch(() => page.props.flash, () => {
    handleFlashMessages();
}, { deep: true });

// Mobile Navigation Setup
const route = inject("route");
const mode = useColorMode({
    attribute: "class",
    modes: { light: "", dark: "dark" },
    initialValue: "light",
});
const isDarkMode = computed(() => mode.value === "dark");
const isMobileMenuOpen = ref(false);
const appName = "DccpHub";

// Navigation configuration (same as in AppSidebarContent)
const navigationConfig = [
    {
        label: "Platform",
        items: [
            {
                name: "Dashboard",
                icon: "lucide:layout-dashboard",
                route: "dashboard",
            },
            { name: "Settings", icon: "lucide:settings", route: "profile.show" },
            { name: "Schedule", icon: "lucide:calendar", route: "schedule.index" },
            { name: "Tuition", icon: "lucide:banknote", route: "tuition.index" },
            { name: "Subjects", icon: "lucide:book", route: "subjects.index" },
        ],
    },
    {
        label: null,
        class: "mt-auto",
        items: [
            {
                name: "Support",
                icon: "lucide:life-buoy",
                href: "https://github.com/yukazakiri/DccpHubv2/issues",
                external: true,
            },
            {
                name: "Change-Log",
                icon: "lucide:file-text",
                route: "changelog.index",
                external: false,
            },
        ],
    },
];

// Selected items for mobile bottom navigation
const mobileNavItems = computed(() => {
    return [
        navigationConfig[0].items[0], // Dashboard
        navigationConfig[0].items[2], // Schedule
        navigationConfig[0].items[3], // Tuition
        navigationConfig[0].items[4], // Subjects
        { name: "Menu", icon: "lucide:menu", action: "toggleMenu" },
    ];
});
function logout() {
    router.post(route("logout"));
}

// Send test notification (for development)
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

// Get current page title
const currentPageTitle = computed(() => props.title || appName);

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

// Reorganize navigation for mobile
const mobileNavConfig = computed(() => ({
    quickActions: [
        {
            name: "Dashboard",
            icon: "lucide:layout-dashboard",
            route: "dashboard",
        },
        {
            name: "Schedule",
            icon: "lucide:calendar",
            route: "schedule.index",
        },
        {
            name: "Tuition",
            icon: "lucide:banknote",
            route: "tuition.index",
        },
        {
            name: "Subjects",
            icon: "lucide:book",
            route: "subjects.index",
        },
    ],
    mainMenu: [
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
            name: "Change-Log",
            icon: "lucide:file-text",
            route: "changelog.index",
        },
    ]
}));
</script>

<template>
    <div>
        <Sonner position="top-center" />

        <!-- Mobile Top Navigation Bar -->
        <div
            class="fixed top-0 left-0 right-0 h-14 border-b bg-background z-50 md:hidden flex items-center justify-between px-4">
            <div class="flex items-center space-x-2">
                <AppLogoIcon class="w-7 h-7" />
                <span class="font-bold">{{ appName }}</span>
            </div>

            <div class="flex items-center gap-2">
                <SemesterSchoolYearSelector />
                <div class="font-medium text-sm">{{ currentPageTitle }}</div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Notifications for authenticated users -->
                <NotificationDropdown
                    v-if="user"
                    ref="notificationDropdown"
                    class="md:hidden"
                />

                <Sheet v-model:open="isMobileMenuOpen">
                <SheetTrigger asChild>
                    <Button variant="ghost" size="icon" class="md:hidden">
                        <Icon icon="lucide:menu" class="h-5 w-5" />
                    </Button>
                </SheetTrigger>
                <SheetContent side="right" class="w-[85vw] max-w-sm p-0">
                    <!-- Header with user profile -->
                    <div class="border-b p-4 bg-secondary/30">
                        <AppUserManager />
                    </div>

                    <!-- Main menu content -->
                    <div class="overflow-y-auto h-[calc(100vh-180px)]">
                        <!-- Quick Actions Grid -->
                        <div class="p-4 border-b">
                            <h3 class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-3">
                                Quick Actions
                            </h3>
                            <div class="grid grid-cols-2 gap-2">
                                <Link v-for="item in mobileNavConfig.quickActions" :key="item.name"
                                    :href="route(item.route)"
                                    class="flex flex-col items-center p-4 rounded-lg hover:bg-secondary/80 transition-colors"
                                    :class="route().current(item.route) ? 'bg-secondary text-primary' : ''" prefetch>
                                <Icon :icon="item.icon" class="h-6 w-6 mb-2" />
                                <span class="text-sm font-medium">{{ item.name }}</span>
                                </Link>
                            </div>
                        </div>

                        <!-- Main Menu List -->
                        <div class="p-4">
                            <h3 class="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-3">
                                Menu
                            </h3>
                            <div class="space-y-1">
                                <component v-for="item in mobileNavConfig.mainMenu" :key="item.name"
                                    v-bind="renderLink(item)" :is="item.external ? 'a' : Link"
                                    class="flex items-center w-full" :class="[
                                        'flex items-center px-4 py-3 text-sm font-medium rounded-md transition-colors',
                                        !item.external && route().current(item.route)
                                            ? 'bg-secondary text-primary' : 'hover:bg-secondary/50'
                                    ]">
                                    <Icon :icon="item.icon" class="mr-3 h-5 w-5" />
                                    {{ item.name }}
                                </component>

                                <!-- Theme Toggle -->
                                <button
                                    class="w-full flex items-center px-4 py-3 text-sm font-medium rounded-md hover:bg-secondary/50 transition-colors"
                                    @click="mode = isDarkMode ? 'light' : 'dark'">
                                    <Icon :icon="isDarkMode ? 'lucide:moon' : 'lucide:sun'" class="mr-3 h-5 w-5" />
                                    {{ isDarkMode ? "Dark" : "Light" }} Mode
                                </button>

                                <!-- Test Notification Button (Development) -->
                                <button
                                    v-if="user && $page.props.app?.env === 'local'"
                                    class="w-full flex items-center px-4 py-3 text-sm font-medium rounded-md hover:bg-secondary/50 transition-colors"
                                    @click="sendTestNotificationHandler">
                                    <Icon icon="lucide:bell" class="mr-3 h-5 w-5" />
                                    Test Notification
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="border-t p-4 bg-background/80 backdrop-blur-sm">
                        <div class="flex items-center justify-between text-sm text-muted-foreground">
                            <span>&copy; {{ new Date().getFullYear() }} {{ appName }}</span>
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
            <Sidebar>
                <SidebarHeader>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <AppTeamManager v-if="$page.props.jetstream.hasTeamFeatures" />
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarHeader>

                <AppSidebarContent />

                <SidebarFooter>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <AppUserManager />
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarFooter>
            </Sidebar>

            <SidebarInset>
                <header
                    class="flex h-16 shrink-0 items-center gap-2 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:block hidden">
                    <div class="flex items-center justify-between w-full gap-2 px-4 mt-4">
                        <div class="flex items-center gap-2">
                            <SidebarTrigger class="" />
                            <Separator orientation="vertical" class="mr-2 h-4 hidden md:block" />
                            <Breadcrumb>
                                <BreadcrumbList>
                                    <BreadcrumbItem>
                                        <BreadcrumbLink>
                                            {{ title }}
                                        </BreadcrumbLink>
                                    </BreadcrumbItem>
                                </BreadcrumbList>
                            </Breadcrumb>
                        </div>

                        <div class="flex items-center gap-2">
                            <!-- Notifications for authenticated users -->
                            <NotificationDropdown
                                v-if="user"
                                ref="notificationDropdown"
                                class="hidden md:block"
                            />
                            <SemesterSchoolYearSelector />
                        </div>
                    </div>
                </header>
                <main class="flex flex-1 flex-col gap-4 p-4 pt-0 mt-14 md:mt-0">
                    <slot />
                </main>

                <!-- Compact Footer -->
                <footer class="py-3 px-4 border-t mb-14 md:mb-0 mg:block">
                    <div class="container mx-auto flex flex-wrap items-center justify-between gap-y-4">
                        <!-- Copyright -->
                        <div class="text-center md:text-left">
                            <p class="text-sm text-muted-foreground">
                                &copy; {{ new Date().getFullYear() }} DccpHub. All rights
                                reserved.
                            </p>
                        </div>

                        <!-- Social Links -->
                        <div class="flex items-center gap-3">
                            <Link href="https://github.com/yukazakiri"
                                class="text-muted-foreground hover:text-primary transition-colors" target="_blank">
                            <Icon icon="lucide:github" class="h-5 w-5" />
                            </Link>
                            <Link href="#" class="text-muted-foreground hover:text-primary transition-colors"
                                target="_blank">
                            <Icon icon="lucide:twitter" class="h-5 w-5" />
                            </Link>
                        </div>

                        <!-- Tech Stack & Load Time -->
                        <div class="flex items-center gap-2 text-muted-foreground text-sm">
                            <Icon icon="logos:laravel" class="h-4 w-4" />
                            <Icon icon="logos:vue" class="h-4 w-4" />
                            <Icon icon="logos:inertiajs-icon" class="h-4 w-4" />
                            <Icon icon="logos:tailwindcss-icon" class="h-4 w-4" />
                            <Icon icon="logos:vitejs" class="h-4 w-4" />
                            <span v-if="loadTime" class="text-xs"> ({{ loadTime }}s) </span>
                            <span v-else class="text-xs"> (Loading...) </span>
                        </div>
                    </div>
                </footer>
            </SidebarInset>
        </SidebarProvider>

        <!-- Redesigned Mobile Bottom Navigation -->
        <div class="fixed bottom-0 left-0 right-0 border-t bg-background/80 backdrop-blur-sm z-50 md:hidden">
            <div class="grid grid-cols-5 px-1 py-1">
                <Link v-for="item in mobileNavConfig.quickActions.slice(0, 4)" :key="item.name"
                    :href="route(item.route)" class="flex flex-col items-center py-2 px-1 rounded-lg transition-colors"
                    :class="route().current(item.route) ? 'text-primary bg-secondary/50' : 'text-muted-foreground hover:bg-secondary/30'"
                    prefetch>
                <Icon :icon="item.icon" class="h-5 w-5 mb-1" />
                <span class="text-xs">{{ item.name }}</span>
                </Link>
                <button
                    class="flex flex-col items-center py-2 px-1 text-muted-foreground rounded-lg hover:bg-secondary/30 transition-colors"
                    @click="isMobileMenuOpen = true">
                    <Icon icon="lucide:menu" class="h-5 w-5 mb-1" />
                    <span class="text-xs">Menu</span>
                </button>
            </div>
        </div>
    </div>
</template>
