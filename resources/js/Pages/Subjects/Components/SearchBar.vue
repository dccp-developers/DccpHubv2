<script setup>
import { Input } from "@/Components/shadcn/ui/input";
import { Button } from "@/Components/shadcn/ui/button";

const props = defineProps({
  searchQuery: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(["update:searchQuery"]);

const updateSearch = (value) => {
  emit("update:searchQuery", value);
};

const clearSearch = () => {
  emit("update:searchQuery", "");
};
</script>

<template>
  <div class="relative w-full sm:w-auto sm:min-w-[250px]">
    <Input
      :value="searchQuery"
      @input="updateSearch($event.target.value)"
      type="text"
      placeholder="Search subjects..."
      class="w-full pr-8"
    />
    <Button
      variant="ghost"
      size="icon"
      class="absolute right-0 top-0 h-full"
      v-if="searchQuery"
      @click="clearSearch"
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
</template>
