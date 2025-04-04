<script setup>
import { Badge } from "@/Components/shadcn/ui/badge";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/Components/shadcn/ui/tooltip";
import { Icon } from "@iconify/vue";

defineProps({
  schedule: {
    type: Object,
    required: true,
  },
  isNow: {
    type: Boolean,
    default: false,
  },
  view: {
    type: String,
    default: 'list',
  },
});
</script>

<template>
  <div 
    class="p-2 rounded-md border flex flex-col sm:flex-row sm:items-center gap-2 transition-all duration-300 hover:shadow-md"
    :class="[
      isNow ? 'border-primary ring-1 ring-primary animate-pulse-subtle' : '',
      view === 'timeline' ? 'w-full h-full' : ''
    ]"
  >
    <div class="flex gap-2 items-center sm:w-1/4">
      <div
        class="w-7 h-7 rounded-full flex items-center justify-center transition-transform duration-300 hover:scale-110"
        :class="schedule.color"
      >
        <span class="uppercase font-bold text-xs">{{ schedule.day_of_week.slice(0, 2) }}</span>
      </div>
      <div>
        <div class="capitalize text-sm font-medium">
          {{ schedule.day_of_week }}
        </div>
        <div class="text-xs text-muted-foreground">
          {{ schedule.time }}
        </div>
      </div>
    </div>

    <div class="grid grid-cols-3 gap-2 sm:w-3/4">
      <div class="text-xs">
        <div class="font-medium">Room</div>
        <div>{{ schedule.room }}</div>
      </div>

      <div class="text-xs">
        <div class="font-medium">Teacher</div>
        <Tooltip>
          <TooltipTrigger>
            <div class="truncate">{{ schedule.teacher }}</div>
          </TooltipTrigger>
          <TooltipContent>
            <p>{{ schedule.teacher }}</p>
          </TooltipContent>
        </Tooltip>
      </div>

      <div class="text-xs">
        <div class="font-medium">Section</div>
        <div>{{ schedule.section }}</div>
      </div>
    </div>
    
    <Badge v-if="isNow" class="absolute top-1 right-1 bg-primary text-primary-foreground text-[10px]">
      <Icon icon="lucide:clock" class="h-3 w-3 mr-1" />
      Now
    </Badge>
  </div>
</template>

<style scoped>
.animate-pulse-subtle {
  animation: pulse-subtle 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse-subtle {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.85;
  }
}
</style>
