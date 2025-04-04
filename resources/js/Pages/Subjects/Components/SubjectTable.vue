<script setup>
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/Components/shadcn/ui/table";
import { Badge } from "@/Components/shadcn/ui/badge";
import { Button } from "@/Components/shadcn/ui/button";
import { Progress } from "@/Components/shadcn/ui/progress";
import { computed, ref } from "vue";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/Components/shadcn/ui/dialog";

const props = defineProps({
  subjects: {
    type: Array,
    required: true,
  },
});

// State
const sortColumn = ref('academic_year');
const sortDirection = ref('asc');
const selectedSubject = ref(null);
const dialogOpen = ref(false);

// Group subjects by year and semester for better organization
const groupedSubjects = computed(() => {
  const grouped = {};

  props.subjects.forEach(subject => {
    const year = subject.academic_year;
    const semester = subject.semester;

    if (!grouped[year]) {
      grouped[year] = {};
    }

    if (!grouped[year][semester]) {
      grouped[year][semester] = [];
    }

    grouped[year][semester].push(subject);
  });

  // Convert to sorted array for v-for
  return Object.entries(grouped)
    .sort(([yearA], [yearB]) => parseInt(yearA) - parseInt(yearB))
    .map(([year, semesters]) => ({
      year: parseInt(year),
      semesters: Object.entries(semesters)
        .sort(([semA], [semB]) => parseInt(semA) - parseInt(semB))
        .map(([semester, subjects]) => ({
          semester: parseInt(semester),
          subjects,
        })),
    }));
});

// Sorted subjects
const sortedSubjects = computed(() => {
  return [...props.subjects].sort((a, b) => {
    let valueA = a[sortColumn.value];
    let valueB = b[sortColumn.value];

    // Handle string comparison
    if (typeof valueA === 'string') {
      valueA = valueA.toLowerCase();
      valueB = valueB.toLowerCase();
    }

    // Special case for status column
    if (sortColumn.value === 'status') {
      const statusOrder = { 'Completed': 1, 'Ongoing': 2, 'Incomplete': 3 };
      valueA = statusOrder[valueA] || 4;
      valueB = statusOrder[valueB] || 4;
    }

    if (valueA < valueB) return sortDirection.value === 'asc' ? -1 : 1;
    if (valueA > valueB) return sortDirection.value === 'asc' ? 1 : -1;
    return 0;
  });
});

// Sort table by column
const sortBy = (column) => {
  if (sortColumn.value === column) {
    // Toggle direction if clicking the same column
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    // Set new column and default to ascending
    sortColumn.value = column;
    sortDirection.value = 'asc';
  }
};

// Get sort icon
const getSortIcon = (column) => {
  if (sortColumn.value !== column) return '';

  return sortDirection.value === 'asc'
    ? `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>`
    : `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>`;
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

const getStatusIcon = (status) => {
  switch (status) {
    case "Completed":
      return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-600 dark:text-green-400"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`;
    case "Ongoing":
      return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-yellow-600 dark:text-yellow-400"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>`;
    case "Incomplete":
      return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-600 dark:text-slate-400"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>`;
    default:
      return '';
  }
};

const getProgressValue = (status) => {
  switch (status) {
    case "Completed":
      return 100;
    case "Ongoing":
      return 50;
    case "Incomplete":
      return 0;
    default:
      return 0;
  }
};

const showSubjectDetails = (subject) => {
  selectedSubject.value = subject;
  dialogOpen.value = true;
};

const closeDialog = () => {
  dialogOpen.value = false;
  // Reset after animation completes
  setTimeout(() => {
    selectedSubject.value = null;
  }, 300);
};
</script>

<template>
  <div class="hidden lg:block overflow-auto">
    <!-- Empty state message -->
    <div
      v-if="subjects.length === 0"
      class="text-center py-12 px-4 border rounded-lg bg-muted/30"
    >
      <div class="flex flex-col items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground mb-2">
          <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
          <line x1="9" x2="15" y1="9" y2="9"></line>
          <line x1="9" x2="15" y1="12" y2="12"></line>
          <line x1="9" x2="15" y1="15" y2="15"></line>
        </svg>
        <p class="text-lg font-medium">No subjects found</p>
        <p class="text-sm text-muted-foreground">Try adjusting your filters to find what you're looking for.</p>
      </div>
    </div>

    <!-- Enhanced Table with sorting and visual improvements -->
    <Table v-if="subjects.length > 0" class="border rounded-md overflow-hidden">
      <TableHeader class="bg-muted/50">
        <TableRow>
          <TableHead @click="sortBy('code')" class="cursor-pointer hover:bg-muted/80 transition-colors">
            <div class="flex items-center gap-1">
              Code
              <span v-html="getSortIcon('code')"></span>
            </div>
          </TableHead>
          <TableHead @click="sortBy('title')" class="cursor-pointer hover:bg-muted/80 transition-colors">
            <div class="flex items-center gap-1">
              Title
              <span v-html="getSortIcon('title')"></span>
            </div>
          </TableHead>
          <TableHead @click="sortBy('units')" class="cursor-pointer hover:bg-muted/80 transition-colors">
            <div class="flex items-center gap-1">
              Units
              <span v-html="getSortIcon('units')"></span>
            </div>
          </TableHead>
          <TableHead @click="sortBy('grade')" class="cursor-pointer hover:bg-muted/80 transition-colors">
            <div class="flex items-center gap-1">
              Grade
              <span v-html="getSortIcon('grade')"></span>
            </div>
          </TableHead>
          <TableHead @click="sortBy('academic_year')" class="cursor-pointer hover:bg-muted/80 transition-colors">
            <div class="flex items-center gap-1">
              Year
              <span v-html="getSortIcon('academic_year')"></span>
            </div>
          </TableHead>
          <TableHead @click="sortBy('semester')" class="cursor-pointer hover:bg-muted/80 transition-colors">
            <div class="flex items-center gap-1">
              Semester
              <span v-html="getSortIcon('semester')"></span>
            </div>
          </TableHead>
          <TableHead @click="sortBy('status')" class="cursor-pointer hover:bg-muted/80 transition-colors">
            <div class="flex items-center gap-1">
              Status
              <span v-html="getSortIcon('status')"></span>
            </div>
          </TableHead>
          <TableHead class="w-[50px]"></TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        <TableRow
          v-for="subject in sortedSubjects"
          :key="subject.id"
          class="group hover:bg-muted/30 cursor-pointer transition-colors"
          @click="showSubjectDetails(subject)"
        >
          <TableCell class="font-medium relative">
            <!-- Status indicator bar -->
            <div
              :class="[
                'absolute left-0 top-0 bottom-0 w-1',
                subject.status === 'Completed' ? 'bg-green-500 dark:bg-green-700' :
                subject.status === 'Ongoing' ? 'bg-yellow-500 dark:bg-yellow-700' :
                'bg-slate-300 dark:bg-slate-700'
              ]"
            ></div>

            <div class="flex items-center gap-2 pl-2">
              <span v-html="getStatusIcon(subject.status)"></span>
              {{ subject.code }}
            </div>
          </TableCell>
          <TableCell>{{ subject.title }}</TableCell>
          <TableCell>{{ subject.units }}</TableCell>
          <TableCell>
            <span :class="{'text-green-600 dark:text-green-400 font-medium': subject.grade && subject.grade >= 75}">
              {{ subject.grade ?? "Not graded" }}
            </span>
          </TableCell>
          <TableCell>{{ subject.academic_year }}</TableCell>
          <TableCell>{{ subject.semester }}</TableCell>
          <TableCell>
            <div class="flex items-center gap-2">
              <Badge :variant="getStatusBadgeVariant(subject.status)">
                {{ subject.status }}
              </Badge>
              <div class="w-16 hidden group-hover:block">
                <Progress :value="getProgressValue(subject.status)" class="h-1.5" />
              </div>
            </div>
          </TableCell>
          <TableCell>
            <Button variant="ghost" size="icon" class="opacity-0 group-hover:opacity-100 transition-opacity">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="1"></circle>
                <circle cx="19" cy="12" r="1"></circle>
                <circle cx="5" cy="12" r="1"></circle>
              </svg>
            </Button>
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>

    <!-- Subject Details Dialog (shared with mobile view) -->
    <Dialog :open="dialogOpen" @update:open="closeDialog">
      <DialogContent v-if="selectedSubject" class="sm:max-w-[500px]">
        <DialogHeader>
          <div class="flex items-center gap-2">
            <span v-html="getStatusIcon(selectedSubject.status)"></span>
            <div>
              <DialogTitle>{{ selectedSubject.code }}</DialogTitle>
              <DialogDescription>{{ selectedSubject.title }}</DialogDescription>
            </div>
          </div>
        </DialogHeader>

        <div class="py-4">
          <!-- Status and progress -->
          <div class="mb-4">
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm font-medium">Completion Status</span>
              <Badge :variant="getStatusBadgeVariant(selectedSubject.status)">
                {{ selectedSubject.status }}
              </Badge>
            </div>
            <Progress :value="getProgressValue(selectedSubject.status)" class="h-2" />
          </div>

          <!-- Details grid with improved layout -->
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <p class="text-sm font-medium">Units</p>
              <p>{{ selectedSubject.units }}</p>
            </div>

            <div class="space-y-1">
              <p class="text-sm font-medium">Grade</p>
              <p :class="{'text-green-600 dark:text-green-400': selectedSubject.grade && selectedSubject.grade >= 75}">
                {{ selectedSubject.grade ?? "Not graded" }}
              </p>
            </div>

            <div class="space-y-1">
              <p class="text-sm font-medium">Year Level</p>
              <p>{{ selectedSubject.academic_year }}</p>
            </div>

            <div class="space-y-1">
              <p class="text-sm font-medium">Semester</p>
              <p>{{ selectedSubject.semester }}</p>
            </div>
          </div>

          <!-- Prerequisites (placeholder) -->
          <div class="mt-4 pt-4 border-t">
            <p class="text-sm font-medium mb-2">Prerequisites</p>
            <p class="text-sm text-muted-foreground">
              No prerequisites for this subject.
            </p>
          </div>
        </div>

        <DialogFooter>
          <Button @click="closeDialog">Close</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
