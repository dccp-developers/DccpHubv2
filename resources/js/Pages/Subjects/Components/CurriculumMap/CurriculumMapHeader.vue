<script setup>
import { Button } from "@/Components/shadcn/ui/button";
import { Badge } from "@/Components/shadcn/ui/badge";
import { Switch } from "@/Components/shadcn/ui/switch";
import { Label } from "@/Components/shadcn/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/shadcn/ui/select";

const props = defineProps({
  viewMode: {
    type: String,
    required: true,
  },
  showLabels: {
    type: Boolean,
    required: true,
  },
  zoomLevel: {
    type: Number,
    required: true,
  },
  highlightMode: {
    type: String,
    required: true,
  },
});

const emit = defineEmits([
  'update:viewMode',
  'update:showLabels',
  'update:zoomLevel',
  'update:highlightMode',
  'resetView',
]);

const handleZoomIn = () => {
  if (props.zoomLevel < 1.5) {
    emit('update:zoomLevel', props.zoomLevel + 0.1);
  }
};

const handleZoomOut = () => {
  if (props.zoomLevel > 0.5) {
    emit('update:zoomLevel', props.zoomLevel - 0.1);
  }
};

const handleResetView = () => {
  emit('resetView');
};
</script>

<template>
  <div class="space-y-4">
    <!-- Title and description -->
    <div>
      <h2 class="text-xl font-bold">Curriculum Map</h2>
      <p class="text-sm text-muted-foreground">
        Visualize your curriculum and subject prerequisites
      </p>
    </div>

    <!-- Controls and legend -->
    <div class="flex flex-col md:flex-row gap-4 justify-between">
      <!-- Controls -->
      <div class="flex flex-wrap gap-2 items-center">
        <!-- View mode selector -->
        <Select
          :value="viewMode"
          @update:modelValue="$emit('update:viewMode', $event)"
          class="w-[140px]"
        >
          <SelectTrigger>
            <SelectValue placeholder="View Mode" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="flow">Flow View</SelectItem>
            <SelectItem value="grid">Grid View</SelectItem>
            <SelectItem value="year">Year View</SelectItem>
          </SelectContent>
        </Select>

        <!-- Highlight mode selector -->
        <Select
          :value="highlightMode"
          @update:modelValue="$emit('update:highlightMode', $event)"
          class="w-[160px]"
        >
          <SelectTrigger>
            <SelectValue placeholder="Highlight" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="none">No Highlight</SelectItem>
            <SelectItem value="status">Status</SelectItem>
            <SelectItem value="prerequisites">Prerequisites</SelectItem>
            <SelectItem value="corequisites">Co-requisites</SelectItem>
          </SelectContent>
        </Select>

        <!-- Zoom controls -->
        <div class="flex items-center gap-1">
          <Button
            variant="outline"
            size="icon"
            class="h-8 w-8"
            @click="handleZoomOut"
            :disabled="zoomLevel <= 0.5"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span class="sr-only">Zoom out</span>
          </Button>
          
          <span class="text-xs w-12 text-center">{{ Math.round(zoomLevel * 100) }}%</span>
          
          <Button
            variant="outline"
            size="icon"
            class="h-8 w-8"
            @click="handleZoomIn"
            :disabled="zoomLevel >= 1.5"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span class="sr-only">Zoom in</span>
          </Button>
        </div>

        <!-- Reset view button -->
        <Button
          variant="outline"
          size="sm"
          @click="handleResetView"
          class="h-8"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1">
            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
            <path d="M3 3v5h5"></path>
          </svg>
          Reset
        </Button>

        <!-- Show labels toggle -->
        <div class="flex items-center space-x-2">
          <Switch
            id="show-labels"
            :checked="showLabels"
            @update:checked="$emit('update:showLabels', $event)"
          />
          <Label for="show-labels" class="text-xs">Show Labels</Label>
        </div>
      </div>

      <!-- Legend -->
      <div class="flex flex-wrap gap-2 items-center">
        <span class="text-xs text-muted-foreground">Status:</span>
        <Badge variant="success" class="text-xs">Completed</Badge>
        <Badge variant="warning" class="text-xs">Ongoing</Badge>
        <Badge variant="secondary" class="text-xs">Incomplete</Badge>
        
        <span class="text-xs text-muted-foreground ml-2">Lines:</span>
        <div class="flex items-center gap-1">
          <div class="w-4 h-0.5 bg-blue-500"></div>
          <span class="text-xs">Prerequisite</span>
        </div>
        <div class="flex items-center gap-1">
          <div class="w-4 h-0.5 bg-purple-500 border-dashed border-t border-purple-500"></div>
          <span class="text-xs">Co-requisite</span>
        </div>
      </div>
    </div>
  </div>
</template>
