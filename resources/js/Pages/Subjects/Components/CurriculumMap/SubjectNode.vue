<script setup>
import { computed } from "vue";
import { Badge } from "@/Components/shadcn/ui/badge";
import { Progress } from "@/Components/shadcn/ui/progress";

const props = defineProps({
  subject: {
    type: Object,
    required: true,
  },
  showLabels: {
    type: Boolean,
    default: true,
  },
  isHighlighted: {
    type: Boolean,
    default: false,
  },
  isPrerequisite: {
    type: Boolean,
    default: false,
  },
  isPostrequisite: {
    type: Boolean,
    default: false,
  },
  isCorequisite: {
    type: Boolean,
    default: false,
  },
  viewMode: {
    type: String,
    default: "flow",
  },
});

const emit = defineEmits(['click', 'mouseenter', 'mouseleave']);

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

const nodeClasses = computed(() => {
  const baseClasses = [
    'border rounded-md overflow-hidden transition-all',
    'hover:shadow-md cursor-pointer relative',
    getStatusColor(props.subject.status),
  ];
  
  // Add highlight classes
  if (props.isHighlighted) {
    baseClasses.push('ring-2 ring-primary ring-offset-2');
  }
  
  // Add relationship highlight classes
  if (props.isPrerequisite) {
    baseClasses.push('ring-2 ring-blue-500 ring-offset-1');
  }
  
  if (props.isPostrequisite) {
    baseClasses.push('ring-2 ring-purple-500 ring-offset-1');
  }
  
  if (props.isCorequisite) {
    baseClasses.push('ring-2 ring-orange-500 ring-offset-1');
  }
  
  // Add opacity for incomplete subjects in certain view modes
  if (props.subject.status === 'Incomplete' && props.viewMode !== 'grid') {
    baseClasses.push('opacity-70');
  }
  
  return baseClasses;
});

const handleClick = () => {
  emit('click', props.subject);
};

const handleMouseEnter = () => {
  emit('mouseenter', props.subject);
};

const handleMouseLeave = () => {
  emit('mouseleave', props.subject);
};
</script>

<template>
  <div 
    :class="nodeClasses"
    @click="handleClick"
    @mouseenter="handleMouseEnter"
    @mouseleave="handleMouseLeave"
    :id="`subject-${subject.code}`"
  >
    <!-- Compact view for flow mode -->
    <div v-if="viewMode === 'flow'" class="p-2 w-[120px]">
      <div class="flex flex-col items-center text-center">
        <Badge :variant="getStatusBadgeVariant(subject.status)" class="mb-1 text-xs">
          {{ subject.status }}
        </Badge>
        <p class="font-medium text-sm">{{ subject.code }}</p>
        <p v-if="showLabels" class="text-xs line-clamp-2 mt-1 text-muted-foreground">
          {{ subject.title }}
        </p>
        <div class="w-full mt-2">
          <Progress :value="getProgressValue(subject.status)" class="h-1" />
        </div>
      </div>
    </div>
    
    <!-- Standard view for grid and year modes -->
    <div v-else class="w-full">
      <div class="h-1.5" :class="subject.status === 'Completed' ? 'bg-green-500' : subject.status === 'Ongoing' ? 'bg-yellow-500' : 'bg-slate-300'"></div>
      <div class="p-3">
        <div class="flex justify-between items-start">
          <p class="font-medium text-sm">{{ subject.code }}</p>
          <Badge :variant="getStatusBadgeVariant(subject.status)" class="text-xs">
            {{ subject.status }}
          </Badge>
        </div>
        <p v-if="showLabels" class="text-xs line-clamp-2 mt-1 text-muted-foreground">
          {{ subject.title }}
        </p>
        <div class="flex justify-between text-xs mt-2 text-muted-foreground">
          <span>{{ subject.units }} units</span>
          <span v-if="subject.grade">Grade: {{ subject.grade }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
