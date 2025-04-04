<script setup>
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/Components/shadcn/ui/card";
import { Progress } from "@/Components/shadcn/ui/progress";

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
  <div class="hidden lg:block">
    <Card>
      <CardHeader class="pb-2">
        <CardTitle class="text-lg">Overall Progress</CardTitle>
        <CardDescription>
          Your progress towards completing all subjects.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <Progress :value="completionPercentage" class="mb-2" />
        <div class="text-sm text-muted-foreground">
          {{ completionPercentage.toFixed(2) }}% Complete
        </div>
      </CardContent>
    </Card>

    <div class="grid grid-cols-3 gap-3 mt-4">
      <Card>
        <CardHeader class="pb-2">
          <CardTitle class="text-lg">Completed</CardTitle>
          <CardDescription>Subjects you have passed.</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">
            {{ completedCount }}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="pb-2">
          <CardTitle class="text-lg">Ongoing</CardTitle>
          <CardDescription>Subjects currently in progress.</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">
            {{ ongoingCount }}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="pb-2">
          <CardTitle class="text-lg">Incomplete</CardTitle>
          <CardDescription>Subjects you have not yet taken.</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">
            {{ incompleteCount }}
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
