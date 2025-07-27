<script setup>
import { Button } from '@/Components/shadcn/ui/button';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/Components/shadcn/ui/card';
import { ChevronRightIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
  recentGrades: {
    type: Array,
    default: () => []
  }
});
</script>

<template>
  <Card class="border-0 shadow-sm">
    <CardHeader class="pb-2">
      <div class="flex items-center justify-between">
        <CardTitle class="text-sm font-semibold">Recent Grades</CardTitle>
        <Button variant="ghost" size="sm" class="text-xs">
          View All
        </Button>
      </div>
    </CardHeader>
    <CardContent class="p-3">
      <div class="space-y-2">
        <div v-for="grade in (Array.isArray(props.recentGrades) ? props.recentGrades.slice(0, 3) : [])" :key="grade.id" class="p-2 border border-border/50 rounded-lg">
          <div class="flex justify-between items-center">
            <span class="text-sm font-bold" :class="{
              'text-green-600': grade.grade && grade.grade.startsWith('A'),
              'text-blue-600': grade.grade && grade.grade.startsWith('B'),
              'text-yellow-600': grade.grade && grade.grade.startsWith('C'),
              'text-orange-600': grade.grade && grade.grade.startsWith('D'),
              'text-red-600': grade.grade && grade.grade.startsWith('F')
            }">{{ grade.grade || 'N/A' }}</span>
            <span class="text-xs text-muted-foreground">{{ grade.date || 'N/A' }}</span>
          </div>
          <p class="text-xs font-medium truncate">{{ grade.subject || 'Unknown Subject' }}</p>
        </div>
        <div v-if="!Array.isArray(props.recentGrades) || props.recentGrades.length === 0" class="text-center py-4">
          <p class="text-xs text-muted-foreground">No recent grades available.</p>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
