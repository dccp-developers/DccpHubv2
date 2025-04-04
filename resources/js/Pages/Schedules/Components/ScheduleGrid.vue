<script setup>
import { Card, CardContent } from "@/Components/shadcn/ui/card";
import { Badge } from "@/Components/shadcn/ui/badge";

defineProps({
  daysOfWeek: {
    type: Array,
    required: true,
  },
  filteredSchedules: {
    type: Array,
    required: true,
  },
  classCountByDay: {
    type: Object,
    required: true,
  },
  isScheduleNow: {
    type: Function,
    required: true,
  },
});
</script>

<template>
  <Card>
    <CardContent class="p-0">
      <div class="max-h-[60vh] overflow-y-auto p-3">
        <div
          class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3"
        >
          <div
            v-for="day in daysOfWeek"
            :key="day"
            class="border rounded-lg overflow-hidden transition-all duration-300 hover:shadow-md"
          >
            <div
              class="bg-muted/30 p-2 border-b flex justify-between items-center"
            >
              <div class="capitalize font-bold text-sm">{{ day }}</div>
              <Badge class="transition-all duration-300">{{ classCountByDay[day] }}</Badge>
            </div>

            <div class="p-2 space-y-2">
              <div
                v-if="
                  filteredSchedules.filter((s) => s.day_of_week === day)
                    .length > 0
                "
                class="space-y-2"
              >
                <div
                  v-for="schedule in filteredSchedules.filter(
                    (s) => s.day_of_week === day,
                  )"
                  :key="schedule.id"
                  class="p-2 rounded-md transition-all duration-300 hover:shadow-md text-xs transform hover:translate-y-[-2px]"
                  :class="[
                    schedule.color,
                    isScheduleNow(schedule)
                      ? 'ring-1 ring-primary animate-pulse-subtle'
                      : '',
                  ]"
                >
                  <div class="text-[10px]">{{ schedule.time }}</div>
                  <div class="font-bold truncate">
                    {{ schedule.subject }}
                  </div>
                  <div class="text-[10px] mt-1 flex justify-between">
                    <span class="truncate">{{ schedule.room }}</span>
                    <span class="truncate ml-1">{{
                      schedule.teacher.split(" ")[0]
                    }}</span>
                  </div>
                </div>
              </div>

              <div
                v-else
                class="h-16 flex items-center justify-center border border-dashed rounded-md text-gray-400 text-xs transition-all duration-300 hover:border-gray-500"
              >
                No classes
              </div>
            </div>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
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
