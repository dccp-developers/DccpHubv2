<script setup>
import {
  SidebarGroup,
  SidebarGroupContent,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/Components/shadcn/ui/sidebar';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  items: { type: Array, required: true },
});
</script>

<template>
  <SidebarGroup>
    <SidebarGroupContent>
      <SidebarMenu>
        <SidebarMenuItem v-for="item in items" :key="item.title">
          <SidebarMenuButton as-child size="sm">
            <!-- External links -->
            <a
              v-if="!item.disabled && item.url.startsWith('http')"
              :href="item.url"
              target="_blank"
              rel="noopener noreferrer"
              class="flex items-center gap-2"
            >
              <component :is="item.icon" />
              <span>{{ item.title }}</span>
            </a>
            <!-- Internal links -->
            <Link
              v-else-if="!item.disabled"
              :href="item.url"
              :class="{ 'pointer-events-none opacity-50': item.disabled }"
              preserve-scroll
              preserve-state
            >
              <component :is="item.icon" />
              <span>{{ item.title }}</span>
            </Link>
            <!-- Disabled items -->
            <div
              v-else
              class="flex items-center gap-2 pointer-events-none opacity-50"
              :aria-disabled="true"
              :tabindex="-1"
            >
              <component :is="item.icon" />
              <span>{{ item.title }}</span>
            </div>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarGroupContent>
  </SidebarGroup>
</template>
