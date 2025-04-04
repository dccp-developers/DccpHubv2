<script setup>
import { computed, ref, onMounted, watch } from 'vue';
import SubjectNode from './SubjectNode.vue';
import ConnectionLines from './ConnectionLines.vue';
import { Button } from "@/Components/shadcn/ui/button";
import { 
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/Components/shadcn/ui/dialog";
import { Badge } from "@/Components/shadcn/ui/badge";
import { Progress } from "@/Components/shadcn/ui/progress";

const props = defineProps({
  subjects: {
    type: Array,
    required: true,
  },
  viewMode: {
    type: String,
    required: true,
  },
  showLabels: {
    type: Boolean,
    required: true,
  },
  zoomLevel: {
    type: Number,
    required: true,
  },
  highlightMode: {
    type: String,
    required: true,
  },
});

// State
const container = ref(null);
const nodePositions = ref({});
const selectedSubject = ref(null);
const hoveredSubject = ref(null);
const dialogOpen = ref(false);
const isLoading = ref(true);

// Computed properties
const subjectsByYearAndSemester = computed(() => {
  const grouped = {};
  
  // Find the unique years and semesters
  const years = [...new Set(props.subjects.map(s => s.academic_year))].sort();
  const semesters = [...new Set(props.subjects.map(s => s.semester))].sort();
  
  // Initialize the structure
  years.forEach(year => {
    grouped[year] = {};
    semesters.forEach(semester => {
      grouped[year][semester] = [];
    });
  });
  
  // Fill in the subjects
  props.subjects.forEach(subject => {
    if (grouped[subject.academic_year] && grouped[subject.academic_year][subject.semester]) {
      grouped[subject.academic_year][subject.semester].push(subject);
    }
  });
  
  return grouped;
});

// Generate subject connections based on prerequisites
const subjectConnections = computed(() => {
  const connections = [];
  const subjectMap = {};
  
  // Create a map of subject codes to subjects
  props.subjects.forEach(subject => {
    subjectMap[subject.code] = subject;
  });
  
  // For each subject, check its prerequisites
  props.subjects.forEach(subject => {
    // In a real implementation, you would use subject.pre_riquisite
    // For now, we'll create some example connections based on academic year and semester
    
    // Simulate prerequisites: subjects from previous semesters/years
    if (subject.academic_year > 1 || subject.semester > 1) {
      // Find potential prerequisites (subjects from earlier years/semesters)
      const potentialPrereqs = props.subjects.filter(s => {
        return (s.academic_year < subject.academic_year) || 
               (s.academic_year === subject.academic_year && s.semester < subject.semester);
      });
      
      // Randomly select 0-2 prerequisites
      if (potentialPrereqs.length > 0) {
        const numPrereqs = Math.floor(Math.random() * Math.min(3, potentialPrereqs.length));
        for (let i = 0; i < numPrereqs; i++) {
          const randomIndex = Math.floor(Math.random() * potentialPrereqs.length);
          const prereq = potentialPrereqs[randomIndex];
          
          connections.push({
            from: prereq.code,
            to: subject.code,
            type: 'prerequisite'
          });
          
          // Remove this prerequisite to avoid duplicates
          potentialPrereqs.splice(randomIndex, 1);
        }
      }
    }
    
    // Simulate corequisites: subjects from the same semester
    const potentialCoreqs = props.subjects.filter(s => {
      return s.code !== subject.code && 
             s.academic_year === subject.academic_year && 
             s.semester === subject.semester;
    });
    
    // Randomly select 0-1 corequisites
    if (potentialCoreqs.length > 0 && Math.random() > 0.7) {
      const randomIndex = Math.floor(Math.random() * potentialCoreqs.length);
      const coreq = potentialCoreqs[randomIndex];
      
      // Only add if this connection doesn't already exist in reverse
      const existingConnection = connections.find(c => 
        c.from === coreq.code && c.to === subject.code && c.type === 'corequisite'
      );
      
      if (!existingConnection) {
        connections.push({
          from: subject.code,
          to: coreq.code,
          type: 'corequisite'
        });
      }
    }
  });
  
  return connections;
});

// Highlighted connections based on selected/hovered subject
const highlightedConnections = computed(() => {
  if (!hoveredSubject.value && !selectedSubject.value) return [];
  
  const subjectCode = (hoveredSubject.value || selectedSubject.value).code;
  
  return subjectConnections.value.filter(conn => 
    conn.from === subjectCode || conn.to === subjectCode
  );
});

// Determine if a subject is a prerequisite of the hovered/selected subject
const isPrerequisite = (subject) => {
  if (!hoveredSubject.value && !selectedSubject.value) return false;
  
  const targetSubject = hoveredSubject.value || selectedSubject.value;
  return subjectConnections.value.some(conn => 
    conn.from === subject.code && conn.to === targetSubject.code
  );
};

// Determine if a subject is a postrequisite of the hovered/selected subject
const isPostrequisite = (subject) => {
  if (!hoveredSubject.value && !selectedSubject.value) return false;
  
  const targetSubject = hoveredSubject.value || selectedSubject.value;
  return subjectConnections.value.some(conn => 
    conn.to === subject.code && conn.from === targetSubject.code
  );
};

// Determine if a subject is a corequisite of the hovered/selected subject
const isCorequisite = (subject) => {
  if (!hoveredSubject.value && !selectedSubject.value) return false;
  
  const targetSubject = hoveredSubject.value || selectedSubject.value;
  return subjectConnections.value.some(conn => 
    (conn.from === subject.code && conn.to === targetSubject.code && conn.type === 'corequisite') ||
    (conn.to === subject.code && conn.from === targetSubject.code && conn.type === 'corequisite')
  );
};

// Handle subject node click
const handleSubjectClick = (subject) => {
  selectedSubject.value = subject;
  dialogOpen.value = true;
};

// Handle subject node hover
const handleSubjectHover = (subject) => {
  if (props.highlightMode !== 'none') {
    hoveredSubject.value = subject;
  }
};

// Handle subject node hover end
const handleSubjectHoverEnd = () => {
  hoveredSubject.value = null;
};

// Close the subject details dialog
const closeDialog = () => {
  dialogOpen.value = false;
  setTimeout(() => {
    selectedSubject.value = null;
  }, 300);
};

// Update node positions for drawing connections
const updateNodePositions = () => {
  if (!container.value) return;
  
  const containerRect = container.value.getBoundingClientRect();
  const newPositions = {};
  
  props.subjects.forEach(subject => {
    const nodeElement = document.getElementById(`subject-${subject.code}`);
    if (nodeElement) {
      const rect = nodeElement.getBoundingClientRect();
      newPositions[subject.code] = {
        x: rect.left - containerRect.left,
        y: rect.top - containerRect.top,
        width: rect.width,
        height: rect.height
      };
    }
  });
  
  nodePositions.value = newPositions;
  isLoading.value = false;
};

// Get status badge variant
const getStatusBadgeVariant = (status) => {
  switch (status) {
    case "Completed":
      return "success";
    case "Ongoing":
      return "warning";
    case "Incomplete":
      return "secondary";
    default:
      return "default";
  }
};

// Get progress value based on status
const getProgressValue = (status) => {
  switch (status) {
    case "Completed":
      return 100;
    case "Ongoing":
      return 50;
    case "Incomplete":
      return 0;
    default:
      return 0;
  }
};

// Get prerequisites for a subject
const getPrerequisites = (subject) => {
  return subjectConnections.value
    .filter(conn => conn.to === subject.code && conn.type === 'prerequisite')
    .map(conn => props.subjects.find(s => s.code === conn.from))
    .filter(Boolean);
};

// Get corequisites for a subject
const getCorequisites = (subject) => {
  return subjectConnections.value
    .filter(conn => 
      (conn.to === subject.code || conn.from === subject.code) && 
      conn.type === 'corequisite'
    )
    .map(conn => {
      const code = conn.from === subject.code ? conn.to : conn.from;
      return props.subjects.find(s => s.code === code);
    })
    .filter(Boolean);
};

// Watch for changes that require position updates
watch(() => props.viewMode, () => {
  isLoading.value = true;
  setTimeout(updateNodePositions, 100);
});

watch(() => props.zoomLevel, () => {
  setTimeout(updateNodePositions, 100);
});

// Initialize
onMounted(() => {
  setTimeout(updateNodePositions, 500);
  window.addEventListener('resize', updateNodePositions);
});
</script>

<template>
  <div class="relative">
    <!-- Loading indicator -->
    <div v-if="isLoading" class="absolute inset-0 flex items-center justify-center bg-background/80 z-50">
      <div class="flex flex-col items-center">
        <svg class="animate-spin h-8 w-8 text-primary mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-sm text-muted-foreground">Loading curriculum map...</p>
      </div>
    </div>
    
    <!-- Map container with zoom -->
    <div 
      ref="container" 
      class="relative overflow-auto border rounded-lg bg-muted/10 min-h-[500px] md:min-h-[700px]"
      :style="{
        transform: `scale(${zoomLevel})`,
        transformOrigin: 'top left',
        height: `${100 / zoomLevel}%`,
        width: `${100 / zoomLevel}%`,
      }"
    >
      <!-- Connection lines -->
      <ConnectionLines 
        :connections="subjectConnections"
        :nodePositions="nodePositions"
        :highlightedConnections="highlightedConnections"
        :zoomLevel="zoomLevel"
        :viewMode="viewMode"
      />
      
      <!-- Flow View -->
      <div v-if="viewMode === 'flow'" class="p-6 flex flex-col items-center">
        <div 
          v-for="(semesters, year) in subjectsByYearAndSemester" 
          :key="year"
          class="mb-16 w-full"
        >
          <h3 class="text-lg font-semibold mb-6 text-center">Year {{ year }}</h3>
          
          <div 
            v-for="(subjects, semester) in semesters" 
            :key="`${year}-${semester}`"
            class="mb-12"
          >
            <h4 class="text-md font-medium mb-4 text-center">Semester {{ semester }}</h4>
            
            <div class="flex flex-wrap justify-center gap-6">
              <SubjectNode
                v-for="subject in subjects"
                :key="subject.id"
                :subject="subject"
                :showLabels="showLabels"
                :viewMode="viewMode"
                :isHighlighted="hoveredSubject?.code === subject.code || selectedSubject?.code === subject.code"
                :isPrerequisite="isPrerequisite(subject)"
                :isPostrequisite="isPostrequisite(subject)"
                :isCorequisite="isCorequisite(subject)"
                @click="handleSubjectClick"
                @mouseenter="handleSubjectHover"
                @mouseleave="handleSubjectHoverEnd"
              />
            </div>
          </div>
        </div>
      </div>
      
      <!-- Grid View -->
      <div v-else-if="viewMode === 'grid'" class="p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
          <SubjectNode
            v-for="subject in props.subjects"
            :key="subject.id"
            :subject="subject"
            :showLabels="showLabels"
            :viewMode="viewMode"
            :isHighlighted="hoveredSubject?.code === subject.code || selectedSubject?.code === subject.code"
            :isPrerequisite="isPrerequisite(subject)"
            :isPostrequisite="isPostrequisite(subject)"
            :isCorequisite="isCorequisite(subject)"
            @click="handleSubjectClick"
            @mouseenter="handleSubjectHover"
            @mouseleave="handleSubjectHoverEnd"
          />
        </div>
      </div>
      
      <!-- Year View -->
      <div v-else class="p-6">
        <div 
          v-for="(semesters, year) in subjectsByYearAndSemester" 
          :key="year"
          class="mb-8"
        >
          <h3 class="text-lg font-semibold mb-4">Year {{ year }}</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div 
              v-for="(subjects, semester) in semesters" 
              :key="`${year}-${semester}`"
              class="border rounded-md p-4 bg-card"
            >
              <h4 class="text-md font-medium mb-3">Semester {{ semester }}</h4>
              
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <SubjectNode
                  v-for="subject in subjects"
                  :key="subject.id"
                  :subject="subject"
                  :showLabels="showLabels"
                  :viewMode="viewMode"
                  :isHighlighted="hoveredSubject?.code === subject.code || selectedSubject?.code === subject.code"
                  :isPrerequisite="isPrerequisite(subject)"
                  :isPostrequisite="isPostrequisite(subject)"
                  :isCorequisite="isCorequisite(subject)"
                  @click="handleSubjectClick"
                  @mouseenter="handleSubjectHover"
                  @mouseleave="handleSubjectHoverEnd"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Subject Details Dialog -->
    <Dialog :open="dialogOpen" @update:open="closeDialog">
      <DialogContent v-if="selectedSubject" class="sm:max-w-[550px]">
        <DialogHeader>
          <div class="flex items-center justify-between">
            <div>
              <DialogTitle class="flex items-center gap-2">
                {{ selectedSubject.code }}
                <Badge :variant="getStatusBadgeVariant(selectedSubject.status)">
                  {{ selectedSubject.status }}
                </Badge>
              </DialogTitle>
              <DialogDescription>{{ selectedSubject.title }}</DialogDescription>
            </div>
          </div>
        </DialogHeader>
        
        <div class="py-4">
          <!-- Status and progress -->
          <div class="mb-4">
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm font-medium">Completion Status</span>
            </div>
            <Progress :value="getProgressValue(selectedSubject.status)" class="h-2" />
          </div>
          
          <!-- Details grid -->
          <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="space-y-1">
              <p class="text-sm font-medium">Units</p>
              <p>{{ selectedSubject.units }}</p>
            </div>
            
            <div class="space-y-1">
              <p class="text-sm font-medium">Grade</p>
              <p :class="{'text-green-600 dark:text-green-400': selectedSubject.grade && selectedSubject.grade >= 75}">
                {{ selectedSubject.grade ?? "Not graded" }}
              </p>
            </div>
            
            <div class="space-y-1">
              <p class="text-sm font-medium">Year Level</p>
              <p>{{ selectedSubject.academic_year }}</p>
            </div>
            
            <div class="space-y-1">
              <p class="text-sm font-medium">Semester</p>
              <p>{{ selectedSubject.semester }}</p>
            </div>
          </div>
          
          <!-- Prerequisites -->
          <div class="mb-4">
            <h4 class="text-sm font-medium mb-2">Prerequisites</h4>
            <div v-if="getPrerequisites(selectedSubject).length > 0" class="flex flex-wrap gap-2">
              <Badge 
                v-for="prereq in getPrerequisites(selectedSubject)" 
                :key="prereq.code"
                :variant="getStatusBadgeVariant(prereq.status)"
                class="flex items-center gap-1"
              >
                {{ prereq.code }}
                <span class="text-xs opacity-70">{{ prereq.status }}</span>
              </Badge>
            </div>
            <p v-else class="text-sm text-muted-foreground">No prerequisites for this subject.</p>
          </div>
          
          <!-- Corequisites -->
          <div>
            <h4 class="text-sm font-medium mb-2">Corequisites</h4>
            <div v-if="getCorequisites(selectedSubject).length > 0" class="flex flex-wrap gap-2">
              <Badge 
                v-for="coreq in getCorequisites(selectedSubject)" 
                :key="coreq.code"
                variant="outline"
                class="flex items-center gap-1"
              >
                {{ coreq.code }}
                <span class="text-xs opacity-70">{{ coreq.status }}</span>
              </Badge>
            </div>
            <p v-else class="text-sm text-muted-foreground">No corequisites for this subject.</p>
          </div>
        </div>
        
        <DialogFooter>
          <Button @click="closeDialog">Close</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
