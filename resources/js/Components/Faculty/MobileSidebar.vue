<script setup>
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/shadcn/ui/avatar'
import { Separator } from '@/Components/shadcn/ui/separator'
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/Components/shadcn/ui/sidebar'
import { useSidebar } from '@/Components/shadcn/ui/sidebar/utils'
import { Link, router, usePage } from '@inertiajs/vue3'
import {
    BookOpen,
    Calendar,
    FileText,
    GraduationCap,
    LayoutDashboard,
    LifeBuoy,
    Settings,
    Users,
    X,
} from 'lucide-vue-next'
import { computed, inject } from 'vue'

const route = inject('route')
const page = usePage()
const { setOpenMobile } = useSidebar()

const user = computed(() => ({
  name: page.props.auth?.user?.name || 'Faculty User',
  email: page.props.auth?.user?.email || 'faculty@example.com',
  avatar: page.props.auth?.user?.profile_photo_url || '',
}))

function closeMenu() {
  setOpenMobile(false)
}

function logout() {
  router.post(route('logout'))
}
</script>

<template>
  <div class="flex h-full w-full flex-col pb-[env(safe-area-inset-bottom)]">
    <!-- Header -->
    <div class="flex items-center justify-between px-4 pt-[env(safe-area-inset-top)] h-14 border-b">
      <div class="grid text-left leading-tight">
        <span class="font-semibold">Faculty Portal</span>
        <span class="text-xs text-muted-foreground">Teaching Hub</span>
      </div>
      <button class="p-2 rounded-md hover:bg-sidebar-accent" aria-label="Close menu" @click="closeMenu">
        <X class="h-5 w-5" />
      </button>
    </div>

    <!-- Profile -->
    <div class="px-4 py-3 border-b flex items-center gap-3">
      <Avatar class="h-9 w-9 rounded-lg">
        <AvatarImage :src="user.avatar" :alt="user.name" />
        <AvatarFallback class="rounded-lg">{{ user.name.charAt(0) }}</AvatarFallback>
      </Avatar>
      <div class="min-w-0">
        <div class="text-sm font-semibold truncate">{{ user.name }}</div>
        <div class="text-xs text-muted-foreground truncate">{{ user.email }}</div>
      </div>
    </div>

    <!-- Main nav using shadcn sidebar primitives -->
    <div class="flex-1 overflow-y-auto py-2 space-y-3">
      <SidebarGroup>
        <SidebarGroupLabel>Quick Access</SidebarGroupLabel>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton as-child size="lg">
              <Link :href="route('faculty.dashboard')" preserve-scroll preserve-state @click="closeMenu" :class="route().current('faculty.dashboard') ? 'text-primary' : ''">
                <LayoutDashboard />
                <span>Home</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
          <SidebarMenuItem>
            <SidebarMenuButton as-child size="lg">
              <Link :href="route('faculty.classes.index')" preserve-scroll preserve-state @click="closeMenu" :class="route().current('faculty.classes.*') ? 'text-primary' : ''">
                <GraduationCap />
                <span>My Classes</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
          <SidebarMenuItem>
            <SidebarMenuButton as-child size="lg">
              <Link :href="route('faculty.students.index')" preserve-scroll preserve-state @click="closeMenu" :class="route().current('faculty.students.*') ? 'text-primary' : ''">
                <Users />
                <span>Students</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
          <SidebarMenuItem>
            <SidebarMenuButton as-child size="lg">
              <Link :href="route('faculty.schedule.index')" preserve-scroll preserve-state @click="closeMenu" :class="route().current('faculty.schedule.*') ? 'text-primary' : ''">
                <Calendar />
                <span>Schedule</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarGroup>

      <SidebarGroup>
        <SidebarGroupLabel>Academic Tools</SidebarGroupLabel>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton as-child size="lg">
              <Link :href="route('faculty.attendance.index')" preserve-scroll preserve-state @click="closeMenu" :class="route().current('faculty.attendance.*') ? 'text-primary' : ''">
                <BookOpen />
                <span>Attendance</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
          <SidebarMenuItem>
            <SidebarMenuButton as-child size="lg">
              <Link :href="route('faculty.attendance.reports')" preserve-scroll preserve-state @click="closeMenu" :class="route().current('faculty.attendance.reports') ? 'text-primary' : ''">
                <FileText />
                <span>Reports</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarGroup>

      <SidebarGroup>
        <SidebarGroupLabel>Account</SidebarGroupLabel>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton as-child size="lg">
              <Link :href="route('profile.show')" preserve-scroll preserve-state @click="closeMenu" :class="route().current('profile.*') ? 'text-primary' : ''">
                <Settings />
                <span>Settings</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
          <SidebarMenuItem>
            <SidebarMenuButton as-child size="lg">
              <a href="https://github.com/yukazakiri/DccpHubv2/issues" target="_blank" rel="noopener noreferrer">
                <LifeBuoy />
                <span>Support</span>
              </a>
            </SidebarMenuButton>
          </SidebarMenuItem>
          <SidebarMenuItem>
            <SidebarMenuButton size="lg" @click="logout">
              <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              <span>Log out</span>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarGroup>
    </div>

    <Separator />
    <div class="h-[env(safe-area-inset-bottom)]" />
  </div>
</template>

