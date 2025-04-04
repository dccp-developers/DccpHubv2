<script setup>
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/shadcn/ui/card';
import { Badge } from '@/Components/shadcn/ui/badge';
import { Icon } from '@iconify/vue';
import { format } from 'date-fns';
import { ref, computed } from 'vue';
import { Input } from '@/Components/shadcn/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/Components/shadcn/ui/select';

const props = defineProps({
  transactions: {
    type: Array,
    required: true,
  },
  formatCurrency: {
    type: Function,
    required: true
  }
});

const emit = defineEmits(['view-transaction']);

// Refs for filtering and sorting
const searchQuery = ref('');
const sortBy = ref('date-desc');
const statusFilter = ref('all');

// Calculate total settlement amount for each transaction
const detailedTransactions = computed(() => {
  return props.transactions.map(transaction => {
    let totalSettlementAmount = 0;
    const details = [];

    // Helper function to add to details and total
    const addSettlement = (label, amount) => {
      if (amount) {
        const numericAmount = Number(amount);
        details.push({ label, amount: numericAmount });
        totalSettlementAmount += numericAmount;
      }
    };

    addSettlement('Registration Fee', transaction.settlements?.registration_fee);
    addSettlement('Tuition Fee', transaction.settlements?.tuition_fee);
    addSettlement('Miscellaneous Fee', transaction.settlements?.miscelanous_fee);
    addSettlement('Diploma/Certificate', transaction.settlements?.diploma_or_certificate);
    addSettlement('Transcript of Records', transaction.settlements?.transcript_of_records);
    addSettlement('Certification', transaction.settlements?.certification);
    addSettlement('Special Exam', transaction.settlements?.special_exam);
    addSettlement('Others', transaction.settlements?.others);

    return {
      ...transaction,
      totalSettlementAmount,
      details,
      formattedDate: format(new Date(transaction.transaction_date), 'MMM dd, yyyy')
    };
  });
});

// Filtered and sorted transactions
const filteredTransactions = computed(() => {
  let result = [...detailedTransactions.value];

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    result = result.filter(t =>
      t.transaction_number.toLowerCase().includes(query) ||
      t.formattedDate.toLowerCase().includes(query) ||
      (t.description && t.description.toLowerCase().includes(query))
    );
  }

  // Apply status filter
  if (statusFilter.value !== 'all') {
    result = result.filter(t => t.status === statusFilter.value);
  }

  // Apply sorting
  switch (sortBy.value) {
    case 'date-asc':
      result.sort((a, b) => new Date(a.transaction_date) - new Date(b.transaction_date));
      break;
    case 'date-desc':
      result.sort((a, b) => new Date(b.transaction_date) - new Date(a.transaction_date));
      break;
    case 'amount-asc':
      result.sort((a, b) => a.totalSettlementAmount - b.totalSettlementAmount);
      break;
    case 'amount-desc':
      result.sort((a, b) => b.totalSettlementAmount - a.totalSettlementAmount);
      break;
  }

  return result;
});

const viewTransaction = (transaction) => {
  emit('view-transaction', transaction);
};

const getStatusColor = (status) => {
  return status === 'completed' ? 'success' : 'default';
};

const getStatusIcon = (status) => {
  return status === 'completed' ? 'mdi:check-circle-outline' : 'mdi:clock-outline';
};
</script>

<template>
  <Card class="shadow-sm">
    <CardHeader class="pb-3 border-b">
      <CardTitle class="flex items-center gap-2">
        <Icon icon="mdi:history" class="w-5 h-5" />
        Transaction History
      </CardTitle>

      <!-- Filters -->
      <div class="flex flex-col sm:flex-row gap-3 mt-4">
        <div class="flex-1">
          <Input v-model="searchQuery" placeholder="Search transactions..." class="w-full">
            <template #prefix>
              <Icon icon="mdi:magnify" class="w-4 h-4 text-muted-foreground" />
            </template>
          </Input>
        </div>
        <div class="flex gap-2">
          <Select v-model="statusFilter">
            <SelectTrigger class="w-[140px]">
              <SelectValue placeholder="Status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Statuses</SelectItem>
              <SelectItem value="completed">Completed</SelectItem>
              <SelectItem value="pending">Pending</SelectItem>
            </SelectContent>
          </Select>

          <Select v-model="sortBy">
            <SelectTrigger class="w-[140px]">
              <SelectValue placeholder="Sort by" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="date-desc">Newest First</SelectItem>
              <SelectItem value="date-asc">Oldest First</SelectItem>
              <SelectItem value="amount-desc">Highest Amount</SelectItem>
              <SelectItem value="amount-asc">Lowest Amount</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>
    </CardHeader>

    <CardContent class="p-0">
      <div class="divide-y">
        <div v-if="filteredTransactions.length === 0" class="p-8 text-center text-muted-foreground">
          <Icon icon="mdi:receipt-text-outline" class="w-12 h-12 mx-auto mb-2 opacity-50" />
          <p>No transactions found</p>
        </div>

        <div v-for="transaction in filteredTransactions" :key="transaction.id"
             class="p-4 hover:bg-muted/50 transition-colors cursor-pointer"
             @click="viewTransaction(transaction)">
          <div class="flex flex-col sm:flex-row justify-between gap-3">
            <div class="flex items-start gap-3">
              <div class="p-2 rounded-full bg-primary/10 text-primary">
                <Icon icon="mdi:receipt-text-outline" class="w-6 h-6" />
              </div>
              <div>
                <p class="font-medium">Transaction #{{ transaction.transaction_number }}</p>
                <p class="text-sm text-muted-foreground">{{ transaction.formattedDate }}</p>
                <p v-if="transaction.description" class="text-sm text-muted-foreground mt-1 line-clamp-1">
                  {{ transaction.description }}
                </p>
              </div>
            </div>

            <div class="flex flex-col items-end gap-1">
              <p class="font-bold">{{ formatCurrency(transaction.totalSettlementAmount) }}</p>
              <Badge :variant="getStatusColor(transaction.status)" class="flex items-center gap-1">
                <Icon :icon="getStatusIcon(transaction.status)" class="w-3 h-3" />
                {{ transaction.status }}
              </Badge>
            </div>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
