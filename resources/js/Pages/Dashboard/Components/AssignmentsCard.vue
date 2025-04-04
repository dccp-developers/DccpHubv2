<script setup>
import { Button } from '@/Components/shadcn/ui/button';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/Components/shadcn/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/shadcn/ui/tabs';
import { computed } from 'vue';

defineProps({
  assignments: {
    type: String,
    required: true,
  }
});

// These will be used when assignments are implemented
const pendingAssignments = computed(() => []);
const completedAssignments = computed(() => []);
</script>

<template>
  <Card>
    <CardHeader class="flex flex-row items-center justify-between">
      <CardTitle>Assignments</CardTitle>
      <Button>Add New</Button>
    </CardHeader>
    <CardContent>
      <Tabs default-value="pending">
        <TabsList class="mb-4">
          <TabsTrigger value="pending">Pending</TabsTrigger>
          <TabsTrigger value="completed">Completed</TabsTrigger>
        </TabsList>
        <TabsContent value="pending">
          <div class="space-y-3 max-h-80 overflow-y-auto">
            <div v-if="pendingAssignments.length === 0" class="text-center py-4 text-muted-foreground">
              Coming Soon!
            </div>
          </div>
        </TabsContent>
        <TabsContent value="completed">
          <div class="space-y-3 max-h-80 overflow-y-auto">
            <div class="text-center py-4 text-muted-foreground">
              Coming Soon
            </div>
          </div>
        </TabsContent>
      </Tabs>
    </CardContent>
  </Card>
</template>
