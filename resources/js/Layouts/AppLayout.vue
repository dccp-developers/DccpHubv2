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
import { useSeoMetaTags } from "@/Composables/useSeoMetaTags.js";
import { onMounted, ref, computed, inject } from "vue";
import { Icon } from "@iconify/vue";
import { Link } from "@inertiajs/vue3";
import { Button } from "@/Components/shadcn/ui/button";
import {
  Sheet,
  SheetContent,
  SheetTrigger,
} from "@/Components/shadcn/ui/sheet";
import { useColorMode } from "@vueuse/core";
import AppLogoIcon from "@/Components/AppLogoIcon.vue";

const props = defineProps({
  title: String,
});

useSeoMetaTags({
  title: props.title,
});

// Page load time
const loadTime = ref(null);

onMounted(() => {
  // Ensure this runs only in the browser
  if (typeof window !== "undefined" && window.performance) {
    const timing = window.performance.timing;
    loadTime.value = (timing.loadEventEnd - timing.navigationStart) / 1000; // in seconds
  }
});

// Mobile Navigation Setup
const route = inject("route");
const mode = useColorMode({
  attribute: "class",
  modes: { light: "", dark: "dark" },
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
</script>

<template>
  <div>
    <Sonner position="top-center" />

    <!-- Mobile Top Navigation Bar -->
    <div
      class="fixed top-0 left-0 right-0 h-14 border-b bg-background z-50 md:hidden flex items-center justify-between px-4"
    >
      <div class="flex items-center space-x-2">
        <AppLogoIcon class="w-7 h-7" />
        <span class="font-bold">{{ appName }}</span>
      </div>

      <div class="font-medium">{{ currentPageTitle }}</div>

      <Sheet v-model:open="isMobileMenuOpen">
        <SheetTrigger asChild>
          <Button variant="ghost" size="icon" class="md:hidden">
            <Icon icon="lucide:menu" class="h-5 w-5" />
          </Button>
        </SheetTrigger>
        <SheetContent side="right" class="w-[85vw] max-w-sm">
          <div class="py-4">
            <!-- App Name and Logo -->
            <div class="mb-4 flex items-center space-x-4">
              <div class="w-8 h-8 rounded-full bg-primary overflow-hidden">
                <img
                  v-if="$page.props.auth.user.profile_photo_url"
                  :src="$page.props.auth.user.profile_photo_url"
                  alt="Profile"
                  class="w-full h-full object-cover"
                />
                <div
                  v-else
                  class="w-full h-full flex items-center justify-center text-primary-foreground font-bold"
                >
                  {{ $page.props.auth.user.name.charAt(0) }}
                </div>
              </div>
              <span class="text-lg font-bold">{{
                $page.props.auth.user.name
              }}</span>
            </div>

            <!-- All navigation items for mobile slide-out menu -->
            <div class="space-y-6">
              <div
                v-for="(group, groupIndex) in navigationConfig"
                :key="groupIndex"
              >
                <h3
                  v-if="group.label"
                  class="px-4 text-xs font-medium text-muted-foreground uppercase tracking-wider mb-2"
                >
                  {{ group.label }}
                </h3>
                <div class="space-y-1">
                  <div
                    v-for="item in group.items"
                    :key="item.name"
                    :class="[
                      'flex items-center px-4 py-2 text-sm font-medium rounded-md',
                      !item.external && route().current(item.route)
                        ? 'bg-secondary text-primary'
                        : 'text-foreground hover:bg-secondary/50',
                    ]"
                  >
                    <component
                      v-bind="renderLink(item)"
                      :is="item.external ? 'a' : Link"
                      class="flex items-center w-full"
                      prefetch
                    >
                      <Icon :icon="item.icon" class="mr-3 h-5 w-5" />
                      {{ item.name }}
                    </component>
                  </div>
                </div>
              </div>

              <!-- Theme toggle in menu -->
              <div
                class="flex items-center px-4 py-2 text-sm font-medium cursor-pointer text-foreground hover:bg-secondary/50 rounded-md"
                @click="mode = isDarkMode ? 'light' : 'dark'"
              >
                <Icon
                  :icon="isDarkMode ? 'lucide:moon' : 'lucide:sun'"
                  class="mr-3 h-5 w-5"
                />
                {{ isDarkMode ? "Dark" : "Light" }} Mode
              </div>
            </div>
          </div>
        </SheetContent>
      </Sheet>
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
          class="flex h-16 shrink-0 items-center gap-2 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:block hidden"
        >
          <div class="flex items-center gap-2 px-4 mt-4">
            <SidebarTrigger class="" />
            <Separator
              orientation="vertical"
              class="mr-2 h-4 hidden md:block"
            />
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
        </header>
        <main class="flex flex-1 flex-col gap-4 p-4 pt-0 mt-14 md:mt-0">
          <slot />
        </main>

        <!-- Compact Footer -->
        <footer class="py-3 px-4 border-t mb-14 md:mb-0 mg:block">
          <div
            class="container mx-auto flex flex-wrap items-center justify-between gap-y-4"
          >
            <!-- Copyright -->
            <div class="text-center md:text-left">
              <p class="text-sm text-muted-foreground">
                &copy; {{ new Date().getFullYear() }} DccpHub. All rights
                reserved.
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

    <!-- Mobile Bottom Navigation Bar -->
    <div
      class="fixed bottom-0 left-0 right-0 border-t bg-background z-50 md:hidden"
    >
      <div class="grid grid-cols-5">
        <Link
          v-for="item in mobileNavItems.slice(0, 4)"
          :key="item.name"
          :href="item.route ? route(item.route) : '#'"
          class="flex flex-col items-center py-2 text-xs"
          :class="
            route().current(item.route)
              ? 'text-primary'
              : 'text-muted-foreground'
          "
          prefetch
        >
          <Icon :icon="item.icon" class="h-5 w-5 mb-1" />
          <span>{{ item.name }}</span>
        </Link>
        <button
          class="flex flex-col items-center py-2 text-xs text-muted-foreground"
          @click="isMobileMenuOpen = true"
        >
          <Icon icon="lucide:menu" class="h-5 w-5 mb-1" />
          <span>Menu</span>
        </button>
      </div>
    </div>
  </div>
</template>
