<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/shadcn/ui/card';
import { Button } from '@/Components/shadcn/ui/button';
import { Input } from '@/Components/shadcn/ui/input';
import { Alert, AlertDescription } from '@/Components/shadcn/ui/alert';
import { Icon } from '@iconify/vue';

const page = usePage();
const props = page.props;

const totalLectureFee = Number(props.totalLectureFee) || 0;
const totalLabFee = Number(props.totalLabFee) || 0;
const totalMiscFee = Number(props.totalMiscFee) || 0;
const overallTuition = Number(props.overallTuition) || 0;
const minDownPayment = Number(props.minDownPayment) || 2000;

const downPayment = ref(minDownPayment);
const isFullPayment = ref(downPayment.value === overallTuition);

const balance = computed(() => overallTuition - downPayment.value);

function formatCurrency(value) {
  if (value === null || value === undefined) return 'N/A';
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value);
}

function handleFullPayment() {
  isFullPayment.value = true;
  downPayment.value = overallTuition;
}

function handlePartialPayment() {
  isFullPayment.value = false;
  downPayment.value = minDownPayment;
}

function handleDownPaymentInput(e) {
  let val = Number(e.target.value);
  if (isNaN(val)) val = minDownPayment;
  if (val < minDownPayment) val = minDownPayment;
  if (val > overallTuition) val = overallTuition;
  downPayment.value = val;
  isFullPayment.value = (val === overallTuition);
}

function confirmPayment() {
  // TODO: Implement payment logic or redirect
  alert(`Proceeding with down payment of ${formatCurrency(downPayment.value)}. (Implement payment logic here)`);
}
</script>

<template>
  <div class="container mx-auto px-4 py-8 max-w-lg">
    <Card class="shadow-lg border border-border">
      <CardHeader class="bg-gradient-to-r from-primary/10 via-background to-background dark:from-primary/20 p-5">
        <CardTitle class="flex items-center gap-2">
          <Icon icon="lucide:credit-card" class="w-6 h-6 text-primary" />
          Payment Processing
        </CardTitle>
      </CardHeader>
      <CardContent class="space-y-6">
        <!-- Fee Breakdown -->
        <div class="divide-y divide-border">
          <div class="flex justify-between py-2">
            <span>Total Lecture Fee</span>
            <span class="font-medium">{{ formatCurrency(totalLectureFee) }}</span>
          </div>
          <div class="flex justify-between py-2">
            <span>Total Laboratory Fee</span>
            <span class="font-medium">{{ formatCurrency(totalLabFee) }}</span>
          </div>
          <div class="flex justify-between py-2">
            <span>Total Miscellaneous Fee</span>
            <span class="font-medium">{{ formatCurrency(totalMiscFee) }}</span>
          </div>
          <div class="flex justify-between py-2">
            <span>Overall Tuition Fee</span>
            <span class="font-bold text-primary">{{ formatCurrency(overallTuition) }}</span>
          </div>
        </div>

        <!-- Down Payment Input -->
        <div class="space-y-2">
          <label class="block font-medium mb-1">How much will you pay now?</label>
          <div class="flex gap-2 mb-2">
            <Button :variant="!isFullPayment ? 'default' : 'outline'" @click="handlePartialPayment">Partial (Min ₱{{ minDownPayment }})</Button>
            <Button :variant="isFullPayment ? 'default' : 'outline'" @click="handleFullPayment">Full Payment</Button>
          </div>
          <Input type="number" :min="minDownPayment" :max="overallTuition" v-model="downPayment" @input="handleDownPaymentInput" :disabled="isFullPayment" class="w-full" />
          <p class="text-xs text-muted-foreground">Minimum down payment is ₱{{ minDownPayment }}. You may pay in full if you wish.</p>
        </div>

        <!-- Balance Summary -->
        <div class="flex justify-between items-center py-2 border-t border-border mt-4">
          <span class="font-medium">Balance after payment:</span>
          <span class="font-bold text-red-500">{{ formatCurrency(balance) }}</span>
        </div>

        <Alert variant="info">
          <AlertDescription class="text-xs">
            You can pay the minimum down payment or the full tuition. The remaining balance can be paid later.
          </AlertDescription>
        </Alert>

        <Button class="w-full mt-4" size="lg" @click="confirmPayment">
          Confirm & Proceed to Payment
        </Button>
      </CardContent>
    </Card>
  </div>
</template>

<style scoped>
</style> 