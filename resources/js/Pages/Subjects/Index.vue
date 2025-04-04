<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { computed, ref } from "vue";
import { Button } from "@/Components/shadcn/ui/button";
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from "@/Components/shadcn/ui/tabs";

// Import our custom components
import SearchBar from "./Components/SearchBar.vue";
import SubjectFilters from "./Components/SubjectFilters.vue";
import ProgressOverview from "./Components/ProgressOverview.vue";
import ProgressCards from "./Components/ProgressCards.vue";
import SubjectTable from "./Components/SubjectTable.vue";
import SubjectCards from "./Components/SubjectCards.vue";
import SubjectVisualizer from "./Components/SubjectVisualizer.vue";
import CurriculumMap from "./Components/CurriculumMap.vue";

const props = defineProps({
  completedSubjects: {
    type: Array,
    required: true,
  },
  ongoingSubjects: {
    type: Array,
    required: true,
  },
  incompleteSubjects: {
    type: Array,
    required: true,
  },
  course: {
    // Course information
    type: Object,
    required: true,
  },
});

// --- State ---
const searchQuery = ref("");
const selectedStatus = ref("All");
const selectedYear = ref("All");
const selectedSemester = ref("All");
const activeView = ref("list"); // list, visualize, curriculum

// --- Computed Properties ---
const allSubjects = computed(() => {
  return [
    ...props.completedSubjects.map((s) => ({ ...s, status: "Completed" })),
    ...props.ongoingSubjects.map((s) => ({ ...s, status: "Ongoing" })),
    ...props.incompleteSubjects.map((s) => ({ ...s, status: "Incomplete" })),
  ];
});

// Extract unique years and semesters for filters
const uniqueYears = computed(() => {
  const years = new Set(allSubjects.value.map((s) => s.academic_year));
  return ["All", ...Array.from(years).sort()]; // Add 'All' and sort
});

const uniqueSemesters = computed(() => {
  const semesters = new Set(allSubjects.value.map((s) => s.semester));
  return ["All", ...Array.from(semesters).sort()]; // Add 'All' and sort
});

const filteredSubjects = computed(() => {
  let filtered = allSubjects.value;

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(
      (subject) =>
        subject.code.toLowerCase().includes(query) ||
        subject.title.toLowerCase().includes(query),
    );
  }

  if (selectedStatus.value !== "All") {
    filtered = filtered.filter(
      (subject) => subject.status === selectedStatus.value,
    );
  }

  if (selectedYear.value !== "All") {
    filtered = filtered.filter(
      (subject) => subject.academic_year === parseInt(selectedYear.value),
    );
  }

  if (selectedSemester.value !== "All") {
    filtered = filtered.filter(
      (subject) => subject.semester === parseInt(selectedSemester.value),
    );
  }

  return filtered;
});

// --- Computed Properties for Visualization ---
const completedCount = computed(() => props.completedSubjects.length);
const ongoingCount = computed(() => props.ongoingSubjects.length);
const incompleteCount = computed(() => props.incompleteSubjects.length);
const totalSubjectsCount = computed(() => allSubjects.value.length);

const completionPercentage = computed(() => {
  if (totalSubjectsCount.value === 0) return 0;
  return (completedCount.value / totalSubjectsCount.value) * 100;
});
</script>
<template>
  <AppLayout title="Subjects">
    <div class="mx-auto px-4 py-4 space-y-5 lg:space-y-6">
      <!-- Header Section with Course Info -->
      <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold">My Subjects</h1>
          <p class="text-sm text-muted-foreground">
            {{ course.code }} - {{ course.title }}
          </p>
        </div>

        <!-- Search Component -->
        <SearchBar v-model:searchQuery="searchQuery" />
      </div>

      <!-- Progress Overview (Mobile) -->
      <ProgressOverview
        :completedCount="completedCount"
        :ongoingCount="ongoingCount"
        :incompleteCount="incompleteCount"
        :completionPercentage="completionPercentage"
      />

      <!-- Main View Tabs -->
      <Tabs v-model="activeView" class="w-full">
        <TabsList class="grid w-full grid-cols-3">
          <TabsTrigger value="list">List View</TabsTrigger>
          <TabsTrigger value="visualize">Timeline</TabsTrigger>
          <TabsTrigger value="curriculum">Curriculum Map</TabsTrigger>
        </TabsList>

        <!-- List View Tab -->
        <TabsContent value="list" class="mt-4 space-y-4">
          <!-- Filters -->
          <SubjectFilters
            v-model:selectedStatus="selectedStatus"
            v-model:selectedYear="selectedYear"
            v-model:selectedSemester="selectedSemester"
            :uniqueYears="uniqueYears"
            :uniqueSemesters="uniqueSemesters"
          />

          <!-- Desktop Progress Cards -->
          <ProgressCards
            :completedCount="completedCount"
            :ongoingCount="ongoingCount"
            :incompleteCount="incompleteCount"
            :completionPercentage="completionPercentage"
          />

          <!-- Results count and export button -->
          <div class="text-sm text-muted-foreground flex items-center justify-between">
            <div>
              <span v-if="filteredSubjects.length">
                {{ filteredSubjects.length }}
                {{ filteredSubjects.length === 1 ? "subject" : "subjects" }} found
              </span>
              <span v-else>No subjects found matching your criteria.</span>
            </div>

            <Button
              v-if="filteredSubjects.length"
              variant="ghost"
              size="sm"
              class="text-xs hidden sm:flex"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="mr-1"
              >
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                <polyline points="7 10 12 15 17 10" />
                <line x1="12" y1="15" x2="12" y2="3" />
              </svg>
              Export
            </Button>
          </div>

          <!-- Subject Cards (Mobile) -->
          <SubjectCards :subjects="filteredSubjects" />

          <!-- Subject Table (Desktop) -->
          <SubjectTable :subjects="filteredSubjects" />
        </TabsContent>

        <!-- Timeline/Visualizer Tab -->
        <TabsContent value="visualize" class="mt-4">
          <SubjectVisualizer :allSubjects="allSubjects" />
        </TabsContent>

        <!-- Curriculum Map Tab -->
        <TabsContent value="curriculum" class="mt-4">
          <CurriculumMap :allSubjects="allSubjects" />
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>
