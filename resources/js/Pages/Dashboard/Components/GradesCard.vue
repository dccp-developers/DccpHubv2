<script setup>
import { Button } from '@/Components/shadcn/ui/button';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/Components/shadcn/ui/card';
import { ChevronRightIcon } from '@heroicons/vue/20/solid';

defineProps({
  recentGrades: {
    type: Array,
    required: true
  }
});
</script>

<template>
  <Card>
    <CardHeader class="flex flex-row items-center justify-between">
      <CardTitle>Recent Grades</CardTitle>
      <Button variant="ghost" size="sm" class="flex items-center">
        View All
        <ChevronRightIcon class="w-4 h-4 ml-1" />
      </Button>
    </CardHeader>
    <CardContent>
      <div class="space-y-3">
        <div v-for="grade in recentGrades" :key="grade.id" class="p-3 border rounded-lg">
          <div class="flex justify-between items-center">
            <span class="text-lg font-bold" :class="{
              'text-green-600': grade.grade.startsWith('A'),
              'text-blue-600': grade.grade.startsWith('B'),
              'text-yellow-600': grade.grade.startsWith('C'),
              'text-orange-600': grade.grade.startsWith('D'),
              'text-red-600': grade.grade.startsWith('F')
            }">{{ grade.grade }}</span>
            <span class="text-sm text-muted-foreground">{{ grade.date }}</span>
          </div>
          <p class="font-medium">{{ grade.subject }} - {{ grade.assignment }}</p>
          <p class="text-sm text-muted-foreground">{{ grade.score }}</p>
        </div>
        <div v-if="recentGrades.length === 0" class="text-center py-4 text-muted-foreground">
          No recent grades available.
        </div>
      </div>
    </CardContent>
  </Card>
</template>
