<script setup>
import { Card, CardContent } from '@/Components/shadcn/ui/card';
import { computed } from 'vue';
import { Icon } from '@iconify/vue';

const props = defineProps({
  tuition: {
    type: Object,
    required: true,
  },
  student: {
    type: Object,
    required: true
  },
  formatCurrency: {
    type: Function,
    required: true
  },
  formattedDueDate: {
    type: String,
    required: true
  }
});

const statusVariant = computed(() => {
  if (!props.tuition) return 'default';
  if (props.tuition.status === 'paid') return 'success';
  const today = new Date();
  const dueDate = props.tuition.due_date ? new Date(props.tuition.due_date) : null;
  if (dueDate && dueDate < today && props.tuition.total_balance > 0) { return 'destructive'; }
  return 'warning';
});

const statusText = computed(() => {
  if (!props.tuition) return 'Unknown';
  if (props.tuition.status === 'paid') return 'Paid';
  const today = new Date();
  const dueDate = props.tuition.due_date ? new Date(props.tuition.due_date) : null;
  if (dueDate && dueDate < today && props.tuition.total_balance > 0) { return 'Overdue'; }
  return 'Pending';
});

const statusIcon = computed(() => {
  if (!props.tuition) return 'mdi:alert-circle-outline';
  if (props.tuition.status === 'paid') return 'mdi:check-circle-outline';
  const today = new Date();
  const dueDate = props.tuition.due_date ? new Date(props.tuition.due_date) : null;
  if (dueDate && dueDate < today && props.tuition.total_balance > 0) { return 'mdi:alert-circle-outline'; }
  return 'mdi:clock-outline';
});

const paymentProgress = computed(() => {
  if (!props.tuition) return 0;
  const paidAmount = props.tuition.overall_tuition - props.tuition.total_balance;
  return Math.max(0, Math.min(100, (paidAmount / props.tuition.overall_tuition) * 100));
});

const totalPaid = computed(() => {
  if (!props.tuition) return 0;
  return props.tuition.overall_tuition - props.tuition.total_balance;
});

const progressColor = computed(() => {
  const progress = paymentProgress.value;
  if (progress >= 100) return 'bg-green-500 dark:bg-green-600';
  if (progress >= 75) return 'bg-emerald-500 dark:bg-emerald-600';
  if (progress >= 50) return 'bg-amber-500 dark:bg-amber-600';
  if (progress >= 25) return 'bg-orange-500 dark:bg-orange-600';
  return 'bg-red-500 dark:bg-red-600';
});

const daysUntilDue = computed(() => {
  if (!props.tuition || !props.tuition.due_date) return null;
  const today = new Date();
  const dueDate = new Date(props.tuition.due_date);
  const diffTime = dueDate - today;
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  return diffDays;
});
</script>

<template>
  <Card class="overflow-hidden border-0 shadow-md">
    <div class="bg-gradient-to-r from-primary/90 to-primary p-6 text-primary-foreground">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h2 class="text-xl font-bold">Payment Summary</h2>
          <p class="text-primary-foreground/80">{{ tuition.school_year }} - Semester {{ tuition.semester }}</p>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 bg-primary-foreground/20 backdrop-blur-sm rounded-full">
          <Icon :icon="statusIcon" class="w-5 h-5" />
          <span class="font-medium">Status: {{ statusText }}</span>
        </div>
      </div>
    </div>

    <CardContent class="p-6">
      <!-- Student Info -->
      <div class="mb-6">
        <div class="flex flex-col sm:flex-row justify-between gap-4">
          <div>
            <p class="text-sm text-muted-foreground">Student</p>
            <p class="text-lg font-medium">{{ student.first_name }} {{ student.middle_name }} {{ student.last_name }}</p>
            <p class="text-sm text-muted-foreground">ID: {{ student.id }}</p>
          </div>
          <div>
            <p class="text-sm text-muted-foreground">Due Date</p>
            <p class="text-lg font-medium">{{ formattedDueDate }}</p>
            <p v-if="daysUntilDue !== null" class="text-sm"
               :class="daysUntilDue < 0 ? 'text-destructive' : daysUntilDue <= 7 ? 'text-amber-500 dark:text-amber-400' : 'text-muted-foreground'">
              {{ daysUntilDue < 0 ? `${Math.abs(daysUntilDue)} days overdue` : daysUntilDue === 0 ? 'Due today' : `${daysUntilDue} days remaining` }}
            </p>
          </div>
        </div>
      </div>

      <!-- Payment Progress -->
      <div class="mb-6">
        <div class="flex justify-between items-center mb-2">
          <p class="text-sm font-medium">Payment Progress</p>
          <p class="text-sm font-medium">{{ paymentProgress.toFixed(0) }}%</p>
        </div>
        <div class="h-3 w-full bg-muted rounded-full overflow-hidden">
          <div :class="['h-full rounded-full transition-all duration-500', progressColor]" :style="{ width: `${paymentProgress}%` }"></div>
        </div>
      </div>

      <!-- Payment Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-muted/50 p-4 rounded-lg">
          <p class="text-sm text-muted-foreground mb-1">Total Paid</p>
          <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ formatCurrency(totalPaid) }}</p>
        </div>
        <div class="bg-muted/50 p-4 rounded-lg">
          <p class="text-sm text-muted-foreground mb-1">Remaining Balance</p>
          <p class="text-xl font-bold text-red-500 dark:text-red-400">{{ formatCurrency(tuition.total_balance) }}</p>
        </div>
        <div class="bg-muted/50 p-4 rounded-lg">
          <p class="text-sm text-muted-foreground mb-1">Overall Tuition</p>
          <p class="text-xl font-bold">{{ formatCurrency(tuition.overall_tuition) }}</p>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
