<script setup>
import { Progress } from "@/Components/shadcn/ui/progress";
import { Button } from "@/Components/shadcn/ui/button";
import { Badge } from "@/Components/shadcn/ui/badge";

const props = defineProps({
  completedCount: {
    type: Number,
    required: true,
  },
  ongoingCount: {
    type: Number,
    required: true,
  },
  incompleteCount: {
    type: Number,
    required: true,
  },
  completionPercentage: {
    type: Number,
    required: true,
  },
});
</script>

<template>
  <!-- Mobile Quick Access Actions -->
  <div class="block lg:hidden">
    <div class="flex flex-col space-y-2">
      <Button variant="outline" class="text-left justify-between">
        <span>{{ completedCount }} Completed</span>
        <Badge variant="success" class="ml-2">
          {{ Math.round(completionPercentage) }}%
        </Badge>
      </Button>
      <div class="grid grid-cols-2 gap-2">
        <Button variant="outline" class="text-left">
          <span>{{ ongoingCount }} Ongoing</span>
        </Button>
        <Button variant="outline" class="text-left">
          <span>{{ incompleteCount }} Incomplete</span>
        </Button>
      </div>
    </div>
  </div>

  <!-- Progress bar (visible on all devices) -->
  <div class="mt-4">
    <div class="flex justify-between items-center mb-1">
      <span class="text-sm font-medium">Overall Progress</span>
      <span class="text-sm text-muted-foreground">
        {{ completionPercentage.toFixed(1) }}%
      </span>
    </div>
    <Progress :value="completionPercentage" class="h-2" />
  </div>
</template>
