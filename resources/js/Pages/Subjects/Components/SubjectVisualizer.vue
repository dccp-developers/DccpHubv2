<script setup>
import { computed, ref } from "vue";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/shadcn/ui/tabs";
import { Card, CardContent } from "@/Components/shadcn/ui/card";
import { Badge } from "@/Components/shadcn/ui/badge";

const props = defineProps({
  allSubjects: {
    type: Array,
    required: true,
  },
});

const activeTab = ref("timeline");

// Group subjects by year and semester for timeline view
const subjectsByYearAndSemester = computed(() => {
  const grouped = {};
  
  // Find the unique years and semesters
  const years = [...new Set(props.allSubjects.map(s => s.academic_year))].sort();
  const semesters = [...new Set(props.allSubjects.map(s => s.semester))].sort();
  
  // Initialize the structure
  years.forEach(year => {
    grouped[year] = {};
    semesters.forEach(semester => {
      grouped[year][semester] = [];
    });
  });
  
  // Fill in the subjects
  props.allSubjects.forEach(subject => {
    grouped[subject.academic_year][subject.semester].push(subject);
  });
  
  return grouped;
});

const getStatusColor = (status) => {
  switch (status) {
    case "Completed":
      return "bg-green-100 dark:bg-green-900/20 border-green-300 dark:border-green-800";
    case "Ongoing":
      return "bg-yellow-100 dark:bg-yellow-900/20 border-yellow-300 dark:border-yellow-800";
    case "Incomplete":
      return "bg-slate-100 dark:bg-slate-900/20 border-slate-300 dark:border-slate-800";
    default:
      return "bg-gray-100 dark:bg-gray-800 border-gray-300 dark:border-gray-700";
  }
};

const getStatusBadgeVariant = (status) => {
  switch (status) {
    case "Completed":
      return "success";
    case "Ongoing":
      return "warning";
    case "Incomplete":
      return "secondary";
    default:
      return "default";
  }
};
</script>

<template>
  <Tabs v-model="activeTab" class="w-full">
    <TabsList class="grid w-full grid-cols-2">
      <TabsTrigger value="timeline">Timeline</TabsTrigger>
      <TabsTrigger value="grid">Grid View</TabsTrigger>
    </TabsList>
    
    <!-- Timeline View -->
    <TabsContent value="timeline" class="mt-4">
      <div class="space-y-8">
        <div v-for="(semesters, year) in subjectsByYearAndSemester" :key="year" class="space-y-4">
          <h3 class="text-lg font-semibold">Year {{ year }}</h3>
          
          <div v-for="(subjects, semester) in semesters" :key="`${year}-${semester}`" class="space-y-2">
            <h4 class="text-md font-medium">Semester {{ semester }}</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
              <div 
                v-for="subject in subjects" 
                :key="subject.id"
                :class="[
                  'p-3 rounded-md border-l-4 transition-all',
                  getStatusColor(subject.status)
                ]"
              >
                <div class="flex justify-between items-start">
                  <div>
                    <p class="font-medium">{{ subject.code }}</p>
                    <p class="text-sm truncate">{{ subject.title }}</p>
                  </div>
                  <Badge :variant="getStatusBadgeVariant(subject.status)" class="ml-2">
                    {{ subject.status }}
                  </Badge>
                </div>
                <div class="mt-1 text-xs text-muted-foreground">
                  <span>{{ subject.units }} units</span>
                  <span v-if="subject.grade" class="ml-2">• Grade: {{ subject.grade }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </TabsContent>
    
    <!-- Grid View -->
    <TabsContent value="grid" class="mt-4">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
        <Card 
          v-for="subject in allSubjects" 
          :key="subject.id"
          :class="[
            'overflow-hidden transition-all hover:shadow-md',
            { 'opacity-60': subject.status === 'Incomplete' }
          ]"
        >
          <div :class="['h-2', getStatusColor(subject.status)]"></div>
          <CardContent class="p-4">
            <div class="flex justify-between items-start">
              <div>
                <p class="font-medium">{{ subject.code }}</p>
                <p class="text-sm">{{ subject.title }}</p>
              </div>
              <Badge :variant="getStatusBadgeVariant(subject.status)">
                {{ subject.status }}
              </Badge>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-x-2 gap-y-1 text-xs text-muted-foreground">
              <div>
                <span class="font-medium">Units:</span> {{ subject.units }}
              </div>
              <div>
                <span class="font-medium">Grade:</span> {{ subject.grade ?? "-" }}
              </div>
              <div>
                <span class="font-medium">Year:</span> {{ subject.academic_year }}
              </div>
              <div>
                <span class="font-medium">Semester:</span> {{ subject.semester }}
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </TabsContent>
  </Tabs>
</template>
