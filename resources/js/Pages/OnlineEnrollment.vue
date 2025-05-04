  <script setup>
import { ref, watch } from 'vue';
import WebLayout from '@/Layouts/WebLayout.vue'; // Corrected Layout Import
import { Head } from '@inertiajs/vue3';
import { Button } from '@/Components/shadcn/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/shadcn/ui/card';
import { Input } from '@/Components/shadcn/ui/input';
import { Label } from '@/Components/shadcn/ui/label';
import { Progress } from '@/Components/shadcn/ui/progress';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/Components/shadcn/ui/select'

const currentStep = ref(1);
const totalSteps = 5;
const stepTitles = {
    1: 'Personal Info',
    2: 'Parent/Guardian',
    3: 'Education',
    4: 'Course',
    5: 'Review & Submit'
};

const formData = ref({
  // Personal Info
  firstName: '',
  lastName: '',
  middleName: '',
  suffix: '',
  birthDate: '',
  gender: '',
  nationality: '',
  religion: '',
  address: '',
  contactNumber: '',
  email: '',

  // Parent/Guardian Info
  fatherName: '',
  fatherOccupation: '',
  fatherContact: '',
  motherName: '',
  motherOccupation: '',
  motherContact: '',
  guardianName: '',
  guardianRelationship: '',
  guardianContact: '',

  // Education Info
  lastSchoolAttended: '',
  lastSchoolAddress: '',
  yearLevelCompleted: '',
  yearGraduated: '',
  lrn: '', // Learner Reference Number

  // Course Selection
  selectedCourse: '',
  selectedStrand: '', // If applicable
});

// Removed duplicate/older nextStep definition here

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
};

const submitForm = () => {
  // Handle form submission logic here
  console.log('Form submitted:', formData.value);
  // Potentially use Inertia post/put request
  alert('Enrollment Submitted! (Check console for data)');
};

// --- Validation Logic ---
const fieldErrors = ref({});

const validateStep1 = () => {
    fieldErrors.value = {}; // Clear previous errors
    let isValid = true;
    if (!formData.value.firstName) { fieldErrors.value.firstName = 'First name is required.'; isValid = false; }
    if (!formData.value.lastName) { fieldErrors.value.lastName = 'Last name is required.'; isValid = false; }
    if (!formData.value.birthDate) { fieldErrors.value.birthDate = 'Birth date is required.'; isValid = false; }
    if (!formData.value.gender) { fieldErrors.value.gender = 'Gender is required.'; isValid = false; }
    if (!formData.value.nationality) { fieldErrors.value.nationality = 'Nationality is required.'; isValid = false; }
    if (!formData.value.address) { fieldErrors.value.address = 'Address is required.'; isValid = false; }
    if (!formData.value.contactNumber) { fieldErrors.value.contactNumber = 'Contact number is required.'; isValid = false; }
    if (!formData.value.email) { fieldErrors.value.email = 'Email address is required.'; isValid = false; }
    // Add more checks as needed (e.g., email format)
    return isValid;
}

const validateCurrentStep = () => {
    switch (currentStep.value) {
        case 1: return validateStep1();
        // case 2: return validateStep2(); // Add validation for other steps
        // case 3: return validateStep3();
        // case 4: return validateStep4();
        default: return true; // No validation for review step or others yet
    }
}

const nextStep = () => {
  if (validateCurrentStep() && currentStep.value < totalSteps) {
    currentStep.value++;
  } else if (!validateCurrentStep()) {
      // Optionally show a general error message or rely on field-specific errors
      console.error("Validation failed for step:", currentStep.value, fieldErrors.value);
      alert('Please fill in all required fields for the current step.');
  }
};

const progressPercentage = ref((currentStep.value / totalSteps) * 100);

// Update progress when step changes
watch(currentStep, (newStep) => {
    progressPercentage.value = (newStep / totalSteps) * 100;
});

</script>

<template>
  <Head title="Online Enrollment" />

  <WebLayout>
    <!-- Removed AuthenticatedLayout's header slot -->
    <!-- You might want to add a specific header/title section within the WebLayout's main content area if needed -->
    <!-- For example:
    <div class="container mx-auto px-4 py-8">
        <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight mb-6">
            Online Enrollment Wizard
        </h2>
    </div>
    -->
    <!-- The rest of the content will now be directly inside WebLayout's default slot -->
    <!-- <template #header> removed -->
      <!-- <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Online Enrollment Wizard
      </h2> -->
    <!-- </template> removed -->

    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <Card class="w-full">
          <CardHeader>
            <CardTitle>Student Enrollment</CardTitle>
            <CardDescription>Please fill out the form carefully. Step {{ currentStep }} of {{ totalSteps }}.</CardDescription>
             <Progress v-model="progressPercentage" class="w-full mt-2" />
          </CardHeader>
          <CardContent>
             <!-- Visual Step Indicator -->
            <div class="mb-8 flex items-center justify-between border-b pb-4">
                 <template v-for="(title, step) in stepTitles" :key="step">
                    <div class="flex flex-col items-center text-center w-1/5">
                        <div
                            :class="[
                                'rounded-full h-8 w-8 flex items-center justify-center border-2 mb-1',
                                currentStep === step ? 'bg-primary border-primary text-primary-foreground' : '',
                                currentStep > step ? 'bg-green-500 border-green-500 text-white' : '',
                                currentStep < step ? 'border-muted-foreground text-muted-foreground' : ''
                            ]"
                        >
                            <span v-if="currentStep <= step">{{ step }}</span>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </div>
                        <span :class="['text-xs sm:text-sm', currentStep === step ? 'font-semibold text-primary' : 'text-muted-foreground']">
                            {{ title }}
                        </span>
                    </div>
                    <div v-if="step < totalSteps" class="flex-1 h-px bg-border -mt-6"></div>
                 </template>
            </div>

            <form @submit.prevent="submitForm"> <!-- Removed classes -->
             <transition name="fade" mode="out-in">
                <div :key="currentStep">

                    <!-- Step 1: Personal Information -->
                    <div v-if="currentStep === 1">
                        <h3 class="text-lg font-medium mb-4">Personal Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label for="firstName">First Name</Label>
                                <Input id="firstName" v-model="formData.firstName" required :aria-invalid="fieldErrors.firstName ? 'true' : 'false'" />
                    <p v-if="fieldErrors.firstName" class="text-sm text-red-600 mt-1">{{ fieldErrors.firstName }}</p>
                  </div>
                  <div>
                    <Label for="lastName">Last Name</Label>
                    <Input id="lastName" v-model="formData.lastName" required :aria-invalid="fieldErrors.lastName ? 'true' : 'false'" />
                     <p v-if="fieldErrors.lastName" class="text-sm text-red-600 mt-1">{{ fieldErrors.lastName }}</p>
                  </div>
                   <div>
                    <Label for="middleName">Middle Name</Label>
                    <Input id="middleName" v-model="formData.middleName" />
                  </div>
                  <div>
                    <Label for="suffix">Suffix (e.g., Jr., Sr.)</Label>
                    <Input id="suffix" v-model="formData.suffix" />
                  </div>
                   <div>
                    <Label for="birthDate">Birth Date</Label>
                    <Input id="birthDate" type="date" v-model="formData.birthDate" required :aria-invalid="fieldErrors.birthDate ? 'true' : 'false'" />
                     <p v-if="fieldErrors.birthDate" class="text-sm text-red-600 mt-1">{{ fieldErrors.birthDate }}</p>
                  </div>
                  <div>
                    <Label for="gender">Gender</Label>
                    <Select v-model="formData.gender" required>
                      <SelectTrigger id="gender" :aria-invalid="fieldErrors.gender ? 'true' : 'false'">
                        <SelectValue placeholder="Select gender" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectGroup>
                          <SelectItem value="Male">Male</SelectItem>
                          <SelectItem value="Female">Female</SelectItem>
                          <SelectItem value="Other">Other</SelectItem>
                          <SelectItem value="Prefer not to say">Prefer not to say</SelectItem>
                        </SelectGroup>
                      </SelectContent>
                    </Select>
                     <p v-if="fieldErrors.gender" class="text-sm text-red-600 mt-1">{{ fieldErrors.gender }}</p>
                  </div>
                   <div>
                    <Label for="nationality">Nationality</Label>
                    <Input id="nationality" v-model="formData.nationality" required :aria-invalid="fieldErrors.nationality ? 'true' : 'false'" />
                     <p v-if="fieldErrors.nationality" class="text-sm text-red-600 mt-1">{{ fieldErrors.nationality }}</p>
                  </div>
                   <div>
                    <Label for="religion">Religion</Label>
                    <Input id="religion" v-model="formData.religion" />
                  </div>
                   <div class="md:col-span-2">
                    <Label for="address">Full Address</Label>
                    <Input id="address" v-model="formData.address" required :aria-invalid="fieldErrors.address ? 'true' : 'false'" />
                     <p v-if="fieldErrors.address" class="text-sm text-red-600 mt-1">{{ fieldErrors.address }}</p>
                  </div>
                   <div>
                    <Label for="contactNumber">Contact Number</Label>
                    <Input id="contactNumber" type="tel" v-model="formData.contactNumber" required :aria-invalid="fieldErrors.contactNumber ? 'true' : 'false'" />
                     <p v-if="fieldErrors.contactNumber" class="text-sm text-red-600 mt-1">{{ fieldErrors.contactNumber }}</p>
                  </div>
                   <div>
                    <Label for="email">Email Address</Label>
                    <Input id="email" type="email" v-model="formData.email" required :aria-invalid="fieldErrors.email ? 'true' : 'false'" />
                     <p v-if="fieldErrors.email" class="text-sm text-red-600 mt-1">{{ fieldErrors.email }}</p>
                        </div> <!-- End email field div -->
                    </div> <!-- End grid div for Step 1 -->
                    </div> <!-- End v-if="currentStep === 1" -->

                    <!-- Step 2: Parent/Guardian Information -->
                    <div v-if="currentStep === 2">
                        <h3 class="text-lg font-medium mb-4">Parent/Guardian Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <h4 class="text-md font-medium mb-2 border-b pb-1">Father's Information</h4>
                    </div>
                    <div>
                        <Label for="fatherName">Father's Full Name</Label>
                        <Input id="fatherName" v-model="formData.fatherName" />
                    </div>
                    <div>
                        <Label for="fatherOccupation">Occupation</Label>
                        <Input id="fatherOccupation" v-model="formData.fatherOccupation" />
                    </div>
                     <div>
                        <Label for="fatherContact">Contact Number</Label>
                        <Input id="fatherContact" type="tel" v-model="formData.fatherContact" />
                    </div>

                    <div class="md:col-span-2 mt-4">
                        <h4 class="text-md font-medium mb-2 border-b pb-1">Mother's Information</h4>
                    </div>
                     <div>
                        <Label for="motherName">Mother's Full Maiden Name</Label>
                        <Input id="motherName" v-model="formData.motherName" />
                    </div>
                    <div>
                        <Label for="motherOccupation">Occupation</Label>
                        <Input id="motherOccupation" v-model="formData.motherOccupation" />
                    </div>
                     <div>
                        <Label for="motherContact">Contact Number</Label>
                        <Input id="motherContact" type="tel" v-model="formData.motherContact" />
                    </div>

                     <div class="md:col-span-2 mt-4">
                        <h4 class="text-md font-medium mb-2 border-b pb-1">Guardian's Information (If not parents)</h4>
                    </div>
                    <div>
                        <Label for="guardianName">Guardian's Full Name</Label>
                        <Input id="guardianName" v-model="formData.guardianName" />
                    </div>
                     <div>
                        <Label for="guardianRelationship">Relationship to Student</Label>
                        <Input id="guardianRelationship" v-model="formData.guardianRelationship" />
                    </div>
                    <div>
                        <Label for="guardianContact">Guardian's Contact Number</Label>
                        <Input id="guardianContact" type="tel" v-model="formData.guardianContact" />
                    </div>
                        </div>
                    </div>

                    <!-- Step 3: Educational Background -->
                    <div v-if="currentStep === 3">
                        <h3 class="text-lg font-medium mb-4">Educational Background</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <Label for="lastSchoolAttended">Last School Attended</Label>
                        <Input id="lastSchoolAttended" v-model="formData.lastSchoolAttended" required />
                    </div>
                     <div class="md:col-span-2">
                        <Label for="lastSchoolAddress">Last School Address</Label>
                        <Input id="lastSchoolAddress" v-model="formData.lastSchoolAddress" required />
                    </div>
                    <div>
                        <Label for="yearLevelCompleted">Highest Year/Grade Level Completed</Label>
                        <Input id="yearLevelCompleted" v-model="formData.yearLevelCompleted" required />
                    </div>
                    <div>
                        <Label for="yearGraduated">Year Graduated/Last Attended</Label>
                        <Input id="yearGraduated" type="number" placeholder="YYYY" v-model="formData.yearGraduated" required />
                    </div>
                     <div>
                        <Label for="lrn">Learner Reference Number (LRN)</Label>
                        <Input id="lrn" v-model="formData.lrn" />
                    </div>
                        </div>
                    </div>

                    <!-- Step 4: Course Selection -->
                    <div v-if="currentStep === 4">
                        <h3 class="text-lg font-medium mb-4">Course/Track Selection</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label for="selectedCourse">Desired Course</Label>
                         <Select v-model="formData.selectedCourse" required>
                            <SelectTrigger id="selectedCourse">
                                <SelectValue placeholder="Select desired course" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectLabel>College Courses</SelectLabel>
                                    <SelectItem value="BSIT">BS Information Technology</SelectItem>
                                    <SelectItem value="BSCS">BS Computer Science</SelectItem>
                                    <SelectItem value="BSECE">BS Electronics Engineering</SelectItem>
                                    <SelectItem value="BSBA">BS Business Administration</SelectItem>
                                    <SelectLabel>Senior High Tracks</SelectLabel>
                                     <SelectItem value="SHS-STEM">SHS - STEM</SelectItem>
                                     <SelectItem value="SHS-ABM">SHS - ABM</SelectItem>
                                     <SelectItem value="SHS-HUMSS">SHS - HUMSS</SelectItem>
                                     <SelectItem value="SHS-GAS">SHS - GAS</SelectItem>
                                     <SelectItem value="SHS-TVL-ICT">SHS - TVL-ICT</SelectItem>
                                     <!-- Add more based on your actual offerings -->
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                         <p v-if="fieldErrors.selectedCourse" class="text-sm text-red-600 mt-1">{{ fieldErrors.selectedCourse }}</p>
                    </div>
                     <div v-if="formData.selectedCourse && formData.selectedCourse.startsWith('SHS-')"> <!-- Example condition -->
                        <Label for="selectedStrand">Desired Strand (SHS)</Label>
                        <!-- You might want another Select here if strands are distinct from tracks -->
                        <Input id="selectedStrand" v-model="formData.selectedStrand" placeholder="e.g., Programming, Animation" />
                    </div>
                    <!-- Add more fields if needed -->
                        </div>
                    </div>

                    <!-- Step 5: Review & Submit -->
                    <div v-if="currentStep === 5">
                        <h3 class="text-lg font-medium mb-4">Review Your Information</h3>
                        <div class="space-y-4">
                            <div class="p-4 border rounded-md bg-muted/30">
                                <h4 class="font-semibold mb-2">Personal Information</h4>
                        <p><strong>Full Name:</strong> {{ formData.firstName }} {{ formData.middleName }} {{ formData.lastName }} {{ formData.suffix }}</p>
                        <p><strong>Birth Date:</strong> {{ formData.birthDate }}</p>
                        <p><strong>Gender:</strong> {{ formData.gender }}</p>
                        <p><strong>Nationality:</strong> {{ formData.nationality }}</p>
                        <p><strong>Religion:</strong> {{ formData.religion }}</p>
                        <p><strong>Address:</strong> {{ formData.address }}</p>
                        <p><strong>Contact:</strong> {{ formData.contactNumber }}</p>
                        <p><strong>Email:</strong> {{ formData.email }}</p>
                    </div>
                     <div class="p-4 border rounded-md bg-muted/30">
                        <h4 class="font-semibold mb-2">Parent/Guardian Information</h4>
                        <p><strong>Father:</strong> {{ formData.fatherName }} ({{ formData.fatherOccupation }}, {{ formData.fatherContact }})</p>
                        <p><strong>Mother:</strong> {{ formData.motherName }} ({{ formData.motherOccupation }}, {{ formData.motherContact }})</p>
                        <p><strong>Guardian:</strong> {{ formData.guardianName }} ({{ formData.guardianRelationship }}, {{ formData.guardianContact }})</p>
                    </div>
                     <div class="p-4 border rounded-md bg-muted/30">
                        <h4 class="font-semibold mb-2">Educational Background</h4>
                        <p><strong>Last School:</strong> {{ formData.lastSchoolAttended }} ({{ formData.lastSchoolAddress }})</p>
                        <p><strong>Level Completed:</strong> {{ formData.yearLevelCompleted }}</p>
                        <p><strong>Year Graduated/Attended:</strong> {{ formData.yearGraduated }}</p>
                        <p><strong>LRN:</strong> {{ formData.lrn }}</p>
                    </div>
                     <div class="p-4 border rounded-md bg-muted/30">
                        <h4 class="font-semibold mb-2">Course Selection</h4>
                        <p><strong>Desired Course:</strong> {{ formData.selectedCourse }}</p>
                            <p v-if="formData.selectedStrand"><strong>Desired Strand:</strong> {{ formData.selectedStrand }}</p>
                        </div> <!-- End Course Selection Review Box -->
                    </div> <!-- End space-y-4 -->
                    <!-- Confirmation text -->
                    <p class="mt-6 text-sm text-gray-600 dark:text-gray-400">
                        By submitting this form, you confirm that all information provided is true and accurate.
                    </p>
                    </div> <!-- End v-if="currentStep === 5" -->

                </div> <!-- End of the keyed div -->
            </transition> <!-- End transition -->
            </form> <!-- End form -->
          </CardContent>
          <CardFooter class="flex justify-between">
            <Button variant="outline" @click="prevStep" :disabled="currentStep === 1">
              Previous
            </Button>
            <Button v-if="currentStep < totalSteps" @click="nextStep">
              Next
            </Button>
            <Button v-if="currentStep === totalSteps" @click="submitForm">
              Submit Enrollment
            </Button>
          </CardFooter>
        </Card>
      </div>
    </div>
  </WebLayout> <!-- Corrected Layout Usage -->
</template>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
