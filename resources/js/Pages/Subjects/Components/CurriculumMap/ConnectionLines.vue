<script setup>
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
  connections: {
    type: Array,
    required: true,
  },
  nodePositions: {
    type: Object,
    required: true,
  },
  highlightedConnections: {
    type: Array,
    default: () => [],
  },
  zoomLevel: {
    type: Number,
    default: 1,
  },
  viewMode: {
    type: String,
    default: 'flow',
  },
});

const svgContainer = ref(null);
const svgWidth = ref(1000);
const svgHeight = ref(1000);

// Update SVG dimensions when container size changes
const updateSvgDimensions = () => {
  if (!svgContainer.value) return;
  
  const container = svgContainer.value.parentElement;
  if (container) {
    svgWidth.value = container.offsetWidth;
    svgHeight.value = container.offsetHeight;
  }
};

// Generate path between two nodes
const generatePath = (from, to, type = 'prerequisite') => {
  if (!from || !to) return '';
  
  // Calculate center points
  const fromX = from.x + from.width / 2;
  const fromY = from.y + from.height;
  const toX = to.x + to.width / 2;
  const toY = to.y;
  
  // Calculate control points for curve
  // For prerequisites (going upward in the curriculum)
  if (type === 'prerequisite') {
    const midY = (fromY + toY) / 2;
    return `M ${fromX} ${fromY} C ${fromX} ${midY}, ${toX} ${midY}, ${toX} ${toY}`;
  } 
  // For corequisites (subjects in the same semester)
  else if (type === 'corequisite') {
    const midX = (fromX + toX) / 2;
    const controlY = Math.min(fromY, toY) - 20; // Control point above both nodes
    return `M ${fromX} ${fromY - from.height/2} Q ${midX} ${controlY}, ${toX} ${toY - to.height/2}`;
  }
};

// Generate all paths
const paths = computed(() => {
  return props.connections.map(connection => {
    const fromNode = props.nodePositions[connection.from];
    const toNode = props.nodePositions[connection.to];
    
    if (!fromNode || !toNode) return null;
    
    const isHighlighted = props.highlightedConnections.some(
      c => c.from === connection.from && c.to === connection.to
    );
    
    return {
      id: `${connection.from}-${connection.to}`,
      path: generatePath(fromNode, toNode, connection.type),
      type: connection.type || 'prerequisite',
      isHighlighted,
      from: connection.from,
      to: connection.to
    };
  }).filter(Boolean);
});

// Get path style based on type and highlight status
const getPathStyle = (path) => {
  const baseStyle = {
    'stroke-width': path.isHighlighted ? '2.5px' : '1.5px',
    'transition': 'all 0.3s ease',
  };
  
  if (path.type === 'prerequisite') {
    return {
      ...baseStyle,
      'stroke': path.isHighlighted ? 'var(--blue-600)' : 'var(--blue-500)',
      'stroke-dasharray': '0',
    };
  } else if (path.type === 'corequisite') {
    return {
      ...baseStyle,
      'stroke': path.isHighlighted ? 'var(--purple-600)' : 'var(--purple-500)',
      'stroke-dasharray': '5,5',
    };
  }
  
  return baseStyle;
};

// Watch for changes in container or zoom level
watch(() => props.zoomLevel, updateSvgDimensions);
watch(() => props.viewMode, updateSvgDimensions);

onMounted(() => {
  updateSvgDimensions();
  window.addEventListener('resize', updateSvgDimensions);
});
</script>

<template>
  <div ref="svgContainer" class="absolute inset-0 pointer-events-none z-0">
    <svg :width="svgWidth" :height="svgHeight" class="absolute top-0 left-0">
      <!-- Draw all connection paths -->
      <g>
        <path
          v-for="path in paths"
          :key="path.id"
          :d="path.path"
          fill="none"
          :style="getPathStyle(path)"
          :class="[
            path.isHighlighted ? 'opacity-100' : 'opacity-70',
          ]"
        />
        
        <!-- Draw arrowheads for prerequisites -->
        <marker
          id="arrowhead"
          markerWidth="10"
          markerHeight="7"
          refX="9"
          refY="3.5"
          orient="auto"
        >
          <polygon points="0 0, 10 3.5, 0 7" fill="var(--blue-500)" />
        </marker>
        
        <!-- Draw arrowheads for corequisites -->
        <marker
          id="corequisite-arrowhead"
          markerWidth="10"
          markerHeight="7"
          refX="9"
          refY="3.5"
          orient="auto"
        >
          <polygon points="0 0, 10 3.5, 0 7" fill="var(--purple-500)" />
        </marker>
      </g>
    </svg>
  </div>
</template>
