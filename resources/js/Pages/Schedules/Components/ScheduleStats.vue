<script setup>
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/shadcn/ui/card";

defineProps({
  totalClasses: {
    type: Number,
    required: true,
  },
  uniqueSubjects: {
    type: Number,
    required: true,
  },
  busiestDay: {
    type: Object,
    required: true,
  },
  classCountByDay: {
    type: Object,
    required: true,
  },
  daysOfWeek: {
    type: Array,
    required: true,
  },
});
</script>

<template>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <Card class="transition-all duration-300 hover:shadow-md">
      <CardHeader class="pb-2">
        <CardTitle class="text-sm font-medium">Total Classes</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="text-2xl font-bold">{{ totalClasses }}</div>
        <p class="text-xs text-muted-foreground">Classes this semester</p>
      </CardContent>
    </Card>

    <Card class="transition-all duration-300 hover:shadow-md">
      <CardHeader class="pb-2">
        <CardTitle class="text-sm font-medium">Subjects</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="text-2xl font-bold">{{ uniqueSubjects }}</div>
        <p class="text-xs text-muted-foreground">Enrolled subjects</p>
      </CardContent>
    </Card>

    <Card class="transition-all duration-300 hover:shadow-md">
      <CardHeader class="pb-2">
        <CardTitle class="text-sm font-medium">Busiest Day</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="text-2xl font-bold capitalize md:text-2xl text-xl">
          {{ busiestDay.day }}
        </div>
        <p class="text-xs text-muted-foreground">
          {{ busiestDay.count }} classes
        </p>
      </CardContent>
    </Card>

    <Card class="transition-all duration-300 hover:shadow-md">
      <CardHeader class="pb-2">
        <CardTitle class="text-sm font-medium">Today</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="text-2xl font-bold">
          {{ classCountByDay[daysOfWeek[new Date().getDay() - 1]] || 0 }}
        </div>
        <p class="text-xs text-muted-foreground">Classes today</p>
      </CardContent>
    </Card>
  </div>
</template>
