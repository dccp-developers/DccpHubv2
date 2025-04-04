<script setup>
import { Card, CardContent } from "@/Components/shadcn/ui/card";
import { Badge } from "@/Components/shadcn/ui/badge";
import ScheduleCard from "./ScheduleCard.vue";
import { ref } from "vue";

const props = defineProps({
  schedulesBySubject: {
    type: Array,
    required: true,
  },
  isScheduleNow: {
    type: Function,
    required: true,
  },
});

const expandedSubjects = ref({});

function toggleSubject(subject) {
  expandedSubjects.value[subject] = !expandedSubjects.value[subject];
}

function isExpanded(subject) {
  return expandedSubjects.value[subject] !== false; // Default to expanded
}
</script>

<template>
  <Card>
    <CardContent class="p-0">
      <div class="max-h-[60vh] overflow-y-auto">
        <div
          v-if="schedulesBySubject.length === 0"
          class="py-8 text-center text-muted-foreground"
        >
          No classes scheduled
        </div>

        <div v-else>
          <div
            v-for="group in schedulesBySubject"
            :key="group.subject"
            class="border-b last:border-0 transition-all duration-300"
          >
            <div
              class="p-3 cursor-pointer hover:bg-muted/50 flex justify-between items-center transition-colors duration-200"
              @click="toggleSubject(group.subject)"
            >
              <div>
                <h3 class="font-bold text-sm">{{ group.subject }}</h3>
                <p class="text-xs text-muted-foreground">
                  {{ group.code }} •
                  {{ group.instances.length }} sessions
                </p>
              </div>
              <div class="flex items-center gap-2">
                <Badge
                  :class="
                    group.color
                      .replace('bg-', 'bg-opacity-20 ')
                      .replace('text-', '')
                  "
                  class="transition-all duration-300"
                >
                  {{ group.instances.length }}
                </Badge>
                <div class="text-muted-foreground transition-transform duration-200" :class="isExpanded(group.subject) ? 'rotate-180' : ''">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down"><path d="m6 9 6 6 6-6"/></svg>
                </div>
              </div>
            </div>

            <div 
              class="px-3 pb-3 grid gap-2 transition-all duration-300 overflow-hidden"
              :class="isExpanded(group.subject) ? 'max-h-[1000px] opacity-100' : 'max-h-0 opacity-0 pb-0'"
            >
              <div
                v-for="schedule in group.instances"
                :key="schedule.id"
                class="transition-all duration-300 transform hover:translate-y-[-2px]"
              >
                <ScheduleCard 
                  :schedule="schedule" 
                  :is-now="isScheduleNow(schedule)"
                  view="list"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
