<script setup>
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/shadcn/ui/card';
import { Icon } from '@iconify/vue';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/Components/shadcn/ui/accordion";

// Payment methods and instructions
const paymentMethods = [
  {
    id: 'bank-deposit',
    name: 'Bank Deposit',
    icon: 'mdi:bank-outline',
    instructions: [
      'Visit any branch of BDO, BPI, or Metrobank',
      'Fill out a deposit slip with the school account number',
      'Indicate your Student ID and full name as reference',
      'Keep the deposit slip as proof of payment',
      'Submit a copy of the deposit slip to the accounting office or upload it through the student portal'
    ],
    accountDetails: [
      { bank: 'BDO', accountName: 'DCCP Educational System Inc.', accountNumber: '1234-5678-9012' },
      { bank: 'BPI', accountName: 'DCCP Educational System Inc.', accountNumber: '9876-5432-1098' },
      { bank: 'Metrobank', accountName: 'DCCP Educational System Inc.', accountNumber: '5678-9012-3456' }
    ]
  },
  {
    id: 'online-banking',
    name: 'Online Banking',
    icon: 'mdi:laptop-outline',
    instructions: [
      'Log in to your online banking account',
      'Select "Fund Transfer" or "Pay Bills"',
      'Enter the school account details',
      'Use your Student ID and full name as reference',
      'Take a screenshot of the confirmation page',
      'Upload the screenshot through the student portal'
    ]
  },
  {
    id: 'payment-centers',
    name: 'Payment Centers',
    icon: 'mdi:store-outline',
    instructions: [
      'Visit any GCash, PayMaya, or 7-Eleven branch',
      'Inform the cashier that you want to pay for DCCP tuition',
      'Provide your Student ID and full name',
      'Pay the amount due',
      'Keep the receipt as proof of payment',
      'Submit a copy of the receipt to the accounting office'
    ]
  },
  {
    id: 'accounting-office',
    name: 'Accounting Office',
    icon: 'mdi:office-building-outline',
    instructions: [
      'Visit the school accounting office during office hours (8:00 AM - 5:00 PM, Monday to Friday)',
      'Present your student ID',
      'Pay the amount due using cash, check, or card',
      'Receive an official receipt',
      'Keep the receipt for your records'
    ]
  }
];

// Contact information
const contactInfo = {
  office: 'Accounting Office',
  email: 'accounting@dccp.edu.ph',
  phone: '(02) 8123-4567',
  hours: 'Monday to Friday, 8:00 AM - 5:00 PM'
};
</script>

<template>
  <Card class="shadow-sm">
    <CardHeader class="pb-3 border-b">
      <CardTitle class="flex items-center gap-2">
        <Icon icon="mdi:information-outline" class="w-5 h-5" />
        Payment Information
      </CardTitle>
    </CardHeader>

    <CardContent class="p-4">
      <!-- Important Notice -->
      <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-lg">
        <div class="flex items-start gap-3">
          <Icon icon="mdi:alert-circle-outline" class="w-6 h-6 text-amber-500 dark:text-amber-400 flex-shrink-0 mt-0.5" />
          <div>
            <h3 class="font-medium text-amber-800 dark:text-amber-300 mb-1">Important Notice</h3>
            <p class="text-sm text-amber-700 dark:text-amber-400">
              Please ensure that all payments are made before the due date to avoid late payment penalties.
              Always keep your payment receipts for verification purposes.
            </p>
          </div>
        </div>
      </div>

      <!-- Payment Methods -->
      <div class="mb-6">
        <h3 class="font-medium mb-3">Payment Methods</h3>

        <Accordion type="single" collapsible class="w-full">
          <AccordionItem v-for="method in paymentMethods" :key="method.id" :value="method.id">
            <AccordionTrigger class="hover:bg-gray-50 px-3 rounded-lg">
              <div class="flex items-center gap-2">
                <Icon :icon="method.icon" class="w-5 h-5 text-primary" />
                <span>{{ method.name }}</span>
              </div>
            </AccordionTrigger>
            <AccordionContent class="px-3">
              <div class="space-y-3 py-2">
                <p class="text-sm font-medium">Instructions:</p>
                <ol class="list-decimal pl-5 text-sm space-y-1">
                  <li v-for="(instruction, i) in method.instructions" :key="i">
                    {{ instruction }}
                  </li>
                </ol>

                <!-- Account details if available -->
                <div v-if="method.accountDetails" class="mt-4">
                  <p class="text-sm font-medium mb-2">Account Details:</p>
                  <div class="space-y-2">
                    <div v-for="(account, i) in method.accountDetails" :key="i"
                         class="p-2 bg-muted/50 rounded border text-sm">
                      <p><span class="font-medium">{{ account.bank }}:</span> {{ account.accountName }}</p>
                      <p class="font-mono">{{ account.accountNumber }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </AccordionContent>
          </AccordionItem>
        </Accordion>
      </div>

      <!-- Contact Information -->
      <div>
        <h3 class="font-medium mb-3">Need Help?</h3>
        <div class="bg-muted/50 p-4 rounded-lg">
          <p class="font-medium mb-2">{{ contactInfo.office }}</p>
          <div class="space-y-2 text-sm">
            <div class="flex items-center gap-2">
              <Icon icon="mdi:email-outline" class="w-4 h-4 text-muted-foreground" />
              <span>{{ contactInfo.email }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Icon icon="mdi:phone-outline" class="w-4 h-4 text-muted-foreground" />
              <span>{{ contactInfo.phone }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Icon icon="mdi:clock-outline" class="w-4 h-4 text-muted-foreground" />
              <span>{{ contactInfo.hours }}</span>
            </div>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
