<script setup>
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/shadcn/ui/card';
import { Progress } from '@/Components/shadcn/ui/progress';
import { Badge } from '@/Components/shadcn/ui/badge';
import { Icon } from '@iconify/vue';

const props = defineProps({
  stats: {
    type: Array,
    required: true,
  },
  recentGrades: {
    type: Array,
    default: () => [],
  },
});

// Calculate semester progress (assuming we're in the middle of semester)
const semesterProgress = computed(() => {
  const now = new Date();
  const currentMonth = now.getMonth();
  
  // Rough calculation based on typical semester months
  // First semester: August (7) to December (11)
  // Second semester: January (0) to May (4)
  
  if (currentMonth >= 7 && currentMonth <= 11) {
    // First semester
    const startMonth = 7; // August
    const endMonth = 11; // December
    const progress = ((currentMonth - startMonth) / (endMonth - startMonth)) * 100;
    return Math.min(Math.max(progress, 0), 100);
  } else if (currentMonth >= 0 && currentMonth <= 4) {
    // Second semester
    const startMonth = 0; // January
    const endMonth = 4; // May
    const progress = ((currentMonth - startMonth) / (endMonth - startMonth)) * 100;
    return Math.min(Math.max(progress, 0), 100);
  }
  
  return 50; // Default fallback
});

// Get GPA from stats
const gpa = computed(() => {
  const gpastat = props.stats.find(stat => stat.label === 'GPA');
  return gpastat ? gpastat.value : 'N/A';
});

// Get attendance from stats
const attendance = computed(() => {
  const attendanceStat = props.stats.find(stat => stat.label === 'Attendance');
  return attendanceStat ? parseFloat(attendanceStat.value) : 0;
});

// Get enrolled units from stats
const enrolledUnits = computed(() => {
  const unitsStat = props.stats.find(stat => stat.label === 'Enrolled Units');
  return unitsStat ? unitsStat.value : '0';
});

// Get enrolled classes from stats
const enrolledClasses = computed(() => {
  const classesStat = props.stats.find(stat => stat.label === 'Enrolled Classes');
  return classesStat ? classesStat.value : '0';
});

// Calculate GPA status
const gpaStatus = computed(() => {
  const gpaValue = parseFloat(gpa.value);
  if (isNaN(gpaValue)) return { text: 'No Data', color: 'text-gray-500', variant: 'secondary' };
  if (gpaValue >= 3.5) return { text: 'Excellent', color: 'text-green-600', variant: 'success' };
  if (gpaValue >= 3.0) return { text: 'Good', color: 'text-blue-600', variant: 'default' };
  if (gpaValue >= 2.5) return { text: 'Fair', color: 'text-yellow-600', variant: 'warning' };
  return { text: 'Needs Improvement', color: 'text-red-600', variant: 'destructive' };
});

// Calculate attendance status
const attendanceStatus = computed(() => {
  if (attendance.value >= 95) return { text: 'Excellent', color: 'text-green-600', variant: 'success' };
  if (attendance.value >= 85) return { text: 'Good', color: 'text-blue-600', variant: 'default' };
  if (attendance.value >= 75) return { text: 'Fair', color: 'text-yellow-600', variant: 'warning' };
  return { text: 'Poor', color: 'text-red-600', variant: 'destructive' };
});

// Recent performance trend
const performanceTrend = computed(() => {
  // Ensure recentGrades is an array and has data
  if (!Array.isArray(props.recentGrades) || props.recentGrades.length < 2) {
    return { trend: 'stable', icon: 'lucide:minus', color: 'text-gray-500' };
  }

  const recent = props.recentGrades.slice(0, 3);
  const grades = recent.map(grade => parseFloat(grade.grade) || 0).filter(grade => grade > 0);

  if (grades.length < 2) return { trend: 'stable', icon: 'lucide:minus', color: 'text-gray-500' };

  const avg1 = grades.slice(0, Math.ceil(grades.length / 2)).reduce((a, b) => a + b, 0) / Math.ceil(grades.length / 2);
  const avg2 = grades.slice(Math.ceil(grades.length / 2)).reduce((a, b) => a + b, 0) / Math.floor(grades.length / 2);

  if (avg1 > avg2 + 0.1) return { trend: 'improving', icon: 'lucide:trending-up', color: 'text-green-600' };
  if (avg2 > avg1 + 0.1) return { trend: 'declining', icon: 'lucide:trending-down', color: 'text-red-600' };
  return { trend: 'stable', icon: 'lucide:minus', color: 'text-gray-500' };
});
</script>

<template>
  <Card>
    <CardHeader class="pb-4">
      <CardTitle class="text-lg md:text-xl flex items-center">
        <Icon icon="lucide:trending-up" class="w-5 h-5 mr-2 text-primary" />
        Academic Progress
      </CardTitle>
    </CardHeader>
    <CardContent class="space-y-6">
      <!-- Semester Progress -->
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-sm">Semester Progress</h3>
          <span class="text-sm text-muted-foreground">{{ Math.round(semesterProgress) }}%</span>
        </div>
        <Progress :model-value="semesterProgress" class="h-2" />
        <p class="text-xs text-muted-foreground">
          Keep up the great work! You're making steady progress this semester.
        </p>
      </div>

      <!-- Academic Metrics Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- GPA -->
        <div class="text-center p-3 rounded-lg bg-muted/30">
          <div class="flex items-center justify-center mb-2">
            <Icon icon="lucide:award" class="w-5 h-5 text-primary mr-1" />
            <span class="text-xs font-medium text-muted-foreground">GPA</span>
          </div>
          <div class="text-lg font-bold">{{ gpa }}</div>
          <Badge :variant="gpaStatus.variant" class="text-xs mt-1">
            {{ gpaStatus.text }}
          </Badge>
        </div>

        <!-- Attendance -->
        <div class="text-center p-3 rounded-lg bg-muted/30">
          <div class="flex items-center justify-center mb-2">
            <Icon icon="lucide:user-check" class="w-5 h-5 text-primary mr-1" />
            <span class="text-xs font-medium text-muted-foreground">Attendance</span>
          </div>
          <div class="text-lg font-bold">{{ attendance }}%</div>
          <Badge :variant="attendanceStatus.variant" class="text-xs mt-1">
            {{ attendanceStatus.text }}
          </Badge>
        </div>

        <!-- Units -->
        <div class="text-center p-3 rounded-lg bg-muted/30">
          <div class="flex items-center justify-center mb-2">
            <Icon icon="lucide:book" class="w-5 h-5 text-primary mr-1" />
            <span class="text-xs font-medium text-muted-foreground">Units</span>
          </div>
          <div class="text-lg font-bold">{{ enrolledUnits }}</div>
          <div class="text-xs text-muted-foreground mt-1">Enrolled</div>
        </div>

        <!-- Classes -->
        <div class="text-center p-3 rounded-lg bg-muted/30">
          <div class="flex items-center justify-center mb-2">
            <Icon icon="lucide:graduation-cap" class="w-5 h-5 text-primary mr-1" />
            <span class="text-xs font-medium text-muted-foreground">Classes</span>
          </div>
          <div class="text-lg font-bold">{{ enrolledClasses }}</div>
          <div class="text-xs text-muted-foreground mt-1">Enrolled</div>
        </div>
      </div>

      <!-- Performance Trend -->
      <div class="flex items-center justify-between p-3 rounded-lg bg-muted/30">
        <div class="flex items-center">
          <Icon :icon="performanceTrend.icon" :class="`w-5 h-5 mr-2 ${performanceTrend.color}`" />
          <div>
            <h4 class="font-medium text-sm">Performance Trend</h4>
            <p class="text-xs text-muted-foreground">Based on recent grades</p>
          </div>
        </div>
        <Badge :variant="performanceTrend.trend === 'improving' ? 'success' : performanceTrend.trend === 'declining' ? 'destructive' : 'secondary'" class="text-xs">
          {{ performanceTrend.trend.charAt(0).toUpperCase() + performanceTrend.trend.slice(1) }}
        </Badge>
      </div>
    </CardContent>
  </Card>
</template>
