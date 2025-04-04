<script setup>
import {
  Sheet,
  SheetClose,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle
} from "@/Components/shadcn/ui/sheet";
import { Button } from "@/Components/shadcn/ui/button";
import { Badge } from '@/Components/shadcn/ui/badge';
import { Icon } from '@iconify/vue';
import { format } from 'date-fns';
import { computed } from 'vue';

const props = defineProps({
  transaction: {
    type: Object,
    default: null,
  },
  isOpen: {
    type: Boolean,
    default: false
  },
  formatCurrency: {
    type: Function,
    required: true
  }
});

const emit = defineEmits(['update:isOpen']);

const closeSheet = () => {
  emit('update:isOpen', false);
};

const formattedDate = computed(() => {
  if (!props.transaction) return '';
  return format(new Date(props.transaction.transaction_date), 'MMMM dd, yyyy');
});
</script>

<template>
  <Sheet :open="isOpen" @update:open="emit('update:isOpen', $event)">
    <SheetContent side="right" class="overflow-y-auto sm:max-w-md">
      <SheetHeader>
        <SheetTitle class="flex items-center gap-2">
          <Icon icon="mdi:receipt-text-outline" class="w-5 h-5" />
          Transaction Details
        </SheetTitle>
        <SheetDescription v-if="transaction">
          Transaction #{{ transaction.transaction_number }} on {{ formattedDate }}
        </SheetDescription>
      </SheetHeader>

      <div v-if="transaction" class="py-6">
        <!-- Status Badge -->
        <div class="mb-6 flex items-center gap-2">
          <span class="text-sm text-muted-foreground">Status:</span>
          <Badge :variant="transaction.status === 'completed' ? 'success' : 'default'" class="flex items-center gap-1">
            <Icon :icon="transaction.status === 'completed' ? 'mdi:check-circle-outline' : 'mdi:clock-outline'" class="w-3 h-3" />
            {{ transaction.status }}
          </Badge>
        </div>

        <!-- Description if available -->
        <div v-if="transaction.description" class="mb-6">
          <h3 class="text-sm font-medium text-muted-foreground mb-1">Description</h3>
          <p class="text-sm">{{ transaction.description }}</p>
        </div>

        <!-- Payment Details -->
        <div class="mb-6">
          <h3 class="text-sm font-medium text-muted-foreground mb-3">Payment Details</h3>

          <div class="bg-muted/50 rounded-lg overflow-hidden">
            <div class="divide-y">
              <div v-for="detail in transaction.details" :key="detail.label"
                   class="flex justify-between items-center p-3">
                <span class="text-sm">{{ detail.label }}</span>
                <span class="text-sm font-medium">{{ formatCurrency(detail.amount) }}</span>
              </div>

              <!-- Total -->
              <div class="flex justify-between items-center p-3 bg-primary/5">
                <span class="font-medium">Total</span>
                <span class="font-bold">{{ formatCurrency(transaction.totalSettlementAmount) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Receipt Actions -->
        <div class="flex flex-col gap-2">
          <Button variant="outline" class="w-full flex items-center justify-center gap-2">
            <Icon icon="mdi:printer-outline" class="w-4 h-4" />
            Print Receipt
          </Button>
          <Button variant="outline" class="w-full flex items-center justify-center gap-2">
            <Icon icon="mdi:download-outline" class="w-4 h-4" />
            Download PDF
          </Button>
        </div>
      </div>

      <SheetFooter>
        <SheetClose asChild>
          <Button type="button" variant="secondary" @click="closeSheet">Close</Button>
        </SheetClose>
      </SheetFooter>
    </SheetContent>
  </Sheet>
</template>
