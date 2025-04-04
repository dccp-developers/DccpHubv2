<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref } from 'vue';
import { format } from 'date-fns';
import { Icon } from '@iconify/vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/shadcn/ui/tabs';

// Import custom components
import PaymentSummary from './Components/PaymentSummary.vue';
import TuitionBreakdown from './Components/TuitionBreakdown.vue';
import TransactionHistory from './Components/TransactionHistory.vue';
import TransactionDetails from './Components/TransactionDetails.vue';
import PaymentInstructions from './Components/PaymentInstructions.vue';

const props = defineProps({
    tuition: {
        type: Object,
        default: null,
    },
    transactions: {
        type: Array,
        required: true,
    },
    student: {
        type: Object,
        required: true
    }
});

// --- Computed Properties ---
const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) { return '₱0'; }
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
};

const formattedDueDate = computed(() => {
    return props.tuition?.due_date ? format(new Date(props.tuition.due_date), 'MMMM dd, yyyy') : 'N/A';
});

// --- Refs for Interactivity ---
const activeTab = ref('overview');
const isTransactionDetailsOpen = ref(false);
const selectedTransaction = ref(null);

// --- Methods ---
const viewTransactionDetails = (transaction) => {
    selectedTransaction.value = transaction;
    isTransactionDetailsOpen.value = true;
};
</script>

<template>
    <AppLayout title="Tuition Fees">
        <div class="md:container mx-auto px-4 py-6 space-y-6">
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-2">
                        <Icon icon="mdi:cash-multiple" class="w-6 h-6 text-primary" />
                        Tuition Fees
                    </h1>
                    <p class="text-muted-foreground">
                        {{ tuition ? `${tuition.school_year} - Semester ${tuition.semester}` : 'Loading...' }}
                    </p>
                </div>
                
                <!-- Print Button -->
                <button 
                    class="flex items-center gap-2 px-4 py-2 bg-primary/10 text-primary rounded-md hover:bg-primary/20 transition-colors"
                    @click="window.print()"
                >
                    <Icon icon="mdi:printer-outline" class="w-5 h-5" />
                    Print Statement
                </button>
            </div>

            <div v-if="tuition" class="space-y-6">
                <!-- Main Content with Tabs -->
                <div class="flex flex-col space-y-6">
                    <!-- Payment Summary Component -->
                    <PaymentSummary 
                        :tuition="tuition" 
                        :student="student" 
                        :formatCurrency="formatCurrency"
                        :formattedDueDate="formattedDueDate"
                    />
                    
                    <!-- Tabs Navigation -->
                    <Tabs v-model="activeTab" class="w-full">
                        <TabsList class="grid w-full grid-cols-3 mb-6">
                            <TabsTrigger value="overview" class="flex items-center gap-2">
                                <Icon icon="mdi:file-document-outline" class="w-4 h-4" />
                                Overview
                            </TabsTrigger>
                            <TabsTrigger value="transactions" class="flex items-center gap-2">
                                <Icon icon="mdi:history" class="w-4 h-4" />
                                Transactions
                            </TabsTrigger>
                            <TabsTrigger value="payment-info" class="flex items-center gap-2">
                                <Icon icon="mdi:information-outline" class="w-4 h-4" />
                                Payment Info
                            </TabsTrigger>
                        </TabsList>
                        
                        <!-- Overview Tab Content -->
                        <TabsContent value="overview" class="space-y-6 mt-0">
                            <TuitionBreakdown 
                                :tuition="tuition" 
                                :formatCurrency="formatCurrency" 
                            />
                        </TabsContent>
                        
                        <!-- Transactions Tab Content -->
                        <TabsContent value="transactions" class="space-y-6 mt-0">
                            <TransactionHistory 
                                :transactions="transactions" 
                                :formatCurrency="formatCurrency"
                                @view-transaction="viewTransactionDetails"
                            />
                        </TabsContent>
                        
                        <!-- Payment Info Tab Content -->
                        <TabsContent value="payment-info" class="space-y-6 mt-0">
                            <PaymentInstructions />
                        </TabsContent>
                    </Tabs>
                </div>
                
                <!-- Transaction Details Component -->
                <TransactionDetails 
                    :transaction="selectedTransaction" 
                    :isOpen="isTransactionDetailsOpen"
                    :formatCurrency="formatCurrency"
                    @update:isOpen="isTransactionDetailsOpen = $event"
                />
            </div>

            <div v-else class="text-center py-8 text-muted-foreground">
                <Icon icon="mdi:alert-circle-outline" class="w-12 h-12 mx-auto mb-2 opacity-50" />
                <p>No tuition information available for the current semester.</p>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    /* Hide elements not needed for printing */
    button, [data-state="inactive"], [role="tablist"] { display: none !important; }
    
    /* Force all tab content to be visible */
    [data-state="inactive"] { display: block !important; }
    
    /* Adjust spacing for print */
    .space-y-6 { margin-top: 1.5rem; }
    
    /* Remove backgrounds and shadows */
    * { background-color: white !important; box-shadow: none !important; }
    
    /* Ensure all content is visible */
    [data-state] { height: auto !important; opacity: 1 !important; visibility: visible !important; }
    
    /* Page breaks */
    .page-break-after { page-break-after: always; }
    .page-break-before { page-break-before: always; }
    
    /* Adjust font sizes */
    h1 { font-size: 1.5rem !important; }
    p { font-size: 0.875rem !important; }
}
</style>
