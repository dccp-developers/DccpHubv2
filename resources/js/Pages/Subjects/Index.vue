<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/Components/shadcn/ui/card";
import { Badge } from "@/Components/shadcn/ui/badge";
import { Separator } from "@/Components/shadcn/ui/separator";
import { computed, ref } from "vue";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/Components/shadcn/ui/accordion";
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/Components/shadcn/ui/table";
import { Input } from "@/Components/shadcn/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/shadcn/ui/select";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/Components/shadcn/ui/popover";
import { Button } from "@/Components/shadcn/ui/button";
import { CalendarIcon } from "lucide-vue-next";
import { Calendar } from "@/Components/shadcn/ui/calendar";
import { format } from "date-fns";
import { cn } from "@/lib/utils";
import { Progress } from "@/Components/shadcn/ui/progress";

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
const date = ref();

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

const getStatusBadgeVariant = (status) => {
  switch (status) {
    case "Completed":
      return "success";
    case "Ongoing":
      return "warning";
    case "Incomplete":
      return "destructive";
    default:
      return "default";
  }
};
</script>
<template>
  <AppLayout title="Subjects">
    <div class="mx-auto px-4 py-4 space-y-5 lg:space-y-6">
      <!-- Mobile Quick Access Actions -->
      <div class="block lg:hidden">
        <div class="flex flex-col space-y-2">
          <Button variant="outline" class="text-left justify-between">
            <span>{{ completedCount }} Completed</span>
            <Badge variant="success" class="ml-2"
              >{{ Math.round(completionPercentage) }}%</Badge
            >
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

      <!-- Header Section with Sticky Filter Bar -->
      <div class="space-y-3">
        <div
          class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2"
        >
          <div>
            <h1 class="text-xl sm:text-2xl font-bold">Subject Status</h1>
            <p class="text-sm text-muted-foreground">
              {{ course.code }} - {{ course.title }}
            </p>
          </div>

          <!-- Search always visible -->
          <div class="relative w-full sm:w-auto sm:min-w-[250px]">
            <Input
              v-model="searchQuery"
              type="text"
              placeholder="Search subjects..."
              class="w-full pr-8"
            />
            <Button
              variant="ghost"
              size="icon"
              class="absolute right-0 top-0 h-full"
              v-if="searchQuery"
              @click="searchQuery = ''"
            >
              <span class="sr-only">Clear search</span>
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="lucide lucide-x"
              >
                <path d="M18 6 6 18" />
                <path d="m6 6 12 12" />
              </svg>
            </Button>
          </div>
        </div>

        <!-- Collapsible filters for mobile -->
        <Accordion type="single" collapsible class="lg:hidden">
          <AccordionItem value="filters">
            <AccordionTrigger class="py-2 text-sm">Filters</AccordionTrigger>
            <AccordionContent>
              <div class="grid grid-cols-1 gap-2 pt-2">
                <Select v-model="selectedStatus">
                  <SelectTrigger class="w-full">
                    <SelectValue placeholder="Status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="All"> All </SelectItem>
                    <SelectItem value="Completed"> Completed </SelectItem>
                    <SelectItem value="Ongoing"> Ongoing </SelectItem>
                    <SelectItem value="Incomplete"> Incomplete </SelectItem>
                  </SelectContent>
                </Select>

                <div class="grid grid-cols-2 gap-2">
                  <Select v-model="selectedYear">
                    <SelectTrigger class="w-full">
                      <SelectValue placeholder="Year" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="year in uniqueYears"
                        :key="year"
                        :value="year"
                      >
                        {{ year === "All" ? "All" : year }}
                      </SelectItem>
                    </SelectContent>
                  </Select>

                  <Select v-model="selectedSemester">
                    <SelectTrigger class="w-full">
                      <SelectValue placeholder="Semester" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="semester in uniqueSemesters"
                        :key="semester"
                        :value="semester"
                      >
                        {{ semester === "All" ? "All" : semester }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </AccordionContent>
          </AccordionItem>
        </Accordion>

        <!-- Desktop filters - always visible -->
        <div class="hidden lg:grid lg:grid-cols-3 gap-3">
          <Select v-model="selectedStatus">
            <SelectTrigger class="w-full">
              <SelectValue placeholder="Status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="All">All</SelectItem>
              <SelectItem value="Completed">Completed</SelectItem>
              <SelectItem value="Ongoing">Ongoing</SelectItem>
              <SelectItem value="Incomplete">Incomplete</SelectItem>
            </SelectContent>
          </Select>

          <Select v-model="selectedYear">
            <SelectTrigger class="w-full">
              <SelectValue placeholder="Year" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="year in uniqueYears" :key="year" :value="year">
                {{ year === "All" ? "All" : year }}
              </SelectItem>
            </SelectContent>
          </Select>

          <Select v-model="selectedSemester">
            <SelectTrigger class="w-full">
              <SelectValue placeholder="Semester" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="semester in uniqueSemesters"
                :key="semester"
                :value="semester"
              >
                {{ semester === "All" ? "All" : semester }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <!-- Desktop Progress Cards - Hidden on mobile -->
      <div class="hidden lg:block">
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-lg"> Overall Progress </CardTitle>
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
              <CardTitle class="text-lg"> Completed </CardTitle>
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
              <CardTitle class="text-lg"> Ongoing </CardTitle>
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
              <CardTitle class="text-lg"> Incomplete </CardTitle>
              <CardDescription
                >Subjects you have not yet taken.</CardDescription
              >
            </CardHeader>
            <CardContent>
              <div class="text-2xl font-bold">
                {{ incompleteCount }}
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Results count and guidance -->
      <div
        class="text-sm text-muted-foreground flex items-center justify-between"
      >
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

      <!-- Mobile Subject Card View -->
      <div class="lg:hidden space-y-3">
        <div
          v-for="subject in filteredSubjects"
          :key="subject.id"
          class="border rounded-md p-3"
        >
          <div class="flex justify-between items-start">
            <div>
              <p class="font-medium">{{ subject.code }}</p>
              <p class="text-sm">{{ subject.title }}</p>
            </div>
            <Badge :variant="getStatusBadgeVariant(subject.status)">
              {{ subject.status }}
            </Badge>
          </div>
          <div
            class="mt-2 grid grid-cols-2 gap-x-2 gap-y-1 text-xs text-muted-foreground"
          >
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
        </div>
        <div
          v-if="filteredSubjects.length === 0"
          class="text-center py-8 text-muted-foreground"
        >
          No subjects found matching your criteria.
        </div>
      </div>

      <!-- Desktop Subject Table - Hidden on mobile -->
      <div class="hidden lg:block overflow-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Code</TableHead>
              <TableHead>Title</TableHead>
              <TableHead> Units </TableHead>
              <TableHead> Grade </TableHead>
              <TableHead> Year </TableHead>
              <TableHead> Semester </TableHead>
              <TableHead>Status</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="subject in filteredSubjects" :key="subject.id">
              <TableCell class="font-medium">
                {{ subject.code }}
              </TableCell>
              <TableCell>{{ subject.title }}</TableCell>
              <TableCell>
                {{ subject.units }}
              </TableCell>
              <TableCell>
                {{ subject.grade ?? "-" }}
              </TableCell>
              <TableCell>
                {{ subject.academic_year }}
              </TableCell>
              <TableCell>
                {{ subject.semester }}
              </TableCell>
              <TableCell>
                <Badge :variant="getStatusBadgeVariant(subject.status)">
                  {{ subject.status }}
                </Badge>
              </TableCell>
            </TableRow>
            <TableRow v-if="filteredSubjects.length === 0">
              <TableCell colspan="7" class="text-center">
                No subjects found matching your criteria.
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </AppLayout>
</template>
