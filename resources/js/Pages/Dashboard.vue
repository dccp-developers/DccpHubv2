<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  StudentHeader,
  StatsCards,
  CurrentClassCard,
  ScheduleCard,
  AssignmentsCard,
  ExamsCard,
  GradesCard,
  AnnouncementsCard,
  ResourcesCard
} from './Dashboard/Components';

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
    <div class="md:container mx-auto px-4 py-6 space-y-6">
      <!-- Student Header -->
      <StudentHeader
        :student="student"
        :user="user"
        :currentDate="currentDate"
      />

      <!-- Stats Cards -->
      <StatsCards
        :stats="stats"
        :course-info="courseInfo"
        :semester="semester"
        :school-year="schoolYear"
      />

      <!-- Today's Schedule and Current Class -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Current/Next Class Card -->
        <CurrentClassCard
          :currentClass="currentClass"
        />

        <!-- Today's Schedule -->
        <ScheduleCard
          :todaysClasses="todaysClasses"
          class="lg:col-span-2"
        />
      </div>

      <!-- Main Dashboard Content - 2 Column Layout -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Assignments and Exams -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Assignments Card -->
          <AssignmentsCard :assignments="assignments" />

          <!-- Exams Card -->
          <ExamsCard :exams="exams" />
        </div>

        <!-- Right Column - Grades, Announcements, Resources -->
        <div class="space-y-6">
          <!-- Recent Grades Card -->
          <GradesCard :recentGrades="recentGrades" />

          <!-- Announcements Card -->
          <AnnouncementsCard :announcements="announcements" />

          <!-- Resources Card -->
          <ResourcesCard :resources="resources" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
