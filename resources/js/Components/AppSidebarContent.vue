<script setup>
import {
  SidebarContent,
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuItem,
  SidebarMenuButton,
} from "@/Components/shadcn/ui/sidebar";
import { Icon } from "@iconify/vue";
import { Link } from "@inertiajs/vue3";
import { useColorMode } from "@vueuse/core";
import { computed, inject, ref } from "vue";
import { Button } from "@/Components/shadcn/ui/button";
import {
  Sheet,
  SheetContent,
  SheetTrigger,
} from "@/Components/shadcn/ui/sheet";
import AppLogoIcon from "@/Components/AppLogoIcon.vue";
const route = inject("route");
const mode = useColorMode({
  attribute: "class",
  modes: { light: "", dark: "dark" },
  initialValue: "light",
});

const appName = "DccpHub";
const isMobileMenuOpen = ref(false);

// Main navigation config - shared between sidebar and mobile navigation
const navigationConfig = [
  {
    label: "Platform",
    items: [
      {
        name: "Dashboard",
        icon: "lucide:layout-dashboard",
        route: "dashboard",
      },
      { name: "Attendance", icon: "lucide:clipboard-list", route: "student.attendance.index" },
      { name: "Attendance History", icon: "lucide:history", route: "student.attendance.history" },
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
    navigationConfig[0].items[1], // Attendance
    navigationConfig[0].items[3], // Schedule
    navigationConfig[0].items[5], // Subjects
    { name: "Menu", icon: "lucide:menu", action: "toggleMenu" },
  ];
});

const isDarkMode = computed(() => mode.value === "dark");

// Get current route information for breadcrumbs
const currentRouteName = computed(() => route().current());

// Get breadcrumbs based on current route
const breadcrumbs = computed(() => {
  const currentRoute = currentRouteName.value;
  const paths = [];

  // Base path is always visible
  paths.push({ name: "Home", route: "dashboard", icon: "lucide:home" });

  // Find the current navigation item
  navigationConfig.forEach((group) => {
    group.items.forEach((item) => {
      if (!item.external && item.route === currentRoute) {
        paths.push({ name: item.name, route: item.route, icon: item.icon });
      }
    });
  });

  return paths;
});

// Get current page title for mobile header
const currentPageTitle = computed(() => {
  const lastCrumb = breadcrumbs.value[breadcrumbs.value.length - 1];
  return lastCrumb ? lastCrumb.name : appName;
});

function handleMobileNavClick(item) {
  if (item.action === "toggleMenu") {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
  }
}

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
  <!-- Mobile Top Navigation Bar -->
  <div
    class="fixed top-0 left-0 right-0 h-14 border-b bg-background z-40 md:hidden flex items-center justify-between px-4"
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
          <div class="p-4 mb-2 flex items-center space-x-2">
            <!-- <AppLogoIcon /> -->
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

  <!-- Desktop Sidebar Content -->
  <SidebarContent>
    <!-- App Name and Logo -->
    <div class="p-4 mb-2 flex items-center space-x-2">
      <AppLogoIcon class="w-7 h-7" />
      <span class="text-lg font-bold">{{ appName }}</span>
    </div>

    <!-- Breadcrumbs -->
    <div class="px-3 mb-3">
      <div class="text-xs text-muted-foreground">
        <div class="flex items-center flex-wrap">
          <div
            v-for="(crumb, index) in breadcrumbs"
            :key="index"
            class="flex items-center"
          >
            <Link
              v-if="index < breadcrumbs.length - 1"
              :href="route(crumb.route)"
              class="flex items-center hover:text-primary"
            >
              <Icon :icon="crumb.icon" class="w-3 h-3 mr-1" />
              {{ crumb.name }}
            </Link>
            <span v-else class="flex items-center font-medium text-primary">
              <Icon :icon="crumb.icon" class="w-3 h-3 mr-1" />
              {{ crumb.name }}
            </span>

            <Icon
              v-if="index < breadcrumbs.length - 1"
              icon="lucide:chevron-right"
              class="w-3 h-3 mx-1 text-muted-foreground"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Divider after breadcrumbs -->
    <div class="h-px bg-border mx-3 mb-4"></div>

    <!-- Navigation Groups -->
    <SidebarGroup
      v-for="(group, index) in navigationConfig"
      :key="index"
      :class="group.class"
    >
      <SidebarGroupLabel v-if="group.label">
        {{ group.label }}
      </SidebarGroupLabel>
      <SidebarMenu>
        <SidebarMenuItem
          v-for="item in group.items"
          :key="item.name"
          :class="{
            'font-semibold text-primary bg-secondary rounded':
              !item.external && route().current(item.route),
          }"
        >
          <SidebarMenuButton as-child>
            <component
              v-bind="renderLink(item)"
              :is="item.external ? 'a' : Link"
              prefetch
            >
              <Icon :icon="item.icon" />
              {{ item.name }}
            </component>
          </SidebarMenuButton>
        </SidebarMenuItem>
        <SidebarMenuItem v-if="index === navigationConfig.length - 1">
          <SidebarMenuButton @click="mode = isDarkMode ? 'light' : 'dark'">
            <Icon :icon="isDarkMode ? 'lucide:moon' : 'lucide:sun'" />
            {{ isDarkMode ? "Dark" : "Light" }} Mode
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarGroup>
  </SidebarContent>

  <!-- Mobile Bottom Navigation Bar -->
  <div
    class="fixed bottom-0 left-0 right-0 border-t bg-background z-40 md:hidden"
  >
    <div class="grid grid-cols-5">
      <Link
        v-for="item in mobileNavItems.slice(0, 4)"
        :key="item.name"
        :href="item.route ? route(item.route) : '#'"
        class="flex flex-col items-center py-2 text-xs"
        :class="
          route().current(item.route) ? 'text-primary' : 'text-muted-foreground'
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

  <!-- Main content area padding offset for mobile navigation -->
  <div class="pt-14 pb-16 md:pt-0 md:pb-0"></div>
</template>

<style scoped>
/* Add any additional styles here */
</style>
