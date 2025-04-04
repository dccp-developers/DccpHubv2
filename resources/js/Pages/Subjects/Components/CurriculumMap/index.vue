<script setup>
import { ref } from 'vue';
import CurriculumMapHeader from './CurriculumMapHeader.vue';
import CurriculumMapView from './CurriculumMapView.vue';

const props = defineProps({
  allSubjects: {
    type: Array,
    required: true,
  },
});

// State
const viewMode = ref('flow'); // flow, grid, year
const showLabels = ref(true);
const zoomLevel = ref(1);
const highlightMode = ref('prerequisites'); // none, status, prerequisites, corequisites

// Reset view to defaults
const resetView = () => {
  viewMode.value = 'flow';
  showLabels.value = true;
  zoomLevel.value = 1;
  highlightMode.value = 'prerequisites';
};
</script>

<template>
  <div class="space-y-6">
    <!-- Header with controls -->
    <CurriculumMapHeader
      v-model:viewMode="viewMode"
      v-model:showLabels="showLabels"
      v-model:zoomLevel="zoomLevel"
      v-model:highlightMode="highlightMode"
      @resetView="resetView"
    />
    
    <!-- Main curriculum map view -->
    <CurriculumMapView
      :subjects="allSubjects"
      :viewMode="viewMode"
      :showLabels="showLabels"
      :zoomLevel="zoomLevel"
      :highlightMode="highlightMode"
    />
    
    <!-- Mobile instructions -->
    <div class="lg:hidden text-sm text-muted-foreground bg-muted/30 p-4 rounded-md">
      <p class="font-medium mb-2">Tips for mobile users:</p>
      <ul class="list-disc pl-5 space-y-1">
        <li>Tap on a subject to view details</li>
        <li>Use the view mode selector to change how subjects are displayed</li>
        <li>Try the "Grid View" for a more compact layout on small screens</li>
        <li>Pinch to zoom in/out on the curriculum map</li>
      </ul>
    </div>
  </div>
</template>
