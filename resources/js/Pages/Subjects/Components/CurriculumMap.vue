<script setup>
import CurriculumMapComponent from './CurriculumMap/index.vue';

const props = defineProps({
  allSubjects: {
    type: Array,
    required: true,
  },
});
</script>

<template>
  <CurriculumMapComponent :allSubjects="allSubjects" />
</template>
