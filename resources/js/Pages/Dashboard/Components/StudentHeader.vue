<script setup>
import { Avatar, AvatarImage, AvatarFallback } from '@/Components/shadcn/ui/avatar';
import { Button } from '@/Components/shadcn/ui/button';
import { Icon } from '@iconify/vue';
import { Card, CardContent } from '@/Components/shadcn/ui/card';
import { Badge } from '@/Components/shadcn/ui/badge';
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from '@/Components/shadcn/ui/dropdown-menu';

defineProps({
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
</script>

<template>
  <Card class="w-full">
    <CardContent class="p-6">
      <div class="flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0">
        <div class="flex items-center space-x-4">
          <Avatar class="h-20 w-20 md:h-24 md:w-24 ring-2 ring-primary ring-offset-2 ring-offset-background">
            <AvatarImage :src="user.profile_photo_url" alt="Student" />
            <AvatarFallback class="text-lg font-bold">{{ student.name.charAt(0) }}</AvatarFallback>
          </Avatar>
          <div class="space-y-1">
            <h1 class="text-2xl md:text-3xl font-bold text-primary">{{ student.name }}</h1>
            <div class="flex flex-wrap items-center gap-2">
              <Badge variant="secondary" class="text-xs md:text-sm">
                <Icon icon="lucide:graduation-cap" class="mr-1 h-3 w-3 md:h-4 md:w-4" />
                {{ student.grade }}
              </Badge>
              <Badge variant="outline" class="text-xs md:text-sm">
                <Icon icon="lucide:calendar" class="mr-1 h-3 w-3 md:h-4 md:w-4" />
                {{ currentDate }}
              </Badge>
            </div>
          </div>
        </div>
        <div class="flex items-center space-x-2">
          <Button variant="outline" size="sm" class="hidden md:flex">
            <Icon icon="lucide:bell" class="mr-2 h-4 w-4" />
            Notifications
          </Button>
          <Button variant="outline" size="sm" class="hidden md:flex">
            <Icon icon="lucide:settings" class="mr-2 h-4 w-4" />
            Settings
          </Button>
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" size="icon" class="md:hidden">
                <Icon icon="lucide:more-vertical" class="h-5 w-5" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem>
                <Icon icon="lucide:bell" class="mr-2 h-4 w-4" />
                Notifications
              </DropdownMenuItem>
              <DropdownMenuItem>
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
