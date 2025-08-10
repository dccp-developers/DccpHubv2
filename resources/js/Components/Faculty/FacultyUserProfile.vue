<script setup>
import { Avatar, AvatarFallback, AvatarImage } from "@/Components/shadcn/ui/avatar";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/shadcn/ui/dropdown-menu";
import { SidebarMenuButton } from "@/Components/shadcn/ui/sidebar";
import { useSidebar } from "@/Components/shadcn/ui/sidebar/utils";
import { Icon } from "@iconify/vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, inject, ref } from "vue";

// Optional prop, falls back to Inertia page auth.user
const props = defineProps({
  user: { type: Object, required: false },
});

const route = inject("route");
const page = usePage();
const { isMobile } = useSidebar();
const mobileOpen = ref(false);

const currentUser = computed(() => ({
  name: props.user?.name || page.props.auth?.user?.name || "Faculty User",
  email: props.user?.email || page.props.auth?.user?.email || "faculty@example.com",
  avatar: props.user?.avatar || page.props.auth?.user?.profile_photo_url || "",
}));

function logout() {
  router.post(route("logout"));
}
</script>

<template>
  <!-- Mobile layout: large touch targets; shows inline actions -->
  <div class="md:hidden">
    <SidebarMenuButton
      size="lg"
      class="w-full"
    >
      <Avatar class="h-9 w-9 rounded-lg">
        <AvatarImage :src="currentUser.avatar" :alt="currentUser.name" />
        <AvatarFallback class="rounded-lg">{{ currentUser.name.charAt(0) }}</AvatarFallback>
      </Avatar>
      <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-semibold">{{ currentUser.name }}</span>
        <span class="truncate text-xs text-muted-foreground">{{ currentUser.email }}</span>
      </div>
    </SidebarMenuButton>

    <div class="px-2 py-2">
      <Link :href="route('profile.show')" class="flex items-center gap-2 rounded-md px-3 py-3 text-sm hover:bg-sidebar-accent">
        <Icon icon="lucide:settings" />
        <span class="font-medium">Settings</span>
      </Link>
      <button @click="logout" class="flex w-full items-center gap-2 rounded-md px-3 py-3 text-left text-sm hover:bg-sidebar-accent">
        <Icon icon="lucide:log-out" />
        <span class="font-medium">Log out</span>
      </button>
    </div>
  </div>

  <!-- Desktop layout: dropdown menu -->
  <DropdownMenu class="hidden md:block">
    <DropdownMenuTrigger as-child>
      <SidebarMenuButton
        size="lg"
        class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
      >
        <Avatar class="h-8 w-8 rounded-lg">
          <AvatarImage :src="currentUser.avatar" :alt="currentUser.name" />
          <AvatarFallback class="rounded-lg">{{ currentUser.name.charAt(0) }}</AvatarFallback>
        </Avatar>
        <div class="grid flex-1 text-left text-sm leading-tight">
          <span class="truncate font-semibold">{{ currentUser.name }}</span>
          <span class="truncate text-xs">Faculty Member</span>
        </div>
        <Icon icon="lucide:chevrons-up-down" class="ml-auto size-4" />
      </SidebarMenuButton>
    </DropdownMenuTrigger>
    <DropdownMenuContent
      class="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg"
      side="right"
      align="end"
      :side-offset="4"
    >
      <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
          <Avatar class="h-8 w-8 rounded-lg">
            <AvatarImage :src="currentUser.avatar" :alt="currentUser.name" />
            <AvatarFallback class="rounded-lg">{{ currentUser.name.charAt(0) }}</AvatarFallback>
          </Avatar>
          <div class="grid flex-1 text-left text-sm leading-tight">
            <span class="truncate font-semibold">{{ currentUser.name }}</span>
            <span class="truncate text-xs">{{ currentUser.email }}</span>
          </div>
        </div>
      </DropdownMenuLabel>
      <DropdownMenuSeparator />
      <DropdownMenuGroup>
        <DropdownMenuItem as-child>
          <Link :href="route('profile.show')" class="cursor-pointer">
            <Icon icon="lucide:settings" />
            Settings
          </Link>
        </DropdownMenuItem>
      </DropdownMenuGroup>
      <DropdownMenuSeparator />
      <DropdownMenuItem @click="logout" class="cursor-pointer">
        <Icon icon="lucide:log-out" />
        Log out
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
