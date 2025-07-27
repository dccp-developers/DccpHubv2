<script setup>
import { Link } from '@inertiajs/vue3';
import { Alert, AlertTitle, AlertDescription } from '@/Components/shadcn/ui/alert';
import { Button } from '@/Components/shadcn/ui/button';
import { Icon } from '@iconify/vue';
import { Card, CardContent } from '@/Components/shadcn/ui/card';
import { computed, ref } from 'vue';

const props = defineProps({
  generalSettings: {
    type: Object,
    required: true,
  },
  user: {
    type: Object,
    required: true
  },
  studentEnrollment: {
    type: Object,
    default: null
  }
});

// Format the date to make it more readable
const enrollmentPeriod = computed(() => {
  const currentDate = new Date();
  const endDate = new Date(currentDate);
  endDate.setDate(currentDate.getDate() + 14); // Example: enrollment period is 14 days
  const options = { month: 'long', day: 'numeric' };
  return `${currentDate.toLocaleDateString('en-US', options)} - ${endDate.toLocaleDateString('en-US', options)}`;
});

// Enrollment status steps
const statusSteps = [
  { key: 'Pending', label: 'Pending', icon: 'lucide:clock' },
  { key: 'Verified By Dept Head', label: 'Verified by Dept Head', icon: 'lucide:user-check' },
  { key: 'Verified By Cashier', label: 'Verified by Cashier', icon: 'lucide:check-circle' },
];

// Compute current step index
const currentStep = computed(() => {
  if (!props.studentEnrollment) return 0;
  const status = props.studentEnrollment.status;
  if (status === 'Pending') return 0;
  if (status === 'Verified By Dept Head') return 1;
  if (status === 'Verified By Cashier') return 2;
  return 0;
});

// Local state for closing the notice after final status
const closed = ref(false);
const canClose = computed(() => props.studentEnrollment && props.studentEnrollment.status === 'Verified By Cashier');
const handleClose = () => { closed.value = true; };

// Check if student is already enrolled (has verified enrollment)
const isAlreadyEnrolled = computed(() => {
  try {
    // Check if student has a verified enrollment for the current period
    return props.studentEnrollment &&
           props.studentEnrollment.id &&
           props.studentEnrollment.status === 'Verified By Cashier';
  } catch (error) {
    console.warn('Error checking enrollment status:', error);
    return false;
  }
});

// Show enrollment notice only if user is student, not closed, and not already enrolled
// PRIORITY: Hide completely if student is already enrolled, regardless of any other settings
const shouldShowEnrollmentNotice = computed(() => {
  // RULE 1: If student is already enrolled (status = 'Verified By Cashier'), NEVER show the banner
  // This takes precedence over all other conditions including online_enrollment_enabled
  if (isAlreadyEnrolled.value) {
    return false;
  }

  // RULE 2: Only show if all conditions are met:
  // - Online enrollment is enabled
  // - User is a student
  // - Banner hasn't been manually closed
  return props.generalSettings.online_enrollment_enabled &&
         props.user.role === 'student' &&
         !closed.value;
});
</script>

<template>
  <div v-if="shouldShowEnrollmentNotice">
    <!-- If student has an enrollment, show status tracker -->
    <template v-if="studentEnrollment && studentEnrollment.id">
      <Card class="border-2 border-primary shadow-lg overflow-hidden bg-gradient-to-br from-primary/5 to-background">
        <div class="absolute top-0 left-0 w-full h-1 bg-primary"></div>
        <CardContent class="p-0">
          <div class="flex flex-col md:flex-row">
            <!-- Left section with icon and main message -->
            <div class="bg-primary/10 p-6 flex items-center justify-center md:w-1/4">
              <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/20 mb-2">
                  <Icon icon="lucide:calendar-clock" class="h-8 w-8 text-primary" />
                </div>
                <h3 class="font-bold text-primary text-lg">Enrollment Status</h3>
                <p class="text-sm font-medium text-primary/80">{{ enrollmentPeriod }}</p>
              </div>
            </div>
            <!-- Right section with status tracker -->
            <div class="p-6 md:w-3/4 relative">
              <div class="absolute top-2 right-2 inline-flex items-center justify-center px-2 py-1 rounded-full bg-primary/10 text-xs font-semibold text-primary">
                <Icon icon="lucide:clock" class="h-3 w-3 mr-1" />
                Tracking
              </div>
              <h2 class="text-2xl font-bold text-primary mb-2 flex items-center gap-2">
                <Icon icon="lucide:bell-ring" class="h-6 w-6 text-primary animate-bounce" />
                Enrollment Progress
              </h2>
              <!-- Stepper -->
              <div class="flex items-center gap-4 my-4">
                <template v-for="(step, idx) in statusSteps" :key="step.key">
                  <div class="flex flex-col items-center">
                    <div :class="[
                      'rounded-full w-10 h-10 flex items-center justify-center mb-1',
                      idx < currentStep ? 'bg-green-500 text-white' : idx === currentStep ? 'bg-primary text-white' : 'bg-gray-200 text-gray-400'
                    ]">
                      <Icon :icon="step.icon" class="w-6 h-6" />
                    </div>
                    <span :class="[
                      'text-xs font-semibold',
                      idx <= currentStep ? 'text-primary' : 'text-gray-400'
                    ]">{{ step.label }}</span>
                  </div>
                  <div v-if="idx < statusSteps.length - 1" class="flex-1 h-1 bg-gradient-to-r" :class="idx < currentStep ? 'from-green-400 to-green-200' : 'from-gray-200 to-gray-100'" />
                </template>
              </div>
              <div class="mb-4 text-foreground">
                <p class="mb-2">
                  Your enrollment for <span class="font-medium">{{ generalSettings.getSemester }}</span> in <span class="font-medium">School Year {{ generalSettings.getSchoolYearString }}</span> is being processed.
                </p>
                <ul class="list-disc list-inside space-y-1 text-sm pl-1">
                  <li v-if="currentStep === 0">Please wait for department head verification.</li>
                  <li v-if="currentStep === 1">Your enrollment is being verified by the cashier.</li>
                  <li v-if="currentStep === 2">You are now officially enrolled! You may download your assessment and certificate soon.</li>
                </ul>
              </div>
              <div class="flex justify-end">
                <Button v-if="canClose" @click="handleClose" size="lg" class="bg-primary hover:bg-primary/90 text-white shadow-md">
                  <Icon icon="lucide:x" class="h-5 w-5 mr-1" />
                  <span class="font-bold">Close</span>
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </template>
    <!-- If no enrollment, show the original enrollment notice -->
    <template v-else>
      <Card class="border-2 border-primary shadow-lg overflow-hidden bg-gradient-to-br from-primary/5 to-background">
        <div class="absolute top-0 left-0 w-full h-1 bg-primary"></div>
        <CardContent class="p-0">
          <div class="flex flex-col md:flex-row">
            <!-- Left section with icon and main message -->
            <div class="bg-primary/10 p-6 flex items-center justify-center md:w-1/4">
              <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/20 mb-2">
                  <Icon icon="lucide:calendar-clock" class="h-8 w-8 text-primary" />
                </div>
                <h3 class="font-bold text-primary text-lg">Enrollment Period</h3>
                <p class="text-sm font-medium text-primary/80">{{ enrollmentPeriod }}</p>
              </div>
            </div>
            <!-- Right section with details and CTA -->
            <div class="p-6 md:w-3/4 relative">
              <div class="absolute top-2 right-2 inline-flex items-center justify-center px-2 py-1 rounded-full bg-primary/10 text-xs font-semibold text-primary">
                <Icon icon="lucide:clock" class="h-3 w-3 mr-1" />
                Limited Time
              </div>
              <h2 class="text-2xl font-bold text-primary mb-2 flex items-center gap-2">
                <Icon icon="lucide:bell-ring" class="h-6 w-6 text-primary animate-bounce" />
                Enrollment Now Open!
              </h2>
              <div class="mb-4 text-foreground">
                <p class="mb-2">
                  <span class="font-medium">{{ generalSettings.getSemester }}</span> enrollment for 
                  <span class="font-medium">School Year {{ generalSettings.getSchoolYearString }}</span> 
                  is now open for registration.
                </p>
                <ul class="list-disc list-inside space-y-1 text-sm pl-1">
                  <li>Select your preferred subjects and schedule</li>
                  <li>Review your tuition fees and payment options</li>
                  <li>Complete your enrollment online in minutes</li>
                </ul>
              </div>
              <div class="flex justify-end">
                <Button as-child size="lg" class="bg-primary hover:bg-primary/90 text-white shadow-md">
                  <Link :href="route('student.enroll.subjects')" class="flex items-center gap-2 px-6">
                    <Icon icon="lucide:book-plus" class="h-5 w-5" />
                    <span class="font-bold">Enroll Now</span>
                    <Icon icon="lucide:arrow-right" class="h-5 w-5 ml-1" />
                  </Link>
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </template>
  </div>
</template> 