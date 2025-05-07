<script setup>
import { ref, computed, nextTick, watch, onMounted } from 'vue';
import EnrollmentLayout from '@/Layouts/EnrollmentLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3'; // Added Link
import { Button } from '@/Components/shadcn/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/shadcn/ui/card';
import { Input } from '@/Components/shadcn/ui/input';
import { Label } from '@/Components/shadcn/ui/label';
// import { Progress } from '@/Components/shadcn/ui/progress'; // Removed Progress
import { Separator } from '@/Components/shadcn/ui/separator'; // Added Separator
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/Components/shadcn/ui/select';
import { Textarea } from '@/Components/shadcn/ui/textarea';
import { Icon } from '@iconify/vue';
import { Alert, AlertDescription, AlertTitle } from '@/Components/shadcn/ui/alert';
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/Components/shadcn/ui/accordion';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/Components/shadcn/ui/alert-dialog';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/shadcn/ui/avatar';
import { cn } from '@/lib/utils'; // Import cn utility for conditional classes

// --- Props ---
const props = defineProps({
    googleEmail: String,
    googleName: String,
    googleAvatar: String,
    flash: Object,
    courses: Array,
});

// --- Constants ---
const MAJOR_STEPS = {
    STUDENT_TYPE_SELECTION: 0,
    WELCOME: 1,
    PERSONAL: 2,
    CONTACT: 3,
    PARENT_GUARDIAN: 4,
    EDUCATION: 5,
    COURSE: 6,
    REVIEW: 7,
    SUBMITTED: 8,
};

const FIELD_TYPES = {
    TEXT: 'text', EMAIL: 'email', TEL: 'tel', DATE: 'date', NUMBER: 'number', SELECT: 'select', TEXTAREA: 'textarea',
    WELCOME: 'welcome', REVIEW: 'review', SUBMITTED: 'submitted',
};

// --- Field Definitions (Unchanged) ---
const fieldDefinitions = ref([
    { majorStep: MAJOR_STEPS.WELCOME, key: 'welcome', type: FIELD_TYPES.WELCOME },
    { majorStep: MAJOR_STEPS.PERSONAL, key: 'first_name', label: "First Name", type: FIELD_TYPES.TEXT, required: true },
    { majorStep: MAJOR_STEPS.PERSONAL, key: 'last_name', label: "Last Name", type: FIELD_TYPES.TEXT, required: true },
    { majorStep: MAJOR_STEPS.PERSONAL, key: 'middle_name', label: "Middle Name", type: FIELD_TYPES.TEXT, placeholder: "Optional" },
    { majorStep: MAJOR_STEPS.PERSONAL, key: 'birth_date', label: "Date of Birth", type: FIELD_TYPES.DATE, required: true },
    { majorStep: MAJOR_STEPS.PERSONAL, key: 'gender', label: "Gender", type: FIELD_TYPES.SELECT, required: true, options: ['Male', 'Female', 'Other', 'Prefer not to say'] },
    { majorStep: MAJOR_STEPS.PERSONAL, key: 'birthplace', label: "Place of Birth", type: FIELD_TYPES.TEXT, placeholder: "Optional" },
    { majorStep: MAJOR_STEPS.PERSONAL, key: 'civil_status', label: "Civil Status", type: FIELD_TYPES.SELECT, options: ['Single', 'Married', 'Widowed', 'Separated', 'Divorced'], placeholder: "Optional" },
    { majorStep: MAJOR_STEPS.PERSONAL, key: 'citizenship', label: "Citizenship", type: FIELD_TYPES.TEXT, required: true },
    { majorStep: MAJOR_STEPS.PERSONAL, key: 'religion', label: "Religion", type: FIELD_TYPES.TEXT, placeholder: "Optional" },
    { majorStep: MAJOR_STEPS.PERSONAL, key: 'height', label: "Height (cm)", type: FIELD_TYPES.NUMBER, placeholder: "Optional" },
    { majorStep: MAJOR_STEPS.PERSONAL, key: 'weight', label: "Weight (kg)", type: FIELD_TYPES.NUMBER, placeholder: "Optional" },
    { majorStep: MAJOR_STEPS.PERSONAL, key: 'current_adress', label: "Current Address", type: FIELD_TYPES.TEXTAREA, required: true },
    { majorStep: MAJOR_STEPS.PERSONAL, key: 'permanent_address', label: "Permanent Address", type: FIELD_TYPES.TEXTAREA, placeholder: "Optional, if different from current" },
    { majorStep: MAJOR_STEPS.CONTACT, key: 'email', label: "Email Address", type: FIELD_TYPES.EMAIL, required: true },
    { majorStep: MAJOR_STEPS.CONTACT, key: 'personal_contact', label: "Personal Contact Number", type: FIELD_TYPES.TEL, required: true },
    { majorStep: MAJOR_STEPS.CONTACT, key: 'facebook_contact', label: "Facebook Profile URL", type: FIELD_TYPES.TEXT, placeholder: "Optional" },
    { majorStep: MAJOR_STEPS.CONTACT, key: 'emergency_contact_name', label: "Emergency Contact Name", type: FIELD_TYPES.TEXT, required: true },
    { majorStep: MAJOR_STEPS.CONTACT, key: 'emergency_contact_phone', label: "Emergency Contact Phone", type: FIELD_TYPES.TEL, required: true },
    { majorStep: MAJOR_STEPS.CONTACT, key: 'emergency_contact_address', label: "Emergency Contact Address", type: FIELD_TYPES.TEXTAREA, required: true },
    { majorStep: MAJOR_STEPS.PARENT_GUARDIAN, key: 'fathers_name', label: "Father's Full Name", type: FIELD_TYPES.TEXT, placeholder: "Optional" },
    { majorStep: MAJOR_STEPS.PARENT_GUARDIAN, key: 'mothers_name', label: "Mother's Full Maiden Name", type: FIELD_TYPES.TEXT, placeholder: "Optional" },
    { majorStep: MAJOR_STEPS.PARENT_GUARDIAN, key: 'guardianName', label: "Guardian's Name", type: FIELD_TYPES.TEXT, placeholder: "If applicable" },
    { majorStep: MAJOR_STEPS.PARENT_GUARDIAN, key: 'guardianRelationship', label: "Guardian's Relationship", type: FIELD_TYPES.TEXT, placeholder: "If applicable" },
    { majorStep: MAJOR_STEPS.PARENT_GUARDIAN, key: 'guardianContact', label: "Guardian's Contact Number", type: FIELD_TYPES.TEL, placeholder: "If applicable" },
    { majorStep: MAJOR_STEPS.EDUCATION, key: 'elementary_school', label: "Elementary School Name", type: FIELD_TYPES.TEXT, required: true },
    { majorStep: MAJOR_STEPS.EDUCATION, key: 'elementary_school_address', label: "Elementary School Address", type: FIELD_TYPES.TEXT, required: true },
    { majorStep: MAJOR_STEPS.EDUCATION, key: 'elementary_graduate_year', label: "Elementary Graduation Year", type: FIELD_TYPES.NUMBER, required: true, min: 1950, max: new Date().getFullYear() + 1 },
    { majorStep: MAJOR_STEPS.EDUCATION, key: 'junior_high_school_name', label: "Junior High School Name", type: FIELD_TYPES.TEXT, required: true },
    { majorStep: MAJOR_STEPS.EDUCATION, key: 'junior_high_school_address', label: "Junior High School Address", type: FIELD_TYPES.TEXT, required: true },
    { majorStep: MAJOR_STEPS.EDUCATION, key: 'junior_high_graduation_year', label: "Junior High Graduation Year", type: FIELD_TYPES.NUMBER, required: true, min: 1950, max: new Date().getFullYear() + 1 },
    { majorStep: MAJOR_STEPS.EDUCATION, key: 'senior_high_name', label: "Senior High School Name", type: FIELD_TYPES.TEXT, placeholder: "If applicable" },
    { majorStep: MAJOR_STEPS.EDUCATION, key: 'senior_high_address', label: "Senior High School Address", type: FIELD_TYPES.TEXT, placeholder: "If applicable" },
    { majorStep: MAJOR_STEPS.EDUCATION, key: 'senior_high_graduate_year', label: "Senior High Graduation Year", type: FIELD_TYPES.NUMBER, placeholder: "If applicable", min: 1950, max: new Date().getFullYear() + 1 },
    { majorStep: MAJOR_STEPS.EDUCATION, key: 'lrn', label: "Learner Reference Number (LRN)", type: FIELD_TYPES.TEXT, placeholder: "Optional" },
    { majorStep: MAJOR_STEPS.COURSE, key: 'course_id', label: "Course/Track Applying For", type: FIELD_TYPES.SELECT, required: true, options: props.courses },
    // { majorStep: MAJOR_STEPS.COURSE, key: 'academic_year', label: "Academic Year Applying For", type: FIELD_TYPES.SELECT, required: true, options: ['2025-2026', '2026-2027'] },
    { majorStep: MAJOR_STEPS.REVIEW, key: 'review', type: FIELD_TYPES.REVIEW },
    { majorStep: MAJOR_STEPS.SUBMITTED, key: 'submitted', type: FIELD_TYPES.SUBMITTED },
]);

// --- Step Configuration (for Stepper) ---
const getStepTitle = (stepValue) => { /* ... */
     const titles = { [MAJOR_STEPS.WELCOME]: "Welcome", [MAJOR_STEPS.PERSONAL]: "Personal Info", [MAJOR_STEPS.CONTACT]: "Contact Info", [MAJOR_STEPS.PARENT_GUARDIAN]: "Parent/Guardian", [MAJOR_STEPS.EDUCATION]: "Education", [MAJOR_STEPS.COURSE]: "Course", [MAJOR_STEPS.REVIEW]: "Review", }; return titles[stepValue] || `Step ${stepValue}`;
};
const getStepIcon = (stepValue) => { /* ... */
    const icons = { [MAJOR_STEPS.WELCOME]: "lucide:party-popper", [MAJOR_STEPS.PERSONAL]: "lucide:user", [MAJOR_STEPS.CONTACT]: "lucide:phone", [MAJOR_STEPS.PARENT_GUARDIAN]: "lucide:users", [MAJOR_STEPS.EDUCATION]: "lucide:graduation-cap", [MAJOR_STEPS.COURSE]: "lucide:book-marked", [MAJOR_STEPS.REVIEW]: "lucide:check-circle", [MAJOR_STEPS.SUBMITTED]: "lucide:send" }; return icons[stepValue] || 'lucide:circle';
};
const getStepStatus = (stepValue) => { /* ... */
    if (stepValue < currentMajorStep.value) return 'complete'; if (stepValue === currentMajorStep.value) return 'current'; return 'upcoming';
};
const stepperData = computed(() => Object.entries(MAJOR_STEPS)
    .filter(([key, value]) => value > MAJOR_STEPS.STUDENT_TYPE_SELECTION && value < MAJOR_STEPS.SUBMITTED)
    .map(([key, value]) => ({ id: value, title: getStepTitle(value), icon: getStepIcon(value), status: getStepStatus(value) }))
);

const LOCAL_STORAGE_KEY = 'online-enrollment-form';

// --- State ---
const currentMajorStep = ref(MAJOR_STEPS.STUDENT_TYPE_SELECTION);
const form = useForm(
    fieldDefinitions.value.reduce((acc, field) => { if (field.key && ![FIELD_TYPES.WELCOME, FIELD_TYPES.REVIEW, FIELD_TYPES.SUBMITTED].includes(field.type)) { acc[field.key] = ''; } return acc; }, {})
);

// Load from localStorage on mount
onMounted(() => {
    const saved = localStorage.getItem(LOCAL_STORAGE_KEY);
    if (saved) {
        try {
            const parsed = JSON.parse(saved);
            if (parsed.form) {
                Object.entries(parsed.form).forEach(([key, value]) => {
                    if (key in form) form[key] = value;
                });
            }
            if (typeof parsed.currentMajorStep === 'number') {
                currentMajorStep.value = parsed.currentMajorStep;
            }
        } catch (e) { /* ignore */ }
    }
});

// Persist to localStorage on change
watch(
    [form, currentMajorStep],
    () => {
        localStorage.setItem(
            LOCAL_STORAGE_KEY,
            JSON.stringify({
                form: { ...form.data() },
                currentMajorStep: currentMajorStep.value,
            })
        );
    },
    { deep: true }
);

if (props.googleEmail) { /* ... */
    form.defaults({ enrollment_google_email: props.googleEmail, email: props.googleEmail }); form.reset();
}
const selectedCourseDetails = ref(null);
const isLoadingCourseDetails = ref(false);
const courseDetailsError = ref(null);
const showSignInRequiredDialog = ref(false); // Added state for sign-in prompt

// --- Computed Properties ---
const currentStepFields = computed(() => { /* ... */
    return fieldDefinitions.value.filter(f => f.majorStep === currentMajorStep.value && ![FIELD_TYPES.REVIEW, FIELD_TYPES.WELCOME, FIELD_TYPES.SUBMITTED].includes(f.type));
});
const isFieldVisible = (field) => !field.condition || field.condition(form.data());
const formattedEstimatedTuition = computed(() => { /* ... */
    if (!selectedCourseDetails.value?.estimated_tuition) return 'N/A'; return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(selectedCourseDetails.value.estimated_tuition);
});
const groupedSubjects = computed(() => { /* ... */
     if (!selectedCourseDetails.value?.subjects) return {}; const grouped = selectedCourseDetails.value.subjects.reduce((acc, subject) => { const year = subject.academic_year; const semester = subject.semester; if (!acc[year]) acc[year] = {}; if (!acc[year][semester]) acc[year][semester] = { subjects: [], totalUnits: 0 }; acc[year][semester].subjects.push(subject); acc[year][semester].totalUnits += subject.units || 0; return acc; }, {}); const sortedGrouped = {}; Object.keys(grouped).sort((a, b) => a - b).forEach(year => { sortedGrouped[year] = {}; Object.keys(grouped[year]).sort((a, b) => a - b).forEach(semester => { sortedGrouped[year][semester] = grouped[year][semester]; sortedGrouped[year][semester].subjects.sort((a, b) => a.title.localeCompare(b.title)); }); }); return sortedGrouped;
});
const formatAcademicYear = (year) => { /* ... */
    switch (parseInt(year)) { case 1: return '1st Year'; case 2: return '2nd Year'; case 3: return '3rd Year'; case 4: return '4th Year'; default: return `${year}th Year`; }
};
const formatSemester = (semester) => { /* ... */
    switch (parseInt(semester)) { case 1: return '1st Semester'; case 2: return '2nd Semester'; case 3: return 'Summer'; default: return `Semester ${semester}`; }
};

// --- Methods ---
const validateStep = () => { /* ... */
    form.clearErrors(); let isValid = true; const fieldsInStep = fieldDefinitions.value.filter(f => f.majorStep === currentMajorStep.value); fieldsInStep.forEach(field => { if (isFieldVisible(field) && field.required && !form[field.key]) { form.setError(field.key, `${field.label} is required.`); isValid = false; } if (field.type === FIELD_TYPES.EMAIL && form[field.key] && !/\S+@\S+\.\S+/.test(form[field.key])) { form.setError(field.key, `Please enter a valid email address.`); isValid = false; } if (field.type === FIELD_TYPES.NUMBER && form[field.key]) { if (isNaN(Number(form[field.key]))) { form.setError(field.key, `${field.label} must be a number.`); isValid = false; } else { if (field.min !== undefined && Number(form[field.key]) < field.min) { form.setError(field.key, `${field.label} must be ${field.min} or greater.`); isValid = false; } if (field.max !== undefined && Number(form[field.key]) > field.max) { form.setError(field.key, `${field.label} cannot be greater than ${field.max}.`); isValid = false; } } } if (field.type === FIELD_TYPES.TEL && form[field.key] && !/^[+\d\s().-]+$/.test(form[field.key])) { form.setError(field.key, `Please enter a valid phone number.`); isValid = false; } }); if (!isValid) { nextTick(() => { const firstErrorKey = Object.keys(form.errors)[0]; if (firstErrorKey) { const element = document.getElementById(firstErrorKey); element?.focus(); element?.scrollIntoView({ behavior: 'smooth', block: 'center' }); } }); } return isValid;
};
const advance = async () => { /* ... */
    if (currentMajorStep.value === MAJOR_STEPS.WELCOME) { currentMajorStep.value++; window.scrollTo({ top: 0, behavior: 'smooth' }); return; } if (currentMajorStep.value === MAJOR_STEPS.REVIEW) { submitEnrollmentForm(); return; } if (!validateStep()) return; let nextStep = currentMajorStep.value + 1; currentMajorStep.value = nextStep < MAJOR_STEPS.SUBMITTED ? nextStep : MAJOR_STEPS.REVIEW; window.scrollTo({ top: 0, behavior: 'smooth' });
};
const goBack = async () => { /* ... */
     if (currentMajorStep.value <= MAJOR_STEPS.PERSONAL) return; currentMajorStep.value--; window.scrollTo({ top: 0, behavior: 'smooth' });
};

const submitEnrollmentForm = () => {
    if (currentMajorStep.value !== MAJOR_STEPS.REVIEW) return;

    // Check if Google Email is linked (present in props)
    if (!props.googleEmail) {
        // If not linked, show the prompt dialog instead of submitting
        showSignInRequiredDialog.value = true;
        return;
    }

    // If Google Email is linked, proceed with submission
    const payload = { ...form.data() };
    // Ensure the linked google email is included, even if the form field was somehow cleared
    payload.enrollment_google_email = props.googleEmail;
    // Add subjects from the selected course/year/semester to the payload
    payload.subjects = selectedCourseDetails.value?.subjects || [];

    console.log("Submitting payload to pending:", payload);
    form.post(route('pending-enrollment.store'), {
        preserveScroll: true,
        onSuccess: () => {
            currentMajorStep.value = MAJOR_STEPS.SUBMITTED;
            window.scrollTo({ top: 0, behavior: 'smooth' });
            form.reset();
            selectedCourseDetails.value = null;
            localStorage.removeItem(LOCAL_STORAGE_KEY); // <-- clear on success
        },
        onError: (errors) => {
            console.error('Submission Error:', errors);
            const firstErrorKey = Object.keys(errors)[0];
            const fieldWithError = fieldDefinitions.value.find(f => f.key === firstErrorKey);
            if (fieldWithError && fieldWithError.majorStep !== MAJOR_STEPS.REVIEW) {
                currentMajorStep.value = fieldWithError.majorStep;
                 nextTick(() => {
                    document.getElementById(firstErrorKey)?.focus();
                    document.getElementById(firstErrorKey)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                 });
            } else {
                 form.setError('general', errors.message || 'An error occurred. Please review the form or contact support.');
                 alert(form.errors.general);
            }
        },
    });
};

// Function to redirect to Google Sign-In from the prompt
const redirectToGoogleSignIn = () => {
    window.location.href = route('enrollment.google.redirect');
};

const getFieldValue = (key) => { /* ... */
    const field = fieldDefinitions.value.find(f => f.key === key); const value = form[key]; if (value === null || value === undefined || value === '') return 'N/A'; if (field?.type === FIELD_TYPES.SELECT && field.options) { const selectedOption = field.options.find(opt => typeof opt === 'object' ? opt.value == value : opt == value); if (typeof selectedOption === 'object') return selectedOption.label || selectedOption.value; if (selectedOption) return selectedOption; } if (field?.type === FIELD_TYPES.DATE && value) { try { return new Date(value).toLocaleDateString(); } catch (e) { return value; } } return value;
};
const getFieldsForReviewStep = (stepValue) => { /* ... */
    return fieldDefinitions.value.filter(f => f.majorStep === stepValue && isFieldVisible(f) && form[f.key] !== null && form[f.key] !== undefined && form[f.key] !== '');
};
const fetchCourseDetails = async (courseId) => { /* ... */
    if (!courseId) { selectedCourseDetails.value = null; courseDetailsError.value = null; return; } isLoadingCourseDetails.value = true; courseDetailsError.value = null; selectedCourseDetails.value = null; try { const response = await fetch(`/api/courses/${courseId}/details`); if (!response.ok) { throw new Error(`HTTP error! status: ${response.status}`); } const data = await response.json(); selectedCourseDetails.value = data; } catch (error) { console.error("Failed to fetch course details:", error); courseDetailsError.value = "Could not load course details. Please try again later."; } finally { isLoadingCourseDetails.value = false; }
};
watch(() => form.course_id, (newCourseId) => { fetchCourseDetails(newCourseId); });
const goToStep = (stepId) => { /* ... */
    if (stepId < currentMajorStep.value) { currentMajorStep.value = stepId; window.scrollTo({ top: 0, behavior: 'smooth' }); }
};

</script>

<template>
  <Head title="Online Enrollment - Step Form" />

  <EnrollmentLayout>
    <!-- Main container with flex layout for stepper and content -->
    <!-- Added padding: px-4 sm:px-6 lg:px-8 -->
    <div class="flex flex-col md:flex-row gap-8 lg:gap-12 px-4 sm:px-6 lg:px-8">

        <!-- Vertical Stepper (Left Column on Desktop) -->
        <aside v-if="currentMajorStep > MAJOR_STEPS.STUDENT_TYPE_SELECTION && currentMajorStep < MAJOR_STEPS.SUBMITTED"
               class="w-full md:w-64 lg:w-72 flex-shrink-0 order-1 md:order-none">
            <Card class="sticky top-8"> <!-- Make stepper sticky -->
                <CardHeader>
                    <CardTitle class="text-lg">Enrollment Progress</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <nav aria-label="Progress">
                        <ol role="list" class="space-y-0 overflow-hidden">
                            <li v-for="(step, stepIdx) in stepperData" :key="step.id" class="relative pb-0">
                                <!-- Separator line (not for last step) -->
                                <div v-if="stepIdx !== stepperData.length - 1" class="absolute left-5 top-5 -ml-px mt-0.5 h-full w-0.5 bg-gray-300 dark:bg-gray-700" aria-hidden="true" />

                                <div class="relative flex items-start p-4 group">
                                    <span class="flex h-10 items-center">
                                        <span :class="cn(
                                            'relative z-10 flex h-8 w-8 items-center justify-center rounded-full border-2',
                                            step.status === 'complete' ? 'bg-primary border-primary' : '',
                                            step.status === 'current' ? 'border-primary bg-primary/10' : '',
                                            step.status === 'upcoming' ? 'border-gray-300 dark:border-gray-600 bg-background dark:bg-gray-800' : ''
                                        )">
                                            <Icon v-if="step.status === 'complete'" icon="lucide:check" class="h-5 w-5 text-white" aria-hidden="true" />
                                            <Icon v-else-if="step.status === 'current'" :icon="step.icon" class="h-5 w-5 text-primary" aria-hidden="true" />
                                            <Icon v-else :icon="step.icon" class="h-5 w-5 text-gray-400 dark:text-gray-500" aria-hidden="true" />
                                        </span>
                                    </span>
                                    <span class="ml-4 flex min-w-0 flex-col pt-1">
                                        <span :class="cn(
                                            'text-sm font-medium',
                                            step.status === 'current' ? 'text-primary' : '',
                                            step.status === 'complete' ? 'text-foreground' : '',
                                            step.status === 'upcoming' ? 'text-muted-foreground' : ''
                                        )">
                                            {{ step.title }}
                                        </span>
                                    </span>
                                     <!-- Clickable overlay for completed steps -->
                                     <button
                                        v-if="step.status === 'complete'"
                                        @click="goToStep(step.id)"
                                        type="button"
                                        class="absolute inset-0 cursor-pointer focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded-md"
                                        :aria-label="`Go to step: ${step.title}`"
                                    ></button>
                                </div>
                            </li>
                        </ol>
                    </nav>
                </CardContent>
            </Card>
        </aside>

        <!-- Main Content Area (Right Column on Desktop) -->
        <main class="flex-1 order-2 md:order-none min-w-0"> <!-- Added min-w-0 -->
            <!-- Flash Messages -->
             <Alert v-if="flash?.success" variant="success" class="mb-6"><Icon icon="lucide:check-circle" class="h-4 w-4" /><AlertTitle>Success</AlertTitle><AlertDescription>{{ flash.success }}</AlertDescription></Alert>
             <Alert v-if="flash?.error" variant="destructive" class="mb-6"><Icon icon="lucide:alert-triangle" class="h-4 w-4" /><AlertTitle>Error</AlertTitle><AlertDescription>{{ flash.error }}</AlertDescription></Alert>
             <Alert v-if="flash?.warning" variant="warning" class="mb-6"><Icon icon="lucide:alert-circle" class="h-4 w-4" /><AlertTitle>Notice</AlertTitle><AlertDescription>{{ flash.warning }}</AlertDescription></Alert>

            <form @submit.prevent="advance" class="">
                <!-- Student Type Selection Step -->
                 <div v-if="currentMajorStep === MAJOR_STEPS.STUDENT_TYPE_SELECTION" class="py-8">
                    <h3 class="text-2xl font-semibold mb-2 text-center text-gray-800 dark:text-gray-200">Let's Get Started!</h3>
                    <p class="text-center text-muted-foreground mb-8">First, tell us if you're a new student or transferring.</p>
                     <!-- Display Google User Info if signed in -->
                     <div v-if="googleEmail" class="mb-8 flex flex-col items-center gap-3 bg-muted/50 dark:bg-gray-800/40 border dark:border-gray-700 p-4 rounded-lg max-w-md mx-auto">
                         <Avatar size="lg"><AvatarImage :src="googleAvatar" :alt="googleName || googleEmail" /><AvatarFallback>{{ googleName ? googleName.charAt(0).toUpperCase() : googleEmail.charAt(0).toUpperCase() }}</AvatarFallback></Avatar>
                         <div class="text-center"><p class="text-sm font-medium text-foreground">{{ googleName || 'Signed in' }}</p><p class="text-xs text-muted-foreground">{{ googleEmail }}</p></div>
                         <div class="flex items-center justify-center gap-4 mt-2"> <!-- New div wrapper -->
                             <p class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1"><Icon icon="lucide:check-circle" class="w-3 h-3" />Google Account Linked</p>
                             <Link :href="route('enrollment.google.logout')" method="get" as="button" class="text-xs text-muted-foreground hover:text-foreground underline">(Sign Out)</Link> <!-- Added Link -->
                         </div>
                     </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl mx-auto">
                        <!-- New/Freshman Option -->
                        <Card class="hover:shadow-lg hover:border-primary transition-all cursor-pointer text-center p-6 flex flex-col items-center justify-center" @click="currentMajorStep = MAJOR_STEPS.WELCOME">
                            <Icon icon="lucide:user-plus" class="w-12 h-12 text-primary mb-4" /><CardTitle class="text-xl mb-2">New / Freshman</CardTitle><CardDescription>Select this if you are enrolling for the first time or as a freshman.</CardDescription>
                        </Card>
                        <!-- Transferee Option (Triggers Dialog) -->
                        <AlertDialog>
                            <AlertDialogTrigger as-child><Card class="hover:shadow-lg hover:border-orange-500 transition-all cursor-pointer text-center p-6 flex flex-col items-center justify-center"><Icon icon="lucide:users-round" class="w-12 h-12 text-orange-500 mb-4" /><CardTitle class="text-xl mb-2">Transferee</CardTitle><CardDescription>Select this if you are transferring from another school.</CardDescription></Card></AlertDialogTrigger>
                            <AlertDialogContent><AlertDialogHeader><AlertDialogTitle class="flex items-center gap-2"><Icon icon="lucide:info" class="w-6 h-6 text-blue-600" />Transferee Enrollment Information</AlertDialogTitle><AlertDialogDescription class="pt-4 text-base">Online enrollment processing for transferees is currently unavailable.<br /><br />We kindly request that you visit the school campus personally to handle the necessary processing and requirements. Our admissions office will be happy to assist you there.</AlertDialogDescription></AlertDialogHeader><AlertDialogFooter><AlertDialogAction>Okay, Understood</AlertDialogAction></AlertDialogFooter></AlertDialogContent>
                        </AlertDialog>
                    </div>
                    <!-- Google Sign-In Button -->
                     <div v-if="!googleEmail" class="text-center mt-8 pt-6 border-t dark:border-gray-700 max-w-md mx-auto">
                        <p class="text-sm text-muted-foreground mb-3">Optional: Sign in with Google to pre-fill your email and help us track your application.</p>
                        <Button type="button" variant="outline" as="a" :href="route('enrollment.google.redirect')"><Icon icon="logos:google-icon" class="w-4 h-4 mr-2" />Sign in with Google</Button>
                    </div>
                 </div>

                <!-- Welcome Step -->
                <Card v-else-if="currentMajorStep === MAJOR_STEPS.WELCOME" class="border-0 shadow-none">
                    <CardContent class="text-center py-12">
                        <Icon :icon="getStepIcon(MAJOR_STEPS.WELCOME)" class="w-16 h-16 mx-auto text-primary mb-4" /><h3 class="text-2xl font-semibold mb-3 text-gray-800 dark:text-gray-200">Welcome, New Enrollee!</h3>
                        <p class="text-muted-foreground mb-6 max-w-lg mx-auto">Ready to start your journey? This enrollment process will guide you through the necessary steps. Please have your information ready. Your application will be reviewed upon submission.</p>
                        <Button size="lg" @click="advance" type="button">Let's Get Started <Icon icon="lucide:arrow-right" class="ml-2 w-5 h-5" /></Button>
                        <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">Fill out the form step-by-step.</p>
                    </CardContent>
                </Card>

                <!-- Dynamic Form Steps -->
                <Card v-else-if="currentMajorStep > MAJOR_STEPS.WELCOME && currentMajorStep < MAJOR_STEPS.REVIEW" class="border-0 shadow-none p-5">
                     <CardHeader class="px-0 pt-0 pb-6">
                         <CardTitle class="text-xl font-semibold flex items-center gap-2"><Icon :icon="getStepIcon(currentMajorStep) || 'lucide:list'" class="w-6 h-6 text-primary" />{{ getStepTitle(currentMajorStep) }}</CardTitle>
                     </CardHeader>
                     <CardContent class="px-0">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4">
                            <template v-for="field in currentStepFields" :key="field.key">
                                <div v-if="isFieldVisible(field)" class="space-y-2" :class="{ 'md:col-span-2 lg:col-span-3': [FIELD_TYPES.TEXTAREA].includes(field.type), 'lg:col-span-2': ['permanent_address', 'emergency_contact_address', 'elementary_school_address', 'junior_high_school_address', 'senior_high_address'].includes(field.key) }">
                                    <Label :for="field.key">{{ field.label }}<span v-if="field.required" class="text-red-500">*</span></Label>
                                    <Input v-if="[FIELD_TYPES.TEXT, FIELD_TYPES.EMAIL, FIELD_TYPES.TEL, FIELD_TYPES.DATE, FIELD_TYPES.NUMBER].includes(field.type)" :id="field.key" :type="field.type === FIELD_TYPES.NUMBER && field.key.includes('year') ? 'number' : field.type" :min="field.min" :max="field.max" v-model="form[field.key]" :required="field.required" :placeholder="field.placeholder" :aria-invalid="form.errors[field.key] ? 'true' : 'false'" :aria-describedby="form.errors[field.key] ? `${field.key}-error` : undefined" class="w-full" />
                                    <Textarea v-else-if="field.type === FIELD_TYPES.TEXTAREA" :id="field.key" v-model="form[field.key]" :required="field.required" :placeholder="field.placeholder" :aria-invalid="form.errors[field.key] ? 'true' : 'false'" :aria-describedby="form.errors[field.key] ? `${field.key}-error` : undefined" class="w-full min-h-[80px]" rows="3" />
                                    <Select v-else-if="field.type === FIELD_TYPES.SELECT" v-model="form[field.key]" :required="field.required" :aria-invalid="form.errors[field.key] ? 'true' : 'false'" :aria-describedby="form.errors[field.key] ? `${field.key}-error` : undefined">
                                        <SelectTrigger :id="field.key" class="w-full"><SelectValue :placeholder="field.placeholder || `Select ${field.label}`" /></SelectTrigger>
                                        <SelectContent><SelectGroup>
                                            <template v-for="(option, index) in field.options" :key="index">
                                                <SelectItem v-if="typeof option === 'string'" :value="option">{{ option }}</SelectItem>
                                                <SelectItem v-else-if="typeof option === 'object' && option.value !== undefined" :value="option.value">{{ option.label || option.value }}</SelectItem>
                                            </template>
                                        </SelectGroup></SelectContent>
                                    </Select>
                                    <p v-if="form.errors[field.key]" :id="`${field.key}-error`" class="text-sm text-red-600 mt-1">{{ form.errors[field.key] }}</p>
                                </div>
                            </template>
                            <!-- Course Details Display -->
                            <div v-if="currentMajorStep === MAJOR_STEPS.COURSE" class="md:col-span-2 lg:col-span-3 mt-6">
                                <div v-if="isLoadingCourseDetails" class="text-center p-4 text-muted-foreground"><Icon icon="lucide:loader-2" class="w-6 h-6 animate-spin inline-block mr-2" /> Loading course details...</div>
                                <Alert v-else-if="courseDetailsError" variant="destructive"><Icon icon="lucide:alert-triangle" class="h-4 w-4" /><AlertTitle>Error</AlertTitle><AlertDescription>{{ courseDetailsError }}</AlertDescription></Alert>
                                <div v-else-if="selectedCourseDetails" class="space-y-4">
                                    <h4 class="text-lg font-semibold border-b pb-2 mb-3">Course Curriculum & Fees</h4>
                                    <Alert variant="info"><Icon icon="lucide:info" class="h-4 w-4" /><AlertTitle>Estimated Tuition Fee</AlertTitle><AlertDescription class="font-semibold text-lg">{{ formattedEstimatedTuition }}<span class="text-sm font-normal text-muted-foreground ml-1">(Based on {{ selectedCourseDetails.total_units }} units at ₱{{ selectedCourseDetails.lec_per_unit }}/unit. Subject to change.)</span></AlertDescription></Alert>
                                    <div class="space-y-3">
                                        <h5 class="font-medium">Curriculum Overview:</h5>
                                        <Accordion v-if="Object.keys(groupedSubjects).length > 0" type="multiple" class="w-full" collapsible>
                                            <template v-for="(semesters, year) in groupedSubjects" :key="year"><template v-for="(semesterData, semester) in semesters" :key="`${year}-${semester}`">
                                                <AccordionItem :value="`item-${year}-${semester}`" class="border rounded-md px-4 mb-2 bg-background dark:bg-gray-800/50">
                                                    <AccordionTrigger class="text-base hover:no-underline py-3"><div class="flex justify-between w-full pr-2 items-center"><span class="font-semibold">{{ formatAcademicYear(year) }} - {{ formatSemester(semester) }}</span><span class="text-sm font-normal text-muted-foreground bg-muted px-2 py-0.5 rounded">{{ semesterData.totalUnits }} units</span></div></AccordionTrigger>
                                                    <AccordionContent class="pt-2 pb-4"><ul class="list-none space-y-2 text-sm pl-2"><li v-for="subject in semesterData.subjects" :key="subject.id" class="flex justify-between items-center"><span><span class="font-medium text-gray-700 dark:text-gray-300">{{ subject.code }}</span> - {{ subject.title }}</span><span class="text-xs text-muted-foreground ml-2 whitespace-nowrap">{{ subject.units }} units</span></li></ul></AccordionContent>
                                                </AccordionItem>
                                            </template></template>
                                        </Accordion>
                                        <p v-else class="text-sm text-muted-foreground">No curriculum details available for this course.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                     </CardContent>
                     <!-- Footer only shown for form steps -->
                     <CardFooter class="px-0 pb-0 pt-6">
                         <div class="w-full flex justify-between items-center">
                             <Button variant="outline" @click="goBack" :disabled="currentMajorStep <= MAJOR_STEPS.PERSONAL" type="button"><Icon icon="lucide:arrow-left" class="mr-2 w-4 h-4" /> Back</Button>
                             <Button @click="advance" :disabled="form.processing" type="button">Next <Icon icon="lucide:arrow-right" class="ml-2 w-4 h-4" /></Button>
                         </div>
                     </CardFooter>
                </Card>

                <!-- Review Step -->
                 <Card v-else-if="currentMajorStep === MAJOR_STEPS.REVIEW" class="border-0 shadow-none p-4">
                     <CardHeader class="px-0 pt-0 pb-6">
                         <CardTitle class="text-xl font-semibold flex items-center gap-2"><Icon :icon="getStepIcon(MAJOR_STEPS.REVIEW)" class="w-6 h-6 text-primary" />{{ getStepTitle(MAJOR_STEPS.REVIEW) }}</CardTitle>
                         <CardDescription>Please review all the information you provided before submitting for review.</CardDescription>
                     </CardHeader>
                     <CardContent class="px-0">
                        <div class="space-y-6">
                            <template v-for="stepValue in [MAJOR_STEPS.PERSONAL, MAJOR_STEPS.CONTACT, MAJOR_STEPS.PARENT_GUARDIAN, MAJOR_STEPS.EDUCATION, MAJOR_STEPS.COURSE]" :key="`review-${stepValue}`">
                                <Card v-if="getFieldsForReviewStep(stepValue).length > 0" class="bg-muted/30 dark:bg-gray-700/30">
                                    <CardHeader class="pb-2"><CardTitle class="text-lg flex items-center gap-2"><Icon :icon="getStepIcon(stepValue) || 'lucide:list'" class="w-5 h-5 text-muted-foreground" />{{ getStepTitle(stepValue) }}</CardTitle></CardHeader>
                                    <CardContent><dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-2 text-sm">
                                        <template v-for="field in getFieldsForReviewStep(stepValue)" :key="field.key"><div class="py-1 border-b border-dashed dark:border-gray-600 break-words"><dt class="text-muted-foreground font-medium">{{ field.label }}:</dt><dd class="font-normal text-gray-800 dark:text-gray-200">{{ getFieldValue(field.key) }}</dd></div></template>
                                        <div v-if="stepValue === MAJOR_STEPS.COURSE && selectedCourseDetails?.estimated_tuition" class="py-1 border-b border-dashed dark:border-gray-600 break-words sm:col-span-2 lg:col-span-3"><dt class="text-muted-foreground font-medium">Estimated Tuition:</dt><dd class="font-semibold text-gray-800 dark:text-gray-200">{{ formattedEstimatedTuition }}</dd></div>
                                    </dl></CardContent>
                                </Card>
                            </template>
                        </div>
                        <p class="mt-8 text-sm text-gray-600 dark:text-gray-400">By clicking "Submit Application", you confirm that all information provided is true and accurate to the best of your knowledge.</p>
                        <p v-if="form.errors.general" class="text-sm text-red-600 mt-4">{{ form.errors.general }}</p>
                     </CardContent>
                      <CardFooter class="px-0 pb-0 pt-6">
                         <div class="w-full flex justify-between items-center">
                             <Button variant="outline" @click="goBack" :disabled="currentMajorStep <= MAJOR_STEPS.PERSONAL" type="button"><Icon icon="lucide:arrow-left" class="mr-2 w-4 h-4" /> Back</Button>
                             <Button @click="submitEnrollmentForm" :disabled="form.processing" type="button"><Icon v-if="form.processing" icon="lucide:loader-2" class="mr-2 h-4 w-4 animate-spin" /><Icon v-else icon="lucide:send" class="mr-2 w-4 h-4" /><span>{{ form.processing ? 'Submitting...' : 'Submit Application' }}</span></Button>
                         </div>
                     </CardFooter>
                </Card>

                 <!-- Submitted Step -->
                <Card v-else-if="currentMajorStep === MAJOR_STEPS.SUBMITTED" class="border-0 shadow-none">
                    <CardContent class="text-center py-12">
                        <Icon :icon="getStepIcon(MAJOR_STEPS.SUBMITTED)" class="w-20 h-20 mx-auto text-green-500 mb-6" />
                        <h3 class="text-2xl font-semibold mb-3 text-gray-800 dark:text-gray-200">Application Submitted Successfully!</h3>
                        <p class="text-muted-foreground mb-6 max-w-lg mx-auto">Thank you for submitting your enrollment application. We have received your information and it is now pending review. You will be notified once it has been processed.</p>
                        <Button variant="outline" as="a" :href="route('home')"><Icon icon="lucide:home" class="mr-2 w-4 h-4" /> Back to Home</Button>
                    </CardContent>
                </Card>

             </form>
        </main>

    </div> <!-- End Flex Container -->

    <!-- Sign-In Prompt Dialog -->
    <AlertDialog :open="showSignInRequiredDialog" @update:open="showSignInRequiredDialog = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle class="flex items-center gap-2">
            <Icon icon="logos:google-icon" class="w-5 h-5" />
            Google Sign-In Required
            </AlertDialogTitle>
          <AlertDialogDescription>
            To ensure your application is processed correctly and to prevent duplicates, please sign in with your Google account before submitting.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction @click="redirectToGoogleSignIn">
             Sign in with Google
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>

  </EnrollmentLayout>
</template>

<style scoped>
/* Add any specific styles if needed */
/* Example: Ensure stepper list items don't have default list styling */
ol[role="list"] li {
    list-style-type: none;
}
</style>
