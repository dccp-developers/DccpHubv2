<script setup>
import { Avatar, AvatarFallback } from "@/Components/shadcn/ui/avatar";
import AvatarImage from "@/Components/shadcn/ui/avatar/AvatarImage.vue";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/shadcn/ui/dropdown-menu";
import DropdownMenuGroup from "@/Components/shadcn/ui/dropdown-menu/DropdownMenuGroup.vue";
import SidebarMenuButton from "@/Components/shadcn/ui/sidebar/SidebarMenuButton.vue";
import { Button } from "@/Components/shadcn/ui/button";
import { Icon } from "@iconify/vue";
import { Link, router } from "@inertiajs/vue3";
import { inject, computed } from "vue";

const route = inject("route");
// Try to inject the sidebar context, but don't throw if it's not available
const sidebarContext = inject('SidebarContext', null);
const hasSidebarContext = computed(() => sidebarContext !== null);

function logout() {
    router.post(route("logout"));
}

// Create a component that will be used as trigger
const TriggerComponent = computed(() => {
    return hasSidebarContext.value ? SidebarMenuButton : Button;
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <component :is="TriggerComponent" size="lg" :class="[
                'w-full flex items-center gap-2',
                hasSidebarContext
                    ? 'data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground'
                    : 'justify-start'
            ]">
                <Avatar class="h-8 w-8 rounded-lg">
                    <AvatarImage :src="$page.props.auth.user.profile_photo_path ?? ''"
                        :alt="$page.props.auth.user.name" />
                    <AvatarFallback class="rounded-lg">
                        {{ $page.props.auth.user.name.charAt(0) }}
                    </AvatarFallback>
                </Avatar>
                <div class="grid flex-1 text-left text-sm leading-tight">
                    <span class="truncate font-semibold">{{
                        $page.props.auth.user.name
                        }}</span>
                    <span class="truncate text-xs">{{
                        $page.props.auth.user.email
                        }}</span>
                </div>
                <Icon icon="lucide:chevrons-up-down" class="ml-auto size-4" />
            </component>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg" side="bottom"
            align="end" :side-offset="4">
            <DropdownMenuLabel class="p-0 font-normal">
                <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <Avatar class="h-8 w-8 rounded-lg">
                        <AvatarImage :src="$page.props.auth.user.profile_photo_path ?? ''"
                            :alt="$page.props.auth.user.name" />
                        <AvatarFallback class="rounded-lg">
                            {{ $page.props.auth.user.name.charAt(0) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="grid flex-1 text-left text-sm leading-tight">
                        <span class="truncate font-semibold">{{
                            $page.props.auth.user.name
                            }}</span>
                        <span class="truncate text-xs">{{
                            $page.props.auth.user.email
                            }}</span>
                    </div>
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                <DropdownMenuItem :as="Link" :href="route('profile.show')">
                    <Icon icon="lucide:settings" />
                    Settings
                </DropdownMenuItem>

                <DropdownMenuItem :as="Link" :href="route('subscriptions.create')">
                    <Icon icon="lucide:credit-card" />
                    Billing
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem @click="logout">
                <Icon icon="lucide:log-out" />
                Log out
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
