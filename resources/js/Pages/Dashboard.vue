<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  StudentHeader,
  StatsCards,
  CurrentClassCard,
  WeeklyScheduleTimeline,
  QuickActionsCard,
  GradesCard,
  AttendanceCard
} from './Dashboard/Components';
import EnrollmentNotice from '@/Components/Dashboard/EnrollmentNotice.vue';

// Define props
const props = defineProps({
  student: {
    type: Object,
    required: true,
  },
  stats: {
    type: Array,
    required: true,
  },
  todaysClasses: {
    type: Array,
    required: true,
  },
  currentClass: {
    type: Object,
    default: null, // Can be null
  },
  recentGrades: {
    type: Array,
    required: true
  },
  weeklySchedule: {
    type: Array,
    required: true
  },
  assignments: { // Keep as placeholders
    type: String,
    required: true,
  },
  exams: { // Keep as placeholders
    type: String,
    required: true,
  },
  announcements: { // Keep as placeholders
    type: String,
    required: true,
  },
  resources: {
    type: String, // Keep as placeholders
    required: true
  },
  user: {
    type: Object,
    required: true
  },
  generalSettings: {
    type: Object,
    required: true
  },
  studentEnrollment: {
    type: Object,
    required: true
  },
  attendanceData: {
    type: Object,
    default: () => ({
      stats: {
        total: 0,
        present: 0,
        absent: 0,
        late: 0,
        excused: 0,
        partial: 0,
        present_count: 0,
        attendance_rate: 0,
      },
      alerts: [],
      recentClasses: []
    })
  }
});

// Format current date
const currentDate = new Date().toLocaleDateString('en-US', {
  weekday: 'long',
  year: 'numeric',
  month: 'long',
  day: 'numeric'
});
</script>

<template>
  <AppLayout>
    <div v-if="user.role === 'guest'" class="flex flex-col items-center justify-center min-h-[60vh]">
      <div class="bg-white dark:bg-gray-900 border border-primary/30 rounded-lg shadow-lg p-8 max-w-lg w-full text-center">
        <h2 class="text-2xl font-bold mb-4 text-primary">Enrollment Approved!</h2>
        <p class="mb-6 text-muted-foreground">Your enrollment request has been approved. Please continue with the rest of the enrollment process below.</p>
        <!-- Placeholder for guest dashboard modal and next steps -->
        <p class="mb-4">(Guest Dashboard coming soon...)</p>
        <!-- You can add a button to continue to the next step or show available subjects here -->
      </div>
    </div>
    <div v-else class="min-h-screen bg-background">
      <div class="container mx-auto px-3 py-3 space-y-3 max-w-6xl">
        <!-- Enrollment Notice -->
        <EnrollmentNotice
          v-if="user.role === 'student'"
          :general-settings="generalSettings"
          :user="user"
          :student-enrollment="studentEnrollment"
        />

        <!-- Simplified Student Header -->
        <StudentHeader
          :student="student"
          :user="user"
          :currentDate="currentDate"
        />

        <!-- Essential Stats (Mobile-First) -->
        <StatsCards
          :stats="stats"
        />

        <!-- Main Content - Mobile-First Layout -->
        <div class="space-y-3">
          <!-- Current Class - Always First Priority (only if there's a current class) -->
          <CurrentClassCard
            v-if="currentClass"
            :currentClass="currentClass"
          />

          <!-- Weekly Schedule Timeline - Replaces break time section -->
          <WeeklyScheduleTimeline
            :weeklySchedule="weeklySchedule"
            :currentDay="new Date().toLocaleDateString('en-US', { weekday: 'long' })"
          />

          <!-- Desktop: Three Column Layout for Secondary Content -->
          <div class="hidden md:grid md:grid-cols-3 gap-4">
            <!-- Attendance Overview -->
            <AttendanceCard
              :attendanceStats="attendanceData.stats"
              :attendanceAlerts="attendanceData.alerts"
              :recentClasses="attendanceData.recentClasses"
            />

            <!-- Recent Grades -->
            <GradesCard :recentGrades="recentGrades" />

            <!-- Quick Actions -->
            <QuickActionsCard />
          </div>

          <!-- Mobile: Single Column for Secondary Content -->
          <div class="md:hidden space-y-3">
            <!-- Attendance Overview -->
            <AttendanceCard
              :attendanceStats="attendanceData.stats"
              :attendanceAlerts="attendanceData.alerts"
              :recentClasses="attendanceData.recentClasses"
            />

            <!-- Recent Grades -->
            <GradesCard :recentGrades="recentGrades" />

            <!-- Quick Actions -->
            <QuickActionsCard />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
