<template>
  <div class="w-full">
    <!-- Chart Container -->
    <div class="relative">
      <!-- Chart Type Selector -->
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">{{ title }}</h3>
        <div class="flex items-center space-x-2">
          <Select v-model="chartType" @update:value="updateChart">
            <SelectTrigger class="w-32">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="bar">Bar Chart</SelectItem>
              <SelectItem value="line">Line Chart</SelectItem>
              <SelectItem value="pie">Pie Chart</SelectItem>
              <SelectItem value="area">Area Chart</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <!-- Chart Display -->
      <div class="bg-white rounded-lg border p-4" :style="{ height: chartHeight + 'px' }">
        <!-- Bar Chart -->
        <div v-if="chartType === 'bar'" class="h-full flex items-end justify-between space-x-2">
          <div v-for="(item, index) in chartData" :key="index" 
               class="flex-1 flex flex-col items-center">
            <div 
              class="bg-primary rounded-t w-full transition-all duration-500 mb-2"
              :style="{ 
                height: `${Math.max((item.value / maxValue) * (chartHeight - 60), 10)}px`,
                backgroundColor: getBarColor(item.value, maxValue)
              }"
            ></div>
            <div class="text-center">
              <p class="text-xs text-muted-foreground truncate">{{ item.label }}</p>
              <p class="text-sm font-medium">{{ formatValue(item.value) }}</p>
            </div>
          </div>
        </div>

        <!-- Line Chart -->
        <div v-if="chartType === 'line'" class="h-full relative">
          <svg :width="chartWidth" :height="chartHeight - 40" class="overflow-visible">
            <!-- Grid lines -->
            <g class="grid-lines">
              <line v-for="i in 5" :key="`grid-${i}`"
                    :x1="0" :y1="(i * (chartHeight - 40)) / 5"
                    :x2="chartWidth" :y2="(i * (chartHeight - 40)) / 5"
                    stroke="#e5e7eb" stroke-width="1" />
            </g>
            
            <!-- Line path -->
            <path :d="linePath" fill="none" stroke="#3b82f6" stroke-width="2" />
            
            <!-- Data points -->
            <circle v-for="(point, index) in linePoints" :key="`point-${index}`"
                    :cx="point.x" :cy="point.y" r="4"
                    fill="#3b82f6" stroke="white" stroke-width="2" />
          </svg>
          
          <!-- X-axis labels -->
          <div class="flex justify-between mt-2">
            <span v-for="(item, index) in chartData" :key="`label-${index}`"
                  class="text-xs text-muted-foreground">
              {{ item.label }}
            </span>
          </div>
        </div>

        <!-- Pie Chart -->
        <div v-if="chartType === 'pie'" class="h-full flex items-center justify-center">
          <div class="relative">
            <svg :width="pieSize" :height="pieSize" class="transform -rotate-90">
              <circle v-for="(segment, index) in pieSegments" :key="`segment-${index}`"
                      :cx="pieSize / 2" :cy="pieSize / 2" :r="pieRadius"
                      fill="none" :stroke="segment.color" :stroke-width="pieStrokeWidth"
                      :stroke-dasharray="segment.dashArray"
                      :stroke-dashoffset="segment.dashOffset"
                      class="transition-all duration-500" />
            </svg>
            
            <!-- Legend -->
            <div class="absolute top-full left-0 right-0 mt-4">
              <div class="grid grid-cols-2 gap-2">
                <div v-for="(item, index) in chartData" :key="`legend-${index}`"
                     class="flex items-center space-x-2">
                  <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: getPieColor(index) }"></div>
                  <span class="text-xs">{{ item.label }}: {{ formatValue(item.value) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Area Chart -->
        <div v-if="chartType === 'area'" class="h-full relative">
          <svg :width="chartWidth" :height="chartHeight - 40" class="overflow-visible">
            <!-- Grid lines -->
            <g class="grid-lines">
              <line v-for="i in 5" :key="`grid-${i}`"
                    :x1="0" :y1="(i * (chartHeight - 40)) / 5"
                    :x2="chartWidth" :y2="(i * (chartHeight - 40)) / 5"
                    stroke="#e5e7eb" stroke-width="1" />
            </g>
            
            <!-- Area path -->
            <path :d="areaPath" fill="rgba(59, 130, 246, 0.2)" stroke="#3b82f6" stroke-width="2" />
            
            <!-- Data points -->
            <circle v-for="(point, index) in linePoints" :key="`point-${index}`"
                    :cx="point.x" :cy="point.y" r="3"
                    fill="#3b82f6" stroke="white" stroke-width="2" />
          </svg>
          
          <!-- X-axis labels -->
          <div class="flex justify-between mt-2">
            <span v-for="(item, index) in chartData" :key="`label-${index}`"
                  class="text-xs text-muted-foreground">
              {{ item.label }}
            </span>
          </div>
        </div>

        <!-- No Data State -->
        <div v-if="!chartData || chartData.length === 0" 
             class="h-full flex items-center justify-center">
          <div class="text-center">
            <Icon icon="heroicons:chart-bar" class="h-12 w-12 text-muted-foreground mx-auto mb-2" />
            <p class="text-muted-foreground">No data available</p>
          </div>
        </div>
      </div>

      <!-- Chart Statistics -->
      <div v-if="showStats && chartData.length > 0" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="text-center p-2 bg-muted rounded-lg">
          <p class="text-sm font-medium">{{ formatValue(maxValue) }}</p>
          <p class="text-xs text-muted-foreground">Maximum</p>
        </div>
        <div class="text-center p-2 bg-muted rounded-lg">
          <p class="text-sm font-medium">{{ formatValue(minValue) }}</p>
          <p class="text-xs text-muted-foreground">Minimum</p>
        </div>
        <div class="text-center p-2 bg-muted rounded-lg">
          <p class="text-sm font-medium">{{ formatValue(averageValue) }}</p>
          <p class="text-xs text-muted-foreground">Average</p>
        </div>
        <div class="text-center p-2 bg-muted rounded-lg">
          <p class="text-sm font-medium">{{ chartData.length }}</p>
          <p class="text-xs text-muted-foreground">Data Points</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/shadcn/ui/select'
import { Icon } from '@iconify/vue'

const props = defineProps({
  title: {
    type: String,
    default: 'Chart'
  },
  data: {
    type: Array,
    default: () => []
  },
  type: {
    type: String,
    default: 'bar'
  },
  height: {
    type: Number,
    default: 300
  },
  showStats: {
    type: Boolean,
    default: true
  },
  valueType: {
    type: String,
    default: 'number' // 'number', 'percentage'
  }
})

const emit = defineEmits(['chart-type-changed'])

const chartType = ref(props.type)
const chartHeight = ref(props.height)
const chartWidth = ref(400)

// Chart data processing
const chartData = computed(() => {
  return props.data.map(item => ({
    label: item.label || item.name || 'Unknown',
    value: item.value || item.count || 0
  }))
})

const maxValue = computed(() => {
  return chartData.value.length > 0 ? Math.max(...chartData.value.map(item => item.value)) : 0
})

const minValue = computed(() => {
  return chartData.value.length > 0 ? Math.min(...chartData.value.map(item => item.value)) : 0
})

const averageValue = computed(() => {
  if (chartData.value.length === 0) return 0
  const sum = chartData.value.reduce((acc, item) => acc + item.value, 0)
  return sum / chartData.value.length
})

// Line chart calculations
const linePoints = computed(() => {
  if (chartData.value.length === 0) return []
  
  const points = []
  const stepX = chartWidth.value / (chartData.value.length - 1 || 1)
  
  chartData.value.forEach((item, index) => {
    const x = index * stepX
    const y = (chartHeight.value - 40) - ((item.value / maxValue.value) * (chartHeight.value - 40))
    points.push({ x, y })
  })
  
  return points
})

const linePath = computed(() => {
  if (linePoints.value.length === 0) return ''
  
  let path = `M ${linePoints.value[0].x} ${linePoints.value[0].y}`
  for (let i = 1; i < linePoints.value.length; i++) {
    path += ` L ${linePoints.value[i].x} ${linePoints.value[i].y}`
  }
  
  return path
})

const areaPath = computed(() => {
  if (linePoints.value.length === 0) return ''
  
  let path = `M ${linePoints.value[0].x} ${chartHeight.value - 40}`
  path += ` L ${linePoints.value[0].x} ${linePoints.value[0].y}`
  
  for (let i = 1; i < linePoints.value.length; i++) {
    path += ` L ${linePoints.value[i].x} ${linePoints.value[i].y}`
  }
  
  path += ` L ${linePoints.value[linePoints.value.length - 1].x} ${chartHeight.value - 40}`
  path += ' Z'
  
  return path
})

// Pie chart calculations
const pieSize = computed(() => Math.min(chartHeight.value - 80, 200))
const pieRadius = computed(() => (pieSize.value - 40) / 2)
const pieStrokeWidth = computed(() => 30)

const pieSegments = computed(() => {
  if (chartData.value.length === 0) return []
  
  const total = chartData.value.reduce((sum, item) => sum + item.value, 0)
  const circumference = 2 * Math.PI * pieRadius.value
  let currentOffset = 0
  
  return chartData.value.map((item, index) => {
    const percentage = item.value / total
    const dashArray = `${percentage * circumference} ${circumference}`
    const dashOffset = -currentOffset * circumference
    
    currentOffset += percentage
    
    return {
      dashArray,
      dashOffset,
      color: getPieColor(index)
    }
  })
})

const getBarColor = (value, max) => {
  const percentage = value / max
  if (percentage >= 0.9) return '#10b981' // green
  if (percentage >= 0.75) return '#3b82f6' // blue
  if (percentage >= 0.5) return '#f59e0b' // yellow
  return '#ef4444' // red
}

const getPieColor = (index) => {
  const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4']
  return colors[index % colors.length]
}

const formatValue = (value) => {
  if (props.valueType === 'percentage') {
    return `${Math.round(value)}%`
  }
  return Math.round(value).toString()
}

const updateChart = () => {
  emit('chart-type-changed', chartType.value)
}

// Responsive chart width
onMounted(() => {
  const updateWidth = () => {
    const container = document.querySelector('.chart-container')
    if (container) {
      chartWidth.value = container.offsetWidth - 32 // Account for padding
    }
  }
  
  updateWidth()
  window.addEventListener('resize', updateWidth)
})

watch(() => props.type, (newType) => {
  chartType.value = newType
})
</script>

<style scoped>
.chart-container {
  width: 100%;
}
</style>
