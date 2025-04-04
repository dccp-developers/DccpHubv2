<script setup>
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/Components/shadcn/ui/card';

defineProps({
  exams: {
    type: String,
    required: true,
  }
});
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Upcoming Exams</CardTitle>
    </CardHeader>
    <CardContent>
      <div class="space-y-3">
        <div class="text-center py-4 text-muted-foreground">
          Coming Soon!
        </div>
      </div>
    </CardContent>
  </Card>
</template>
