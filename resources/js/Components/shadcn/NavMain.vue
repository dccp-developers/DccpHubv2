<script setup>
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/Components/shadcn/ui/collapsible';
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuAction,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from '@/Components/shadcn/ui/sidebar';
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from "lucide-vue-next";

defineProps({
  items: { type: Array, required: true },
});
</script>

<template>
  <SidebarGroup>
    <SidebarGroupLabel>Platform</SidebarGroupLabel>
    <SidebarMenu>
      <Collapsible
        v-for="item in items"
        :key="item.title"
        as-child
        :default-open="item.isActive"
      >
        <SidebarMenuItem>
          <SidebarMenuButton as-child :tooltip="item.title">
            <Link
              v-if="!item.disabled"
              :href="item.url"
              :class="{ 'pointer-events-none opacity-50': item.disabled }"
              preserve-scroll
              preserve-state
            >
              <component :is="item.icon" />
              <span>{{ item.title }}</span>
            </Link>
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
          <template v-if="item.items?.length">
            <CollapsibleTrigger as-child>
              <SidebarMenuAction class="data-[state=open]:rotate-90">
                <ChevronRight />
                <span class="sr-only">Toggle</span>
              </SidebarMenuAction>
            </CollapsibleTrigger>
            <CollapsibleContent>
              <SidebarMenuSub>
                <SidebarMenuSubItem
                  v-for="subItem in item.items"
                  :key="subItem.title"
                >
                  <SidebarMenuSubButton as-child>
                    <Link
                      v-if="!subItem.disabled"
                      :href="subItem.url"
                      :class="{ 'pointer-events-none opacity-50': subItem.disabled }"
                      preserve-scroll
                      preserve-state
                    >
                      <span>{{ subItem.title }}</span>
                    </Link>
                    <div
                      v-else
                      class="flex items-center pointer-events-none opacity-50"
                      :aria-disabled="true"
                      :tabindex="-1"
                    >
                      <span>{{ subItem.title }}</span>
                    </div>
                  </SidebarMenuSubButton>
                </SidebarMenuSubItem>
              </SidebarMenuSub>
            </CollapsibleContent>
          </template>
        </SidebarMenuItem>
      </Collapsible>
    </SidebarMenu>
  </SidebarGroup>
</template>
