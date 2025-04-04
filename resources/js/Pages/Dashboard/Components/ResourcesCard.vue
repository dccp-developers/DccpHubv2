<script setup>
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/Components/shadcn/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/shadcn/ui/tabs';

defineProps({
  resources: {
    type: String,
    required: true
  }
});
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Learning Resources</CardTitle>
    </CardHeader>
    <CardContent>
      <!-- Subject Tabs -->
      <Tabs default-value="math">
        <TabsList class="mb-4">
          <TabsTrigger value="math">Math</TabsTrigger>
          <TabsTrigger value="science">Science</TabsTrigger>
          <TabsTrigger value="english">English</TabsTrigger>
          <TabsTrigger value="history">History</TabsTrigger>
        </TabsList>
        <TabsContent value="math">
          <div class="space-y-2">
            <div class="text-center py-4 text-muted-foreground">
              Coming Soon!
            </div>
          </div>
        </TabsContent>
        <TabsContent value="science">
          <div class="text-center py-4 text-muted-foreground">
            Coming Soon!
          </div>
        </TabsContent>
        <TabsContent value="english">
          <div class="text-center py-4 text-muted-foreground">
            Coming Soon!
          </div>
        </TabsContent>
        <TabsContent value="history">
          <div class="text-center py-4 text-muted-foreground">
            Coming Soon!
          </div>
        </TabsContent>
      </Tabs>
    </CardContent>
  </Card>
</template>
