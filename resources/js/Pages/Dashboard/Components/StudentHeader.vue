<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Avatar, AvatarImage, AvatarFallback } from '@/Components/shadcn/ui/avatar';
import { Button } from '@/Components/shadcn/ui/button';
import { Icon } from '@iconify/vue';
import { Card, CardContent } from '@/Components/shadcn/ui/card';
import { Badge } from '@/Components/shadcn/ui/badge';
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from '@/Components/shadcn/ui/dropdown-menu';

const props = defineProps({
  student: {
    type: Object,
    required: true,
  },
  user: {
    type: Object,
    required: true
  },
  currentDate: {
    type: String,
    required: true
  }
});

// Get greeting based on time of day
const greeting = computed(() => {
  const hour = new Date().getHours();
  if (hour < 12) return 'Good morning';
  if (hour < 17) return 'Good afternoon';
  return 'Good evening';
});

// Get student initials for avatar fallback
const studentInitials = computed(() => {
  const names = props.student.name.split(' ');
  return names.map(name => name.charAt(0)).join('').substring(0, 2).toUpperCase();
});

// Navigation functions
const navigateToProfile = () => {
  router.visit('/profile');
};

const navigateToSettings = () => {
  router.visit('/settings');
};

const navigateToNotifications = () => {
  router.visit('/notifications');
};
</script>

<template>
  <Card class="w-full border-0 shadow-sm">
    <CardContent class="p-3 md:p-4">
      <div class="flex items-center justify-between">
        <!-- Student Info Section -->
        <div class="flex items-center space-x-3">
          <Avatar class="h-10 w-10 md:h-12 md:w-12 ring-2 ring-primary/20">
            <AvatarImage :src="user.profile_photo_url" alt="Student" />
            <AvatarFallback class="text-sm font-bold bg-primary/10 text-primary">{{ studentInitials }}</AvatarFallback>
          </Avatar>

          <div class="min-w-0 flex-1">
            <h1 class="text-sm md:text-base font-semibold text-foreground truncate">
              {{ greeting }}, {{ student.name.split(' ')[0] }}!
            </h1>
            <div class="flex items-center gap-2 mt-1">
              <Badge variant="secondary" class="text-xs px-2 py-0.5">
                {{ student.grade }}
              </Badge>
              <span class="text-xs text-muted-foreground hidden sm:inline">
                {{ new Date().toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Simplified Actions -->
        <div class="flex items-center space-x-2">
          <!-- Notifications Button -->
          <Button variant="ghost" size="sm" @click="navigateToNotifications" class="relative">
            <Icon icon="lucide:bell" class="h-4 w-4" />
            <Badge variant="destructive" class="absolute -top-1 -right-1 h-4 w-4 p-0 text-xs flex items-center justify-center">
              3
            </Badge>
          </Button>

          <!-- Profile Menu -->
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" size="sm">
                <Icon icon="lucide:more-vertical" class="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-40">
              <DropdownMenuItem @click="navigateToProfile">
                <Icon icon="lucide:user" class="mr-2 h-4 w-4" />
                Profile
              </DropdownMenuItem>
              <DropdownMenuItem @click="navigateToSettings">
                <Icon icon="lucide:settings" class="mr-2 h-4 w-4" />
                Settings
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
