<script setup>
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/shadcn/ui/select";

defineProps({
  currentSemester: {
    type: String,
    required: true,
  },
  currentSchoolYear: {
    type: String,
    required: true,
  },
  selectedDay: {
    type: String,
    required: true,
  },
  daysOfWeek: {
    type: Array,
    required: true,
  },
});

const emit = defineEmits(['update:selectedDay']);

function updateSelectedDay(value) {
  emit('update:selectedDay', value);
}
</script>

<template>
  <header class="flex flex-col sm:flex-row justify-between gap-4">
    <div>
      <h1 class="text-xl font-bold">My Class Schedule</h1>
      <p class="text-sm text-muted-foreground">
        {{ currentSemester }} Semester, School Year {{ currentSchoolYear }}
      </p>
    </div>

    <div class="flex items-center gap-2">
      <Select :model-value="selectedDay" @update:model-value="updateSelectedDay" class="w-full sm:w-40">
        <SelectTrigger>
          <SelectValue placeholder="Filter by day" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All Days</SelectItem>
          <SelectItem v-for="day in daysOfWeek" :key="day" :value="day">
            {{ day.charAt(0).toUpperCase() + day.slice(1) }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>
  </header>
</template>
