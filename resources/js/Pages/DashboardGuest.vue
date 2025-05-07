<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, usePage, useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/shadcn/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/shadcn/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/shadcn/ui/table';
import { Alert, AlertDescription } from '@/Components/shadcn/ui/alert';
import { Badge } from '@/Components/shadcn/ui/badge';
import { Icon } from '@iconify/vue';
import { computed, ref } from 'vue';
import { Separator } from '@/Components/shadcn/ui/separator';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/shadcn/ui/dialog';

const props = defineProps({
  guestInfo: { type: Object, required: true },
  course: { type: Object, required: true },
  semester: { type: Number, required: true },
  schoolYear: { type: String, required: true },
  subjects: { type: Array, required: true },
  totalEstimatedTuition: { type: Number, required: true },
  enrollmentStatus: { type: String, default: null },
  enrollment: { type: Object, default: null },
  student: { type: Object, default: null },
});
console.log(props.enrollmentStatus);
const formattedSubjectSemester = '1st Semester';
const formattedAcademicYear = '1st Year';

const formatCurrency = (value) => {
    if (value === null || value === undefined) return 'N/A';
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value);
};

const hasSubjects = computed(() => props.subjects && props.subjects.length > 0);

// Fee breakdown values
const totalLectureFee = computed(() => props.totalEstimatedTuition); // Example: all tuition is lecture
const totalLabFee = computed(() => 0); // Example: no lab fee
const totalMiscFee = computed(() => 3500); // Example: fixed
const minDownPayment = 2000;
const overallTuition = computed(() => totalLectureFee.value + totalLabFee.value + totalMiscFee.value);

// Stepper state
const step = ref(1);

// Payment process state
const page = usePage();
const pendingEnrollmentId = page.props.pendingEnrollmentId || null;

const paymentForm = useForm({
  pending_enrollment_id: pendingEnrollmentId || '',
  downpayment: minDownPayment,
  payment_method: 'cash',
  subjects: props.subjects,
  totalLectureFee: computed(() => props.totalEstimatedTuition),
  totalLabFee: 0,
  totalMiscFee: 3500,
  overallTuition: computed(() => props.totalEstimatedTuition + 0 + 3500),
  semester: props.semester,
  academic_year: 1,
  school_year: props.schoolYear,
});

const isFullPayment = ref(false);
const balance = computed(() => paymentForm.overallTuition - paymentForm.downpayment);

// Payment methods
const paymentMethods = [
  {
    key: 'cash',
    name: 'Cash',
    icon: 'mdi:cash',
    available: true,
    description: 'Pay at the school cashier or accounting office.'
  },
  {
    key: 'gcash',
    name: 'GCash',
    icon: 'mdi:cellphone-message',
    available: false,
    description: 'GCash payments coming soon.'
  },
  {
    key: 'bank',
    name: 'Bank Transfer',
    icon: 'mdi:bank-transfer',
    available: false,
    description: 'Bank transfer coming soon.'
  },
  {
    key: 'card',
    name: 'Credit/Debit Card',
    icon: 'mdi:credit-card-outline',
    available: false,
    description: 'Card payments coming soon.'
  }
];

const selectedPaymentMethod = ref('cash');

function selectPaymentMethod(method) {
  if (method.available) {
    paymentForm.payment_method = method.key;
  }
}

function goToPaymentStep() {
  step.value = 2;
  paymentForm.downpayment = minDownPayment;
  isFullPayment.value = false;
}

function handleFullPayment() {
  isFullPayment.value = true;
  paymentForm.downpayment = paymentForm.overallTuition;
}

function handlePartialPayment() {
  isFullPayment.value = false;
  paymentForm.downpayment = minDownPayment;
}

function handleDownPaymentInput(e) {
  let val = Number(e.target.value);
  if (isNaN(val)) val = minDownPayment;
  if (val < minDownPayment) val = minDownPayment;
  if (val > paymentForm.overallTuition) val = paymentForm.overallTuition;
  paymentForm.downpayment = val;
  isFullPayment.value = (val === paymentForm.overallTuition);
}

const showConfirmation = ref(false);
const notification = ref(null);

function submitEnrollment() {
  notification.value = null;
  paymentForm.post(route('enroll.confirm'), {
    onSuccess: () => {
      showConfirmation.value = false;
      notification.value = {
        type: 'success',
        message: 'Your enrollment has been submitted successfully and is now being processed.'
      };
    },
    onError: (errors) => {
      notification.value = {
        type: 'error',
        message: errors.enrollment || 'Failed to submit enrollment. Please try again.'
      };
    }
  });
}

function confirmPayment() {
  showConfirmation.value = true;
}

// Helper to get full name from guestInfo if student is missing
const summaryName = computed(() => {
  if (props.student && props.student.full_name) return props.student.full_name;
  if (props.guestInfo && (props.guestInfo.first_name || props.guestInfo.last_name)) {
    return [props.guestInfo.first_name, props.guestInfo.middle_name, props.guestInfo.last_name].filter(Boolean).join(' ');
  }
  return '';
});
// Helper to get course title/code from enrollment or course prop
const summaryCourse = computed(() => {
  if (props.enrollment && props.enrollment.course && props.enrollment.course.title) {
    return `${props.enrollment.course.title} (${props.enrollment.course.code})`;
  }
  if (props.course && props.course.title) {
    return `${props.course.title} (${props.course.code})`;
  }
  return '';
});
const summarySchoolYear = computed(() => props.enrollment?.school_year || props.schoolYear || '');
const summarySemester = computed(() => props.enrollment?.semester || props.semester || '');
const summaryStatus = computed(() => props.enrollment?.status || props.enrollmentStatus || '');
</script>

<template>
  <Head :title="`Enrollment: ${course.title} - ${formattedSubjectSemester}`" />
  <AppLayout :title="`Enrollment - ${course.title}`">
    <div class="container mx-auto px-4 py-8 space-y-8">
      <!-- Notification -->
      <div v-if="notification" class="mb-4">
        <Alert :variant="notification.type === 'success' ? 'success' : 'destructive'">
          <AlertDescription>{{ notification.message }}</AlertDescription>
        </Alert>
      </div>
      <!-- Status Card if enrollment is processing or exists -->
      <template v-if="enrollmentStatus === 'processed' || enrollment">
        <div class="max-w-lg mx-auto">
          <Card class="shadow-xl border border-green-400 bg-gradient-to-br from-green-50 to-background">
            <CardHeader class="flex flex-col items-center gap-2 p-8">
              <Icon icon="lucide:check-circle" class="w-16 h-16 text-green-500 mb-2" />
              <CardTitle class="text-2xl text-green-700 text-center">Enrollment Submitted!</CardTitle>
              <CardDescription class="text-center text-base mt-2">
                Your enrollment is now <span class="font-semibold text-green-700">being processed</span>.<br>
                Please check your email for confirmation and further instructions.
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="rounded-lg border border-green-200 bg-white/80 p-4">
                <div class="font-semibold mb-3 flex items-center gap-2 text-lg">
                  <Icon icon="lucide:user-check" class="w-6 h-6 text-green-600" />
                  Enrollment Details
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-3">
                  <div class="flex items-center gap-2">
                    <Icon icon="lucide:user" class="w-4 h-4 text-primary" />
                    <span class="font-medium">Name:</span>
                  </div>
                  <div>{{ summaryName }}</div>
                  <div class="flex items-center gap-2">
                    <Icon icon="lucide:book" class="w-4 h-4 text-primary" />
                    <span class="font-medium">Course:</span>
                  </div>
                  <div>{{ summaryCourse }}</div>
                  <div class="flex items-center gap-2">
                    <Icon icon="lucide:calendar" class="w-4 h-4 text-primary" />
                    <span class="font-medium">School Year:</span>
                  </div>
                  <div>{{ summarySchoolYear }}</div>
                  <div class="flex items-center gap-2">
                    <Icon icon="lucide:clock" class="w-4 h-4 text-primary" />
                    <span class="font-medium">Semester:</span>
                  </div>
                  <div>{{ summarySemester }}</div>
                  <div class="flex items-center gap-2">
                    <Icon icon="lucide:badge-check" class="w-4 h-4 text-primary" />
                    <span class="font-medium">Status:</span>
                  </div>
                  <div>
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold"
                          :class="summaryStatus === 'approved' ? 'bg-green-100 text-green-700' : summaryStatus === 'processed' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700'">
                      <Icon v-if="summaryStatus === 'approved'" icon="lucide:check-circle" class="w-4 h-4" />
                      <Icon v-else-if="summaryStatus === 'processed'" icon="lucide:clock" class="w-4 h-4" />
                      <Icon v-else icon="lucide:info" class="w-4 h-4" />
                      {{ summaryStatus.charAt(0).toUpperCase() + summaryStatus.slice(1) }}
                    </span>
                  </div>
                </div>
                <Alert variant="success" class="mt-4">
                  <AlertDescription class="text-base flex items-center gap-2">
                    <Icon icon="lucide:info" class="w-5 h-5 text-green-600" />
                    <span>
                      Thank you for submitting your enrollment!<br>
                      <span class="font-medium">Next steps:</span> Please check your email for confirmation and further instructions. Our team will review your submission and notify you once your enrollment is approved or if further action is needed.
                    </span>
                  </AlertDescription>
                </Alert>
              </div>
            </CardContent>
          </Card>
        </div>
      </template>
      <!-- Stepper and forms if not processing and no enrollment -->
      <template v-else>
        <!-- Step Indicator -->
        <div class="flex items-center justify-center gap-2 mb-4">
          <div class="flex items-center gap-2" :class="step === 1 ? '' : 'opacity-60'">
            <span class="rounded-full bg-primary text-white w-8 h-8 flex items-center justify-center font-bold">1</span>
            <span class="font-semibold text-primary">Review Subjects</span>
          </div>
          <Separator class="w-8 mx-2" />
          <div class="flex items-center gap-2" :class="step === 2 ? '' : 'opacity-60'">
            <span class="rounded-full bg-muted w-8 h-8 flex items-center justify-center font-bold">2</span>
            <span class="font-semibold">Payment Details</span>
          </div>
        </div>

        <!-- Step 1: Subject Review -->
        <template v-if="step === 1">
          <!-- Summary Card -->
          <Card class="shadow border border-border mb-6">
            <CardHeader class="bg-gradient-to-r from-primary/10 via-background to-background dark:from-primary/20 p-5">
              <CardTitle class="text-lg flex items-center gap-2">
                <Icon icon="lucide:graduation-cap" class="w-6 h-6 text-primary" />
                Enrollment Summary
              </CardTitle>
              <CardDescription class="mt-1">
                <span class="font-medium text-foreground">{{ course.title }}</span> ({{ course.code }})<br>
                <span class="text-muted-foreground">{{ formattedAcademicYear }}, {{ formattedSubjectSemester }}</span><br>
                <span class="text-muted-foreground">A.Y.:</span> <span class="font-medium">{{ schoolYear }}</span>
              </CardDescription>
            </CardHeader>
          </Card>
          <!-- Subject Table -->
          <Card class="shadow border border-border">
            <CardHeader class="p-4 bg-muted/30">
              <CardTitle class="text-base flex items-center gap-2">
                <Icon icon="lucide:book-open-check" class="w-5 h-5 text-primary" />
                Subjects for this Semester
              </CardTitle>
            </CardHeader>
            <CardContent class="p-0">
              <div v-if="!hasSubjects" class="p-12 text-center text-muted-foreground">
                <Icon icon="lucide:folder-search" class="w-12 h-12 mx-auto mb-4 text-gray-400" />
                <p>No subjects found for this semester.</p>
                <p class="text-sm">Please contact administration.</p>
              </div>
              <div v-else>
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead class="w-12 text-center">#</TableHead>
                      <TableHead>Code</TableHead>
                      <TableHead>Title</TableHead>
                      <TableHead class="text-center">Units</TableHead>
                      <TableHead class="text-center">Lecture/Lab</TableHead>
                      <TableHead class="text-right">Est. Fee</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="(subject, index) in subjects" :key="subject.id">
                      <TableCell class="text-center font-semibold">{{ index + 1 }}</TableCell>
                      <TableCell><Badge variant="secondary">{{ subject.code }}</Badge></TableCell>
                      <TableCell>{{ subject.title }}</TableCell>
                      <TableCell class="text-center">{{ subject.units }}</TableCell>
                      <TableCell class="text-center text-xs">
                        {{ subject.lecture_units }} lec<span v-if="subject.lab_units > 0">, {{ subject.lab_units }} lab</span>
                      </TableCell>
                      <TableCell class="text-right font-medium">{{ formatCurrency(subject.fee) }}</TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
          <!-- Fee Summary Card -->
          <div v-if="hasSubjects" class="flex flex-col md:flex-row md:justify-end gap-4 mt-4">
            <Card class="w-full md:w-1/2 shadow-lg border border-primary/30 bg-primary/5">
              <CardContent class="p-6 flex flex-col gap-2">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted-foreground">Estimated Tuition (Subjects Only):</span>
                  <span class="text-2xl font-bold text-primary">{{ formatCurrency(totalLectureFee) }}</span>
                </div>
                <Alert variant="info" class="mt-2">
                  <Icon icon="lucide:info" class="h-4 w-4" />
                  <AlertDescription class="text-xs">
                    This is an estimate based on per-unit costs (₱{{ course.lec_per_unit }}/lec, ₱{{ course.lab_per_unit }}/lab). Miscellaneous and other fees will be added in the next step.
                  </AlertDescription>
                </Alert>
              </CardContent>
            </Card>
          </div>
          <!-- Action Button -->
          <div v-if="hasSubjects" class="text-center mt-8">
            <Button size="lg" class="w-full sm:w-auto px-10 py-6 text-lg" @click="goToPaymentStep">
              Proceed to Payment Details
              <Icon icon="lucide:arrow-right-circle" class="ml-3 w-6 h-6" />
            </Button>
            <p class="text-xs text-muted-foreground mt-2">You'll confirm the full breakdown and payment options next.</p>
          </div>
        </template>

        <!-- Step 2: Payment Process -->
        <template v-else-if="step === 2">
          <div class="max-w-lg mx-auto space-y-8">
            <Card class="shadow-xl border border-primary/30 bg-gradient-to-br from-primary/5 to-background">
              <CardHeader class="bg-gradient-to-r from-primary/10 via-background to-background dark:from-primary/20 p-5 rounded-t-lg">
                <CardTitle class="flex items-center gap-2 text-2xl">
                  <Icon icon="lucide:credit-card" class="w-7 h-7 text-primary" />
                  Payment Processing
                </CardTitle>
                <CardDescription class="mt-2 text-base text-muted-foreground">
                  Please review your payment breakdown and select your payment method.
                </CardDescription>
              </CardHeader>
              <CardContent class="space-y-6">
                <!-- Fancy Fee Breakdown -->
                <div class="rounded-lg border border-primary/10 bg-white/70 dark:bg-background/80 shadow p-4 mb-2">
                  <div class="flex flex-col gap-2">
                    <div class="flex justify-between items-center">
                      <span class="font-medium flex items-center gap-2"><Icon icon="mdi:school-outline" class="w-5 h-5 text-primary" /> Total Lecture Fee</span>
                      <span class="font-semibold">{{ formatCurrency(totalLectureFee) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                      <span class="font-medium flex items-center gap-2"><Icon icon="mdi:flask-outline" class="w-5 h-5 text-primary/70" /> Total Laboratory Fee</span>
                      <span class="font-semibold">{{ formatCurrency(totalLabFee) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                      <span class="font-medium flex items-center gap-2"><Icon icon="mdi:cash-multiple" class="w-5 h-5 text-primary/70" /> Miscellaneous Fee</span>
                      <span class="font-semibold">{{ formatCurrency(totalMiscFee) }}</span>
                    </div>
                    <div class="flex justify-between items-center border-t border-border pt-2 mt-2">
                      <span class="font-bold flex items-center gap-2 text-lg"><Icon icon="mdi:file-document-outline" class="w-6 h-6 text-primary" /> Overall Tuition Fee</span>
                      <span class="font-bold text-primary text-lg">{{ formatCurrency(overallTuition) }}</span>
                    </div>
                  </div>
                </div>

                <!-- Payment Method Selection -->
                <div>
                  <div class="mb-2 font-semibold text-base flex items-center gap-2">
                    <Icon icon="mdi:credit-card-outline" class="w-5 h-5 text-primary" />
                    Payment Method
                  </div>
                  <div class="grid grid-cols-2 gap-4">
                    <Card v-for="method in paymentMethods" :key="method.key"
                          :class="[
                            'transition-all duration-200 cursor-pointer',
                            method.available ? 'border-primary shadow-lg ring-2 ring-primary/30' : 'opacity-60 border-border',
                            selectedPaymentMethod === method.key && method.available ? 'bg-primary/10 border-2' : ''
                          ]"
                          @click="selectPaymentMethod(method)"
                          :tabindex="method.available ? 0 : -1"
                          :aria-disabled="!method.available"
                    >
                      <CardContent class="flex flex-col items-center justify-center gap-2 py-6">
                        <Icon :icon="method.icon" class="w-10 h-10" :class="method.available ? 'text-primary' : 'text-gray-400 dark:text-gray-600'" />
                        <span class="font-semibold text-lg" :class="method.available ? 'text-primary' : 'text-muted-foreground'">{{ method.name }}</span>
                        <span v-if="!method.available" class="text-xs text-muted-foreground mt-1">Coming Soon</span>
                        <span v-else-if="method.key === 'cash'" class="text-xs text-green-600 font-medium mt-1">Available</span>
                      </CardContent>
                    </Card>
                  </div>
                  <p class="text-xs text-muted-foreground mt-2">Currently, only <span class="font-semibold text-primary">Cash</span> payment is available. Other methods will be enabled soon.</p>
                </div>

                <!-- Down Payment Input -->
                <div class="space-y-2 mt-6">
                  <label class="block font-medium mb-1">How much will you pay now?</label>
                  <div class="flex gap-2 mb-2">
                    <Button :variant="!isFullPayment ? 'default' : 'outline'" @click="handlePartialPayment">Partial (Min ₱{{ minDownPayment }})</Button>
                    <Button :variant="isFullPayment ? 'default' : 'outline'" @click="handleFullPayment">Full Payment</Button>
                  </div>
                  <input type="number" :min="minDownPayment" :max="overallTuition" v-model="paymentForm.downpayment" @input="handleDownPaymentInput" :disabled="isFullPayment" class="w-full border rounded px-3 py-2" />
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
          <!-- Confirmation Modal -->
          <Dialog v-model:open="showConfirmation">
            <DialogContent class="max-w-lg">
              <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                  <Icon icon="lucide:shield-check" class="w-5 h-5 text-primary" />
                  Confirm Your Enrollment
                </DialogTitle>
              </DialogHeader>
              <form @submit.prevent="submitEnrollment">
              <div class="space-y-4 my-2">
                <div>
                  <div class="font-semibold mb-1">Subjects to Enroll:</div>
                  <ul class="list-disc pl-6 text-sm">
                    <li v-for="subject in subjects" :key="subject.id">
                      {{ subject.title }} <span class="text-muted-foreground">({{ subject.code }}, {{ subject.units }} units)</span>
                    </li>
                  </ul>
                </div>
                <div>
                  <div class="font-semibold mb-1">Tuition Breakdown:</div>
                  <ul class="text-sm">
                      <li>Total Lecture Fee: <span class="font-medium">{{ formatCurrency(paymentForm.totalLectureFee) }}</span></li>
                      <li>Total Laboratory Fee: <span class="font-medium">{{ formatCurrency(paymentForm.totalLabFee) }}</span></li>
                      <li>Miscellaneous Fee: <span class="font-medium">{{ formatCurrency(paymentForm.totalMiscFee) }}</span></li>
                      <li class="font-bold mt-1">Overall Tuition: <span class="text-primary">{{ formatCurrency(paymentForm.overallTuition) }}</span></li>
                      <li>Down Payment: <span class="font-medium">{{ formatCurrency(paymentForm.downpayment) }}</span></li>
                    <li>Balance: <span class="font-bold text-red-500">{{ formatCurrency(balance) }}</span></li>
                  </ul>
                </div>
                <div>
                  <div class="font-semibold mb-1">Payment Method:</div>
                  <div class="flex items-center gap-2">
                      <Icon :icon="paymentMethods.find(m => m.key === paymentForm.payment_method).icon" class="w-5 h-5 text-primary" />
                      <span class="font-medium">{{ paymentMethods.find(m => m.key === paymentForm.payment_method).name }}</span>
                    </div>
                  </div>
                  <div v-if="paymentForm.hasErrors" class="text-red-600 text-sm">
                    <div v-for="(err, key) in paymentForm.errors" :key="key">{{ err }}</div>
                </div>
              </div>
              <DialogFooter>
                  <Button variant="outline" @click="showConfirmation = false" type="button">Cancel</Button>
                  <Button class="ml-2" :disabled="paymentForm.processing" type="submit">
                    <span v-if="paymentForm.processing">Submitting...</span>
                    <span v-else>Confirm & Submit</span>
                  </Button>
              </DialogFooter>
              </form>
            </DialogContent>
          </Dialog>
        </template>
      </template>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Add minor scoped styles if necessary, but prefer Tailwind utilities */
</style> 