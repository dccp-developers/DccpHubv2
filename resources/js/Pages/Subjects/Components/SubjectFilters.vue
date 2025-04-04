<script setup>
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/Components/shadcn/ui/accordion";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/shadcn/ui/select";

const props = defineProps({
  selectedStatus: {
    type: String,
    required: true,
  },
  selectedYear: {
    type: [String, Number],
    required: true,
  },
  selectedSemester: {
    type: [String, Number],
    required: true,
  },
  uniqueYears: {
    type: Array,
    required: true,
  },
  uniqueSemesters: {
    type: Array,
    required: true,
  },
});

const emit = defineEmits([
  "update:selectedStatus",
  "update:selectedYear",
  "update:selectedSemester",
]);
</script>

<template>
  <!-- Mobile filters -->
  <Accordion type="single" collapsible class="lg:hidden">
    <AccordionItem value="filters">
      <AccordionTrigger class="py-2 text-sm">Filters</AccordionTrigger>
      <AccordionContent>
        <div class="grid grid-cols-1 gap-2 pt-2">
          <Select
            :value="selectedStatus"
            @update:modelValue="emit('update:selectedStatus', $event)"
          >
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

          <div class="grid grid-cols-2 gap-2">
            <Select
              :value="selectedYear"
              @update:modelValue="emit('update:selectedYear', $event)"
            >
              <SelectTrigger class="w-full">
                <SelectValue placeholder="Year" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="year in uniqueYears" :key="year" :value="year">
                  {{ year === "All" ? "All" : year }}
                </SelectItem>
              </SelectContent>
            </Select>

            <Select
              :value="selectedSemester"
              @update:modelValue="emit('update:selectedSemester', $event)"
            >
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

  <!-- Desktop filters -->
  <div class="hidden lg:grid lg:grid-cols-3 gap-3">
    <Select
      :value="selectedStatus"
      @update:modelValue="emit('update:selectedStatus', $event)"
    >
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

    <Select
      :value="selectedYear"
      @update:modelValue="emit('update:selectedYear', $event)"
    >
      <SelectTrigger class="w-full">
        <SelectValue placeholder="Year" />
      </SelectTrigger>
      <SelectContent>
        <SelectItem v-for="year in uniqueYears" :key="year" :value="year">
          {{ year === "All" ? "All" : year }}
        </SelectItem>
      </SelectContent>
    </Select>

    <Select
      :value="selectedSemester"
      @update:modelValue="emit('update:selectedSemester', $event)"
    >
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
</template>
