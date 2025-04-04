<script setup>
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/shadcn/ui/card';
import { Icon } from '@iconify/vue';
import { computed } from 'vue';

const props = defineProps({
  tuition: {
    type: Object,
    required: true,
  },
  formatCurrency: {
    type: Function,
    required: true
  }
});

const totalPaid = computed(() => {
  if (!props.tuition) return 0;
  return props.tuition.overall_tuition - props.tuition.total_balance;
});

// Group fees into categories for better organization
const feeCategories = [
  {
    name: 'Tuition Fees',
    icon: 'mdi:school-outline',
    items: [
      { label: 'Total Tuition', value: props.tuition.total_tuition },
      { label: 'Lectures', value: props.tuition.total_lectures },
      { label: 'Laboratory', value: props.tuition.total_laboratory },
    ]
  },
  {
    name: 'Other Fees',
    icon: 'mdi:cash-multiple-outline',
    items: [
      { label: 'Miscellaneous', value: props.tuition.total_miscelaneous_fees },
    ]
  },
  {
    name: 'Adjustments',
    icon: 'mdi:calculator-variant-outline',
    items: [
      { label: 'Discount', value: props.tuition.discount },
      { label: 'Downpayment', value: props.tuition.downpayment },
    ]
  },
  {
    name: 'Summary',
    icon: 'mdi:file-document-outline',
    items: [
      { label: 'Overall Tuition', value: props.tuition.overall_tuition, isBold: true },
      { label: 'Total Paid', value: totalPaid.value, isBold: true, isPositive: true },
      { label: 'Balance', value: props.tuition.total_balance, isBold: true, isNegative: true },
    ]
  }
];
</script>

<template>
  <Card class="shadow-sm">
    <CardHeader class="pb-3 border-b">
      <CardTitle class="flex items-center gap-2">
        <Icon icon="mdi:cash-register" class="w-5 h-5" />
        Tuition Breakdown
      </CardTitle>
    </CardHeader>
    <CardContent class="p-0">
      <div class="divide-y">
        <div v-for="(category, index) in feeCategories" :key="index" class="p-4">
          <div class="flex items-center gap-2 mb-3">
            <Icon :icon="category.icon" class="w-5 h-5 text-primary" />
            <h3 class="font-medium">{{ category.name }}</h3>
          </div>

          <div class="space-y-2">
            <div v-for="(item, itemIndex) in category.items" :key="itemIndex"
                 class="flex justify-between items-center py-1">
              <span :class="{ 'font-semibold': item.isBold }">{{ item.label }}</span>
              <span :class="{
                'font-semibold': item.isBold,
                'text-green-600 dark:text-green-400': item.isPositive,
                'text-red-500 dark:text-red-400': item.isNegative
              }">{{ formatCurrency(item.value) }}</span>
            </div>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
