<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch, nextTick } from 'vue';
import { Button } from '@/Components/shadcn/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/shadcn/ui/card';
import { Alert, AlertDescription, AlertTitle } from '@/Components/shadcn/ui/alert';
import { Checkbox } from '@/Components/shadcn/ui/checkbox';
import { Progress } from '@/Components/shadcn/ui/progress';
import { Icon } from '@iconify/vue';
import { Badge } from '@/Components/shadcn/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/shadcn/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/shadcn/ui/dialog';
import { Separator } from '@/Components/shadcn/ui/separator';
import { 
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/Components/shadcn/ui/tooltip';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/shadcn/ui/tabs';
import { Input } from '@/Components/shadcn/ui/input';

const props = defineProps({
  student: {
    type: Object,
    required: true,
  },
  subjects: {
    type: Array,
    required: true,
  },
  course: {
    type: Object,
    required: true,
  },
  academicYear: {
    type: Number,
    required: true,
  },
  semester: {
    type: Number,
    required: true,
  },
  schoolYear: {
    type: String,
    required: true,
  },
  previouslyPassedSubjects: {
    type: Array,
    default: () => [],
  },
  currentlyEnrolledSubjects: {
    type: Array,
    default: () => [],
  },
  allEnrolledSubjects: {
    type: Array,
    default: () => [],
  },
  availableClasses: {
    type: Object,
    default: () => ({}),
  },
  generalSettings: {
    type: Object,
    required: true,
  }
});

// Search and filter
const searchQuery = ref('');
const activeTab = ref('recommended');
const statusFilter = ref('all');

// Filter subjects based on search and filters
const filteredSubjects = computed(() => {
  let result = props.subjects;
  
  // Apply search filter
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase();
    result = result.filter(subject => 
      subject.code.toLowerCase().includes(query) || 
      subject.title.toLowerCase().includes(query)
    );
  }
  
  // Apply status filter
  if (statusFilter.value !== 'all') {
    switch (statusFilter.value) {
      case 'available':
        result = result.filter(subject => hasClass(subject) && !isCurrentlyEnrolled(subject));
        break;
      case 'unavailable':
        result = result.filter(subject => !hasClass(subject) && !isCurrentlyEnrolled(subject));
        break;
      case 'enrolled':
        result = result.filter(subject => isCurrentlyEnrolled(subject));
        break;
      case 'completed':
        result = result.filter(subject => isPreviouslyPassed(subject));
        break;
    }
  }
  
  // Apply tab filter
  if (activeTab.value === 'recommended') {
    result = result.filter(subject => 
      subject.academic_year === props.academicYear && 
      subject.semester === props.semester &&
      !isPreviouslyPassed(subject)
    );
  } else if (activeTab.value === 'previous') {
    result = result.filter(subject => 
      (subject.academic_year < props.academicYear || 
       (subject.academic_year === props.academicYear && subject.semester < props.semester)) &&
      !isPreviouslyPassed(subject)
    );
  } else if (activeTab.value === 'future') {
    result = result.filter(subject => 
      (subject.academic_year > props.academicYear || 
       (subject.academic_year === props.academicYear && subject.semester > props.semester))
    );
  } else if (activeTab.value === 'all') {
    // No additional filtering for 'all' tab
  }
  
  return result;
});

// Group subjects by semester and academic year
const groupedSubjects = computed(() => {
  const groups = {};
  
  filteredSubjects.value.forEach(subject => {
    const key = `${subject.academic_year}-${subject.semester}`;
    if (!groups[key]) {
      groups[key] = {
        academicYear: subject.academic_year,
        semester: subject.semester,
        subjects: []
      };
    }
    groups[key].subjects.push(subject);
  });
  
  // Convert to array and sort by academic year and semester
  return Object.values(groups).sort((a, b) => {
    if (a.academicYear !== b.academicYear) {
      return a.academicYear - b.academicYear;
    }
    return a.semester - b.semester;
  });
});

// Check if subject has an assigned class
const hasClass = (subject) => {
  return props.availableClasses[subject.code] !== undefined && 
         props.availableClasses[subject.code].length > 0;
};

// Get class details for a subject (returns first class by default)
const getClassDetails = (subject) => {
  const classes = props.availableClasses[subject.code] || [];
  return classes.length > 0 ? classes[0] : null;
};

// Check if subject is currently enrolled
const isCurrentlyEnrolled = (subject) => {
  return props.currentlyEnrolledSubjects.some(enrolled => enrolled.id === subject.id);
};

// Check if subject was previously passed
const isPreviouslyPassed = (subject) => {
  return props.previouslyPassedSubjects.some(passed => passed.id === subject.id);
};

// Check if subject was previously enrolled in any semester
const wasPreviouslyEnrolled = (subject) => {
  return props.allEnrolledSubjects.some(enrolled => 
    enrolled.subject_id === subject.id && 
    !props.currentlyEnrolledSubjects.some(current => current.id === subject.id)
  );
};

// Check if prerequisites are met for each subject
const prerequisitesMet = computed(() => {
  const result = {};
  
  props.subjects.forEach(subject => {
    if (!subject.pre_riquisite || subject.pre_riquisite.length === 0) {
      result[subject.id] = true;
    } else {
      // Check if student has passed the prerequisite subjects by code
      result[subject.id] = subject.pre_riquisite.every(
        preReqCode => props.previouslyPassedSubjects.some(passed => passed.code === preReqCode)
      );
    }
  });
  
  return result;
});

// Form for selected subjects
const form = useForm({
  subjects: [],
  student_id: props.student.id,
  course_id: props.course.id,
  academic_year: props.academicYear,
  semester: props.semester,
  school_year: props.schoolYear,
  downpayment: 2000,
  payment_method: 'cash',
  totalLectureFee: computed(() => 0),
  totalLabFee: computed(() => 0),
  totalMiscFee: 3500,
  overallTuition: computed(() => 0),
  selectedClasses: {},
});

// Calculate total units and other stats
const totalUnits = computed(() => {
  return form.subjects.reduce((sum, subjectId) => {
    const subject = props.subjects.find(s => s.id === subjectId);
    return sum + (subject ? subject.units : 0);
  }, 0);
});

const totalLectureHours = computed(() => {
  return form.subjects.reduce((sum, subjectId) => {
    const subject = props.subjects.find(s => s.id === subjectId);
    return sum + (subject ? subject.lecture : 0);
  }, 0);
});

const totalLabHours = computed(() => {
  return form.subjects.reduce((sum, subjectId) => {
    const subject = props.subjects.find(s => s.id === subjectId);
    return sum + (subject ? (subject.laboratory || 0) : 0);
  }, 0);
});

// Compute estimated tuition based on units
const estimatedTuition = computed(() => {
  return form.subjects.reduce((sum, subjectId) => {
    const subject = props.subjects.find(s => s.id === subjectId);
    if (!subject) return sum;
    
    // Get per unit rates from the course
    const lectureFeePerUnit = props.course.lec_per_unit || 500; // Default to 500 if not specified
    const labFeePerUnit = props.course.lab_per_unit || 300; // Default to 300 if not specified
    
    // Calculate lecture fee
    const lectureFee = subject.lecture * lectureFeePerUnit;
    
    // Calculate lab fee (if any)
    const labFee = (subject.laboratory || 0) * labFeePerUnit;
    
    return sum + lectureFee + labFee;
  }, 0);
});

// Calculate lecture fees only
const totalLectureFee = computed(() => {
  return form.subjects.reduce((sum, subjectId) => {
    const subject = props.subjects.find(s => s.id === subjectId);
    if (!subject) return sum;
    
    // Get lecture rate from the course
    const lectureFeePerUnit = props.course.lec_per_unit || 500; // Default to 500 if not specified
    
    // Calculate lecture fee
    const lectureFee = subject.lecture * lectureFeePerUnit;
    
    return sum + lectureFee;
  }, 0);
});

// Calculate lab fees only
const totalLabFee = computed(() => {
  return form.subjects.reduce((sum, subjectId) => {
    const subject = props.subjects.find(s => s.id === subjectId);
    if (!subject) return sum;
    
    // Get lab rate from the course
    const labFeePerUnit = props.course.lab_per_unit || 300; // Default to 300 if not specified
    
    // Calculate lab fee (if any)
    const labFee = (subject.laboratory || 0) * labFeePerUnit;
    
    return sum + labFee;
  }, 0);
});

// Selected subject details
const selectedSubjects = computed(() => {
  return form.subjects.map(subjectId => {
    const subject = props.subjects.find(s => s.id === subjectId);
    if (!subject) return null;
    
    // Get the selected class for this subject
    const selectedClass = form.selectedClasses[subjectId];
    
    return {
      id: subject.id,
      code: subject.code,
      title: subject.title,
      units: subject.units,
      lecture: subject.lecture,
      laboratory: subject.laboratory || 0,
      hasClass: hasClass(subject),
      classDetails: selectedClass || getClassDetails(subject)
    };
  }).filter(Boolean);
});

// Format currency
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
};

// Class selection
const showingClassSelection = ref(false);
const currentSelectingSubject = ref(null);
const selectedClass = ref(null);
const pendingSubjectForSection = ref(null);

// Function to get available classes for a subject
const getAvailableClassesForSubject = (subject) => {
  // Guard against null/undefined subject
  if (!subject) return [];
  
  const subjectCode = subject.code;
  if (!props.availableClasses[subjectCode]) {
    return [];
  }
  
  // If the class has an array of sections, return them
  if (Array.isArray(props.availableClasses[subjectCode])) {
    return props.availableClasses[subjectCode];
  }
  
  // If it's a single class instance, wrap it in array
  return [props.availableClasses[subjectCode]];
};

// Function to format class schedule display
const formatClassSchedule = (classObj) => {
  if (!classObj || !classObj.Schedule || classObj.Schedule.length === 0) {
    return 'Schedule not available';
  }
  
  return classObj.Schedule.map(schedule => {
    const day = schedule.day_of_week;
    const startTime = schedule.start_time;
    const endTime = schedule.end_time;
    const room = schedule.room ? schedule.room.name : 'TBA';
    
    return `${day} ${startTime}-${endTime} (${room})`;
  }).join(', ');
};

// Handle subject selection
const toggleSubject = (subjectId) => {
  const index = form.subjects.indexOf(subjectId);
  const subject = props.subjects.find(s => s.id === subjectId);
  
  if (!subject) {
    console.error(`Subject with ID ${subjectId} not found`);
    return;
  }
  
  if (index === -1) {
    // Add to selection if not already selected
    if (!hasClass(subject)) {
      showNoClassWarning(subject);
      return;
    }
    
    // If prerequisites are not met, show warning first
    if (!prerequisitesMet.value[subjectId]) {
      pendingSubjectForSection.value = subject;
      showPrerequisiteWarning(subject);
      return;
    }
    // If prerequisites are met, go straight to section selection
    selectedClass.value = null;
    currentSelectingSubject.value = subject;
    nextTick(() => {
      showingClassSelection.value = true;
    });
  } else {
    // Remove from selection
    form.subjects.splice(index, 1);
    if (form.selectedClasses && form.selectedClasses[subjectId]) {
      delete form.selectedClasses[subjectId];
    }
  }
};

// Initialize selectedClasses in form
form.selectedClasses = {};

// Add class selection to subject
const confirmClassSelection = () => {
  if (!currentSelectingSubject.value) {
    console.error('No subject selected');
    showingClassSelection.value = false;
    return;
  }
  if (!selectedClass.value) {
    console.error('No class section selected');
    return;
  }
  try {
    const subjectId = currentSelectingSubject.value.id;
    if (!form.subjects.includes(subjectId)) {
      form.subjects.push(subjectId);
    }
    form.selectedClasses[subjectId] = selectedClass.value;
    showingClassSelection.value = false;
  } catch (error) {
    console.error('Error during class selection:', error);
  } finally {
    nextTick(() => {
      currentSelectingSubject.value = null;
      selectedClass.value = null;
    });
  }
};

// No classes warning
const showingNoClassWarning = ref(false);
const currentNoClassSubject = ref(null);

const showNoClassWarning = (subject) => {
  currentNoClassSubject.value = subject;
  showingNoClassWarning.value = true;
};

// Prerequisite warning
const showingPrerequisiteWarning = ref(false);
const currentPrerequisiteSubject = ref(null);

const showPrerequisiteWarning = (subject) => {
  currentPrerequisiteSubject.value = subject;
  showingPrerequisiteWarning.value = true;
};

// Show confirmation dialog before submitting
const showingConfirmation = ref(false);
const isSubmitting = ref(false);

// Payment process state
const confirmationStep = ref(1);
const minDownPayment = 2000;
const totalMiscFee = 3500; // Example fixed value
const overallTuition = computed(() => totalLectureFee.value + totalLabFee.value + totalMiscFee);

// Extended form to include payment details
form.downpayment = minDownPayment;
form.payment_method = 'cash';
form.totalLectureFee = computed(() => totalLectureFee.value);
form.totalLabFee = computed(() => totalLabFee.value);
form.totalMiscFee = totalMiscFee;
form.overallTuition = computed(() => overallTuition.value);

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

const isFullPayment = ref(false);
const balance = computed(() => form.overallTuition - form.downpayment);

function selectPaymentMethod(method) {
  if (method.available) {
    form.payment_method = method.key;
  }
}

function handleFullPayment() {
  isFullPayment.value = true;
  form.downpayment = form.overallTuition;
}

function handlePartialPayment() {
  isFullPayment.value = false;
  form.downpayment = minDownPayment;
}

function handleDownPaymentInput(e) {
  let val = Number(e.target.value);
  if (isNaN(val)) val = minDownPayment;
  if (val < minDownPayment) val = minDownPayment;
  if (val > form.overallTuition) val = form.overallTuition;
  form.downpayment = val;
  isFullPayment.value = (val === form.overallTuition);
}

const confirmEnrollment = () => {
  if (form.subjects.length === 0) return;
  showingConfirmation.value = true;
  confirmationStep.value = 1;
};

function goToPaymentStep() {
  confirmationStep.value = 2;
}

// Submit enrollment with payment details
const submitEnrollment = () => {
  isSubmitting.value = true;
  
  // Ensure selectedClasses is properly formatted for submission
  // Convert from object format to array format if needed
  form.post(route('student.enroll.subjects.submit'), {
    onSuccess: () => {
      isSubmitting.value = false;
      showingConfirmation.value = false;
    },
    onError: () => {
      isSubmitting.value = false;
    }
  });
};

// Helper to get the full subject object by ID
const getSubjectById = (id) => {
  return props.subjects.find(s => s.id === id) || {};
};

// Progress percentage for academic completion
const academicProgress = computed(() => {
  const totalRequiredSubjects = props.subjects.length;
  const passedSubjects = props.previouslyPassedSubjects.length;
  
  return Math.round((passedSubjects / totalRequiredSubjects) * 100);
});

// Get semester name
const semesterName = computed(() => {
  return props.semester === 1 ? '1st Semester' : '2nd Semester';
});

// Get academic year name
const academicYearName = computed(() => {
  const years = ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'];
  return years[props.academicYear - 1] || `Year ${props.academicYear}`;
});

// Format academic year and semester for display
const formatAcademicInfo = (academicYear, semester) => {
  const years = ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'];
  const semesters = ['1st Semester', '2nd Semester'];
  
  const yearLabel = years[academicYear - 1] || `Year ${academicYear}`;
  const semesterLabel = semesters[semester - 1] || `Semester ${semester}`;
  
  return `${yearLabel}, ${semesterLabel}`;
};

// Detailed fee breakdown
const showDetailedBreakdown = ref(false);

const confirmPrerequisiteWarning = () => {
  showingPrerequisiteWarning.value = false;
  // Open section selection for the pending subject
  if (pendingSubjectForSection.value) {
    selectedClass.value = null;
    currentSelectingSubject.value = pendingSubjectForSection.value;
    nextTick(() => {
      showingClassSelection.value = true;
    });
    pendingSubjectForSection.value = null;
  }
};
</script>

<template>
  <Head :title="`Enroll in Subjects - ${semesterName}`" />
  <AppLayout :title="`Subject Enrollment - ${semesterName}`">
    <div class="container mx-auto px-4 py-6 space-y-6 max-w-7xl">
      <!-- Header Section -->
      <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
        <div>
          <h1 class="text-3xl font-bold mb-2 flex items-center">
            <Icon icon="ph:graduation-cap-fill" class="mr-3 h-8 w-8 text-primary" />
            Subject Enrollment
          </h1>
          <div class="flex flex-col sm:flex-row sm:items-center text-muted-foreground gap-2 sm:gap-4">
            <div class="flex items-center">
              <Icon icon="ph:calendar-check-duotone" class="mr-1 h-4 w-4" />
              {{ schoolYear }} | {{ semesterName }}
            </div>
            <div class="flex items-center">
              <Icon icon="ph:book-bookmark-duotone" class="mr-1 h-4 w-4" />
              {{ course.title }} ({{ course.code }}) | {{ academicYearName }}
            </div>
          </div>
        </div>
        
        <div class="flex items-center gap-3">
          <Button @click="router.visit(route('dashboard'))" variant="outline" class="flex items-center gap-2">
            <Icon icon="ph:arrow-left-bold" class="h-4 w-4" />
            Back to Dashboard
          </Button>
        </div>
      </div>
      
      <!-- Status Header -->
      <Card class="bg-gradient-to-r from-primary/10 to-primary/5 border-primary/10">
        <CardContent class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="md:col-span-3">
              <div class="space-y-1">
                <h2 class="text-lg font-semibold">Hello, {{ student.first_name || 'Student' }}</h2>
                <p class="text-muted-foreground text-sm">
                  Welcome to the subject enrollment system. Select the subjects you want to enroll in for
                  {{ semesterName }} of School Year {{ schoolYear }}. Subjects with available classes are 
                  highlighted and can be selected.
                </p>
              </div>
              
              <div class="flex items-center space-x-1 mt-4">
                <Badge variant="outline" class="bg-green-50 text-green-700 border-green-200">
                  <Icon icon="ph:check-circle-duotone" class="mr-1 h-4 w-4" />
                  Available
                </Badge>
                <Badge variant="outline" class="bg-amber-50 text-amber-700 border-amber-200">
                  <Icon icon="ph:warning-circle-duotone" class="mr-1 h-4 w-4" />
                  Prerequisites
                </Badge>
                <Badge variant="outline" class="bg-red-50 text-red-700 border-red-200">
                  <Icon icon="ph:x-circle-duotone" class="mr-1 h-4 w-4" />
                  No Class
                </Badge>
                <Badge variant="outline" class="bg-blue-50 text-blue-700 border-blue-200">
                  <Icon icon="ph:check-square-duotone" class="mr-1 h-4 w-4" />
                  Selected
                </Badge>
              </div>
            </div>
            
            <div class="md:col-span-2">
              <div class="bg-white/50 dark:bg-background/50 rounded-lg p-4 border border-border/50">
                <div class="flex justify-between items-center">
                  <h3 class="font-medium">Summary</h3>
                  <Badge>{{ totalUnits }} Units</Badge>
                </div>
                
                <div class="grid grid-cols-2 gap-2 mt-3">
                  <div class="bg-muted/30 p-3 rounded-md text-center">
                    <div class="text-xs text-muted-foreground mb-1">Selected</div>
                    <div class="text-2xl font-bold">{{ form.subjects.length }}</div>
                  </div>
                  <div class="bg-muted/30 p-3 rounded-md text-center">
                    <div class="text-xs text-muted-foreground mb-1">Tuition Est.</div>
                    <div class="text-lg font-semibold">{{ formatCurrency(estimatedTuition) }}</div>
                  </div>
                </div>
                
                <Button 
                  class="w-full mt-3" 
                  :disabled="form.subjects.length === 0 || form.processing"
                  @click="confirmEnrollment"
                >
                  <Icon icon="ph:check-bold" class="mr-2 h-4 w-4" />
                  Confirm Enrollment
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
      
      <!-- Tabs and Filters -->
      <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <Tabs v-model="activeTab" class="w-full sm:w-auto">
            <TabsList class="grid grid-cols-4 w-full sm:w-auto">
              <TabsTrigger value="recommended">Recommended</TabsTrigger>
              <TabsTrigger value="previous">Previous</TabsTrigger>
              <TabsTrigger value="future">Future</TabsTrigger>
              <TabsTrigger value="all">All</TabsTrigger>
            </TabsList>
          </Tabs>
          
          <div class="flex items-center gap-2">
            <Input 
              v-model="searchQuery" 
              placeholder="Search subjects..." 
              class="max-w-xs"
            >
              <template #prepend>
                <Icon icon="ph:magnifying-glass" class="h-4 w-4 text-muted-foreground" />
              </template>
            </Input>
            
            <select 
              v-model="statusFilter" 
              class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
            >
              <option value="all">All statuses</option>
              <option value="available">Available</option>
              <option value="unavailable">Unavailable</option>
              <option value="enrolled">Currently enrolled</option>
              <option value="completed">Completed</option>
            </select>
          </div>
        </div>
        
        <!-- Subjects Display Section -->
        <div class="space-y-6">
          <!-- No results message -->
          <Alert v-if="filteredSubjects.length === 0" variant="default" class="bg-muted">
            <AlertTitle>No subjects found</AlertTitle>
            <AlertDescription>
              Try adjusting your search or filters to see available subjects.
            </AlertDescription>
          </Alert>
          
          <!-- Subject groups by academic year and semester -->
          <div v-for="group in groupedSubjects" :key="`${group.academicYear}-${group.semester}`" class="space-y-4">
            <div class="bg-muted/30 px-4 py-2 rounded-md flex items-center">
              <h3 class="font-medium">{{ formatAcademicInfo(group.academicYear, group.semester) }}</h3>
              <Badge variant="outline" class="ml-3">{{ group.subjects.length }} subjects</Badge>
              <span class="ml-auto text-sm text-muted-foreground">
                {{ group.academicYear === academicYear && group.semester === semester ? '(Current)' : '' }}
              </span>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
              <Card 
                v-for="subject in group.subjects" 
                :key="subject.id"
                :class="[
                  hasClass(subject) ? 'border-green-100 hover:border-green-200' : 'border-red-100',
                  form.subjects.includes(subject.id) ? 'ring-2 ring-primary' : '',
                  isCurrentlyEnrolled(subject) ? 'border-blue-100 bg-blue-50/30' : '',
                  isPreviouslyPassed(subject) ? 'border-gray-100 bg-gray-50/30' : '',
                ]"
              >
                <CardContent class="p-4">
                  <div class="flex justify-between items-start">
                    <div>
                      <div class="flex items-center space-x-2">
                        <div class="font-mono text-sm px-2 py-1 bg-primary/10 rounded text-primary">
                          {{ subject.code }}
                        </div>
                        
                        <TooltipProvider>
                          <Tooltip>
                            <TooltipTrigger asChild>
                              <div>
                                <!-- Status icons -->
                                <Icon 
                                  v-if="isPreviouslyPassed(subject)" 
                                  icon="ph:seal-check-duotone" 
                                  class="h-5 w-5 text-gray-400" 
                                />
                                <Icon 
                                  v-else-if="isCurrentlyEnrolled(subject)" 
                                  icon="ph:notebook-duotone" 
                                  class="h-5 w-5 text-blue-500" 
                                />
                                <Icon 
                                  v-else-if="!hasClass(subject)" 
                                  icon="ph:prohibit-duotone" 
                                  class="h-5 w-5 text-red-500" 
                                />
                                <Icon 
                                  v-else-if="!prerequisitesMet[subject.id]" 
                                  icon="ph:warning-duotone" 
                                  class="h-5 w-5 text-amber-500" 
                                />
                                <Icon 
                                  v-else
                                  icon="ph:check-circle-duotone" 
                                  class="h-5 w-5 text-green-500" 
                                />
                              </div>
                            </TooltipTrigger>
                            <TooltipContent>
                              <p v-if="isPreviouslyPassed(subject)">Completed</p>
                              <p v-else-if="isCurrentlyEnrolled(subject)">Currently enrolled</p>
                              <p v-else-if="!hasClass(subject)">No class available</p>
                              <p v-else-if="!prerequisitesMet[subject.id]">Prerequisites required</p>
                              <p v-else>Available for enrollment</p>
                            </TooltipContent>
                          </Tooltip>
                        </TooltipProvider>
                      </div>
                      
                      <h3 class="font-medium mt-2">{{ subject.title }}</h3>
                      
                      <div class="flex items-center mt-2 text-sm text-muted-foreground space-x-3">
                        <span class="flex items-center">
                          <Icon icon="ph:book-open-duotone" class="h-4 w-4 mr-1" />
                          {{ subject.units }} units
                        </span>
                        
                        <span class="flex items-center">
                          <Icon icon="ph:chalkboard-teacher-duotone" class="h-4 w-4 mr-1" />
                          {{ subject.lecture }} lec
                        </span>
                        
                        <span v-if="subject.laboratory > 0" class="flex items-center">
                          <Icon icon="ph:flask-duotone" class="h-4 w-4 mr-1" />
                          {{ subject.laboratory }} lab
                        </span>
                      </div>
                      
                      <!-- Prerequisites indicator -->
                      <div v-if="subject.pre_riquisite && subject.pre_riquisite.length > 0" class="mt-2">
                        <Button 
                          v-if="!prerequisitesMet[subject.id]" 
                          variant="ghost" 
                          size="sm" 
                          class="p-0 h-auto text-xs text-amber-600 hover:text-amber-700 hover:bg-transparent"
                          @click="showPrerequisiteWarning(subject)"
                        >
                          <Icon icon="ph:warning-duotone" class="h-4 w-4 mr-1" />
                          Prerequisites required
                        </Button>
                        <span 
                          v-else 
                          class="text-xs text-green-600"
                        >
                          <Icon icon="ph:check-duotone" class="h-3 w-3 mr-1 inline" />
                          Prerequisites met
                        </span>
                      </div>
                      
                      <!-- Class schedule preview if available -->
                      <div v-if="hasClass(subject)" class="mt-2 text-xs text-muted-foreground">
                        <div class="flex items-center">
                          <Icon icon="ph:user-duotone" class="h-3 w-3 mr-1" />
                          {{ getClassDetails(subject)?.faculty?.faculty_full_name || 'TBA' }}
                        </div>
                        
                        <div v-if="getClassDetails(subject)?.Schedule?.length" class="flex items-center mt-1">
                          <Icon icon="ph:clock-duotone" class="h-3 w-3 mr-1" />
                          <span>Has scheduled class times</span>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Checkbox for selection -->
                    <div>
                      <Checkbox 
                        :checked="form.subjects.includes(subject.id)"
                        :disabled="
                          !hasClass(subject) || 
                          isPreviouslyPassed(subject) || 
                          isCurrentlyEnrolled(subject)
                        "
                        @update:checked="() => toggleSubject(subject.id)"
                        class="data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground"
                      />
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Selected Subjects Summary Card (Mobile) -->
      <div class="lg:hidden">
        <Card v-if="form.subjects.length > 0" class="sticky bottom-4 border-primary/20 bg-background/95 backdrop-blur-sm shadow-lg">
          <CardContent class="p-4">
            <div class="flex justify-between items-center">
              <div>
                <h3 class="font-medium">Selected Subjects</h3>
                <div class="text-sm text-muted-foreground">
                  {{ form.subjects.length }} subjects, {{ totalUnits }} units
                </div>
              </div>
              
              <Button @click="confirmEnrollment" size="sm">
                Confirm Enrollment
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
      
      <!-- Prerequisites Warning Dialog -->
      <Dialog v-model:open="showingPrerequisiteWarning">
        <DialogContent>
          <DialogHeader>
            <DialogTitle class="text-amber-600 flex items-center">
              <Icon icon="ph:warning-duotone" class="mr-2 h-5 w-5" />
              Prerequisites Warning
            </DialogTitle>
            <DialogDescription>
              The following prerequisites are recommended before enrolling in {{ currentPrerequisiteSubject?.title }}:
            </DialogDescription>
          </DialogHeader>
          
          <div class="space-y-2 mt-2 max-h-[40vh] overflow-y-auto">
            <div v-for="preReqId in currentPrerequisiteSubject?.pre_riquisite || []" :key="preReqId">
              <div class="p-3 border rounded bg-amber-50/50 border-amber-200">
                {{ props.subjects.find(s => s.id === preReqId)?.title || 'Unknown Subject' }}
                <span class="text-xs text-muted-foreground flex items-center mt-1">
                  <Icon icon="ph:code-duotone" class="mr-1 h-3 w-3" />
                  {{ props.subjects.find(s => s.id === preReqId)?.code || '' }}
                </span>
              </div>
            </div>
          </div>
          
          <Alert variant="warning" class="mt-4">
            <AlertDescription class="text-sm">
              You can still enroll in this subject even without completing the prerequisites, but it may be more challenging.
            </AlertDescription>
          </Alert>
          
          <DialogFooter class="flex justify-between">
            <Button variant="outline" @click="showingPrerequisiteWarning = false">
              Cancel
            </Button>
            <Button 
              variant="default" 
              @click="confirmPrerequisiteWarning"
            >
              Enroll Anyway
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
      
      <!-- No Class Available Warning Dialog -->
      <Dialog v-model:open="showingNoClassWarning">
        <DialogContent>
          <DialogHeader>
            <DialogTitle class="text-red-600 flex items-center">
              <Icon icon="ph:prohibit-duotone" class="mr-2 h-5 w-5" />
              No Class Available
            </DialogTitle>
            <DialogDescription>
              {{ currentNoClassSubject?.title }} does not have any classes scheduled for {{ semesterName }} of {{ schoolYear }}.
            </DialogDescription>
          </DialogHeader>
          
          <div class="mt-4 p-4 bg-red-50 rounded-md text-sm">
            <p>You cannot enroll in this subject as there are no available classes. Please contact your academic advisor if you believe this is an error.</p>
            
            <div class="mt-3 p-2 bg-white/50 rounded border border-red-100">
              <div class="font-medium">{{ currentNoClassSubject?.title }}</div>
              <div class="text-xs text-muted-foreground mt-1">{{ currentNoClassSubject?.code }} - {{ currentNoClassSubject?.units }} units</div>
            </div>
          </div>
          
          <DialogFooter>
            <Button @click="showingNoClassWarning = false">
              Close
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
      
      <!-- Class Selection Dialog -->
      <Dialog v-model:open="showingClassSelection">
        <DialogContent>
          <DialogHeader>
            <DialogTitle class="flex items-center">
              <Icon icon="ph:chalkboard-teacher" class="mr-2 h-5 w-5 text-primary" />
              Select Class Section
            </DialogTitle>
            <DialogDescription>
              Please select a section for {{ currentSelectingSubject?.title || 'this subject' }}
            </DialogDescription>
          </DialogHeader>
          
          <div v-if="currentSelectingSubject" class="space-y-4 my-4">
            <div class="bg-primary/5 p-3 rounded-md">
              <h4 class="font-medium">{{ currentSelectingSubject.title }}</h4>
              <div class="text-sm text-muted-foreground">
                {{ currentSelectingSubject.code }} - {{ currentSelectingSubject.units }} units
              </div>
            </div>
            
            <div class="space-y-2">
              <label class="block text-sm font-medium">Available Sections:</label>
              <div class="max-h-[30vh] overflow-y-auto space-y-2">
                <Card 
                  v-for="(classObj, index) in getAvailableClassesForSubject(currentSelectingSubject)"
                  :key="index"
                  :class="[
                    'cursor-pointer transition-all',
                    selectedClass === classObj ? 'border-primary bg-primary/5' : 'hover:border-primary/50'
                  ]"
                  @click="selectedClass = classObj"
                >
                  <CardContent class="p-3">
                    <div class="flex justify-between">
                      <div>
                        <div class="font-medium">Section: {{ classObj.section || 'Default' }}</div>
                        <div class="text-xs text-muted-foreground mt-1">
                          <div class="flex items-center gap-1">
                            <Icon icon="ph:user" class="h-3 w-3" />
                            {{ classObj.faculty?.faculty_full_name || 'TBA' }}
                          </div>
                          <div class="flex items-center gap-1 mt-0.5">
                            <Icon icon="ph:clock" class="h-3 w-3" />
                            {{ formatClassSchedule(classObj) }}
                          </div>
                          <div class="flex items-center gap-1 mt-0.5">
                            <Icon icon="ph:users" class="h-3 w-3" />
                            {{ classObj.student_count || 0 }} enrolled
                          </div>
                        </div>
                      </div>
                      <div v-if="selectedClass === classObj" class="text-primary">
                        <Icon icon="ph:check-circle-fill" class="h-5 w-5" />
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </div>
            </div>
          </div>
          <div v-else class="my-4 p-4 bg-amber-50 rounded-md">
            <p class="text-amber-700">No subject selected or subject information is unavailable.</p>
          </div>
          
          <DialogFooter>
            <Button variant="outline" @click="showingClassSelection = false">
              Cancel
            </Button>
            <Button 
              variant="default" 
              @click="confirmClassSelection"
              :disabled="!selectedClass || !currentSelectingSubject"
            >
              Select Section
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
      
      <!-- Enrollment Confirmation Dialog -->
      <Dialog v-model:open="showingConfirmation">
        <DialogContent class="max-w-2xl">
          <DialogHeader>
            <DialogTitle class="flex items-center">
              <Icon icon="ph:clipboard-text-duotone" class="mr-2 h-5 w-5 text-primary" />
              {{ confirmationStep === 1 ? 'Confirm Enrollment' : 'Payment Details' }}
            </DialogTitle>
            <DialogDescription>
              {{ confirmationStep === 1 ? 'Please review your selected subjects before proceeding to payment.' : 'Please select your payment method and specify your down payment amount.' }}
            </DialogDescription>
          </DialogHeader>
          
          <!-- Step Indicator -->
          <div class="flex items-center justify-center gap-2 mt-4 mb-6">
            <div class="flex items-center gap-2" :class="confirmationStep === 1 ? '' : 'opacity-60'">
              <span class="rounded-full bg-primary text-white w-8 h-8 flex items-center justify-center font-bold">1</span>
              <span class="font-semibold text-primary">Review Subjects</span>
            </div>
            <Separator class="w-8 mx-2" />
            <div class="flex items-center gap-2" :class="confirmationStep === 2 ? '' : 'opacity-60'">
              <span 
                class="rounded-full w-8 h-8 flex items-center justify-center font-bold" 
                :class="confirmationStep === 2 ? 'bg-primary text-white' : 'bg-muted text-foreground'"
              >2</span>
              <span :class="confirmationStep === 2 ? 'font-semibold text-primary' : 'font-semibold'">Payment Details</span>
            </div>
          </div>
          
          <!-- Step 1: Subject Review -->
          <div v-if="confirmationStep === 1" class="space-y-4 my-4">
            <div>
              <h4 class="font-medium mb-2 flex items-center">
                <Icon icon="ph:list-checks-duotone" class="mr-1 h-4 w-4" />
                Selected Subjects:
              </h4>
              <div class="max-h-[30vh] overflow-y-auto">
                <div v-for="subject in selectedSubjects" :key="subject.id" 
                     class="flex justify-between p-3 mb-2 border rounded-md bg-muted/20">
                  <div>
                    <div class="font-medium">{{ subject.title }}</div>
                    <div class="text-xs text-muted-foreground mt-1">{{ subject.code }}</div>
                    <div v-if="subject.classDetails" class="text-xs text-muted-foreground mt-1">
                      <span class="font-medium">Section:</span> {{ subject.classDetails.section || 'Default' }}
                      <div class="text-xs mt-0.5">
                        <span class="font-medium">Instructor:</span> {{ subject.classDetails.faculty?.faculty_full_name || 'TBA' }}
                      </div>
                    </div>
                  </div>
                  <div class="text-right">
                    <div>{{ subject.units }} units</div>
                    <Button 
                      variant="ghost" 
                      size="sm" 
                      class="h-6 py-0 px-1 text-xs text-destructive hover:text-destructive/80 hover:bg-destructive/10"
                      @click="toggleSubject(subject.id)"
                    >
                      Remove
                    </Button>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="bg-primary/5 p-4 rounded-md">
              <h4 class="font-medium mb-2 flex items-center">
                <Icon icon="ph:calculator-duotone" class="mr-1 h-4 w-4" />
                Summary:
              </h4>
              <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <div>Total Subjects:</div>
                <div class="text-right font-medium">{{ form.subjects.length }}</div>
                
                <div>Total Units:</div>
                <div class="text-right font-medium">{{ totalUnits }}</div>
                
                <div>Lecture Hours:</div>
                <div class="text-right">{{ totalLectureHours }}</div>
                
                <div>Lab Hours:</div>
                <div class="text-right">{{ totalLabHours }}</div>
                
                <div class="font-medium pt-2 border-t">Estimated Tuition:</div>
                <div class="text-right font-bold text-primary pt-2 border-t">{{ formatCurrency(estimatedTuition) }}</div>
              </div>
            </div>
            
            <Alert variant="default" class="bg-amber-50 border-amber-200">
              <AlertTitle class="flex items-center text-amber-700">
                <Icon icon="ph:info-duotone" class="mr-1 h-4 w-4" />
                Important
              </AlertTitle>
              <AlertDescription class="text-amber-800">
                By proceeding, you'll be able to review your fee breakdown and select your payment options in the next step.
              </AlertDescription>
            </Alert>
          </div>
          
          <!-- Step 2: Payment Process -->
          <div v-if="confirmationStep === 2" class="space-y-4 my-4">
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
            
            <!-- Detailed Subject Fee Breakdown (Collapsible) -->
            <div class="border rounded-md overflow-hidden mb-4">
              <div 
                class="bg-muted/30 p-3 flex justify-between items-center cursor-pointer hover:bg-muted/40"
                @click="showDetailedBreakdown = !showDetailedBreakdown"
              >
                <span class="font-medium">Detailed Fee Breakdown</span>
                <Icon 
                  :icon="showDetailedBreakdown ? 'ph:caret-up' : 'ph:caret-down'" 
                  class="h-4 w-4"
                />
              </div>
              <div v-show="showDetailedBreakdown" class="p-3 space-y-3">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Subject</TableHead>
                      <TableHead>Units</TableHead>
                      <TableHead>Lecture</TableHead>
                      <TableHead>Laboratory</TableHead>
                      <TableHead>Total</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="subject in selectedSubjects" :key="subject.id">
                      <TableCell class="font-medium">{{ subject.code }}</TableCell>
                      <TableCell>{{ subject.units }}</TableCell>
                      <TableCell>
                        {{ subject.lecture }} × ₱{{ props.course.lec_per_unit || 500 }} = 
                        {{ formatCurrency(subject.lecture * (props.course.lec_per_unit || 500)) }}
                      </TableCell>
                      <TableCell>
                        <span v-if="subject.laboratory">
                          {{ subject.laboratory }} × ₱{{ props.course.lab_per_unit || 300 }} = 
                          {{ formatCurrency(subject.laboratory * (props.course.lab_per_unit || 300)) }}
                        </span>
                        <span v-else>-</span>
                      </TableCell>
                      <TableCell class="font-semibold">
                        {{ formatCurrency(
                          (subject.lecture * (props.course.lec_per_unit || 500)) + 
                          (subject.laboratory * (props.course.lab_per_unit || 300))
                        ) }}
                      </TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell colspan="4" class="text-right font-bold">Subtotal:</TableCell>
                      <TableCell class="font-bold">{{ formatCurrency(totalLectureFee + totalLabFee) }}</TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell colspan="4" class="text-right font-bold">Miscellaneous Fee:</TableCell>
                      <TableCell class="font-bold">{{ formatCurrency(totalMiscFee) }}</TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell colspan="4" class="text-right font-bold text-primary">Total Fee:</TableCell>
                      <TableCell class="font-bold text-primary">{{ formatCurrency(overallTuition) }}</TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
                <div class="text-xs text-muted-foreground pt-2 border-t">
                  <p>
                    <span class="font-medium">Note:</span> 
                    Lecture fees are calculated at ₱{{ props.course.lec_per_unit || 500 }} per unit. 
                    Laboratory fees are calculated at ₱{{ props.course.lab_per_unit || 300 }} per laboratory hour.
                  </p>
                </div>
              </div>
            </div>

            <!-- Payment Method Selection -->
            <div>
              <div class="mb-2 font-semibold text-base flex items-center gap-2">
                <Icon icon="mdi:credit-card-outline" class="w-5 h-5 text-primary" />
                Payment Method
              </div>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <Card v-for="method in paymentMethods" :key="method.key"
                      :class="[
                        'transition-all duration-200 cursor-pointer',
                        method.available ? 'border-primary/50 shadow-sm' : 'opacity-60 border-border',
                        form.payment_method === method.key && method.available ? 'bg-primary/10 border-2 border-primary' : ''
                      ]"
                      @click="selectPaymentMethod(method)"
                      :tabindex="method.available ? 0 : -1"
                      :aria-disabled="!method.available"
                >
                  <CardContent class="flex flex-col items-center justify-center gap-1 py-4 px-2">
                    <Icon :icon="method.icon" class="w-6 h-6" :class="method.available ? 'text-primary' : 'text-gray-400 dark:text-gray-600'" />
                    <span class="font-semibold text-sm" :class="method.available ? 'text-primary' : 'text-muted-foreground'">{{ method.name }}</span>
                    <span v-if="!method.available" class="text-xs text-muted-foreground">Soon</span>
                  </CardContent>
                </Card>
              </div>
              <p class="text-xs text-muted-foreground mt-2">Currently, only <span class="font-semibold text-primary">Cash</span> payment is available. Other methods will be enabled soon.</p>
            </div>

            <!-- Down Payment Input -->
            <div class="space-y-2 mt-4">
              <label class="block font-medium mb-1">How much will you pay now?</label>
              <div class="flex gap-2 mb-2">
                <Button :variant="!isFullPayment ? 'default' : 'outline'" @click="handlePartialPayment" size="sm">Partial (Min ₱{{ minDownPayment }})</Button>
                <Button :variant="isFullPayment ? 'default' : 'outline'" @click="handleFullPayment" size="sm">Full Payment</Button>
              </div>
              <input type="number" :min="minDownPayment" :max="overallTuition" v-model="form.downpayment" @input="handleDownPaymentInput" :disabled="isFullPayment" class="w-full border rounded px-3 py-2" />
              <p class="text-xs text-muted-foreground">Minimum down payment is ₱{{ minDownPayment }}. You may pay in full if you wish.</p>
            </div>

            <!-- Balance Summary -->
            <div class="flex justify-between items-center py-2 border-t border-border mt-4">
              <span class="font-medium">Balance after payment:</span>
              <span class="font-bold text-red-500">{{ formatCurrency(balance) }}</span>
            </div>

            <Alert variant="info">
              <AlertDescription class="text-xs">
                You can pay the minimum down payment now. The remaining balance can be paid at the cashier according to your payment schedule.
              </AlertDescription>
            </Alert>
          </div>
          
          <DialogFooter>
            <Button 
              v-if="confirmationStep === 2" 
              variant="outline" 
              @click="confirmationStep = 1" 
              :disabled="isSubmitting"
            >
              Back
            </Button>
            <Button 
              v-if="confirmationStep === 1" 
              variant="outline" 
              @click="showingConfirmation = false" 
              :disabled="isSubmitting"
            >
              Cancel
            </Button>
            <Button 
              v-if="confirmationStep === 1" 
              @click="goToPaymentStep" 
              :disabled="isSubmitting || form.subjects.length === 0"
            >
              Proceed to Payment
            </Button>
            <Button 
              v-if="confirmationStep === 2" 
              @click="submitEnrollment" 
              :disabled="isSubmitting"
            >
              <Icon v-if="isSubmitting" icon="ph:spinner-gap-duotone" class="mr-2 h-4 w-4 animate-spin" />
              <Icon v-else icon="ph:check-bold" class="mr-2 h-4 w-4" />
              {{ isSubmitting ? 'Processing...' : 'Confirm & Submit Enrollment' }}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template> 