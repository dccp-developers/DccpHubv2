<template>
  <FacultyLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Release Timeline</h1>
          <p class="text-muted-foreground">
            Track the evolution of DCCPHub through our release history
          </p>
        </div>
        <div class="flex items-center space-x-2">
          <Badge variant="outline" class="text-xs">
            Current: {{ currentVersion }}
          </Badge>
        </div>
      </div>

      <!-- Timeline Container -->
      <div class="relative timeline-container">
        <!-- Timeline Line -->
        <div class="absolute left-8 top-0 bottom-0 w-0.5 timeline-line"></div>

        <!-- Timeline Items -->
        <div class="space-y-12">
          <div
            v-for="(release, index) in releases"
            :key="release.version"
            class="relative flex items-start space-x-6 timeline-item"
          >
            <!-- Timeline Node -->
            <div class="relative flex-shrink-0">
              <div
                :class="[
                  'w-4 h-4 rounded-full border-4 bg-background timeline-node cursor-pointer',
                  release.isLatest
                    ? 'border-primary shadow-lg shadow-primary/20'
                    : 'border-muted-foreground/30 hover:border-primary/50'
                ]"
              ></div>
              <!-- Pulse animation for latest release -->
              <div
                v-if="release.isLatest"
                class="absolute inset-0 w-4 h-4 rounded-full border-2 border-primary animate-ping opacity-75"
              ></div>
            </div>

            <!-- Timeline Content -->
            <div class="flex-1 min-w-0 pb-8 timeline-content">
              <!-- Release Header -->
              <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                  <Badge
                    :variant="release.isLatest ? 'default' : 'secondary'"
                    class="text-sm font-semibold flex items-center space-x-1"
                  >
                    <Rocket v-if="release.isLatest" class="w-3 h-3" />
                    <Sparkles v-else-if="getReleaseType(release.version) === 'Major Release'" class="w-3 h-3" />
                    <Wrench v-else class="w-3 h-3" />
                    <span>{{ release.version }}</span>
                  </Badge>
                  <Badge v-if="release.isLatest" variant="outline" class="text-xs animate-pulse">
                    Latest
                  </Badge>
                  <div class="text-sm text-muted-foreground flex items-center space-x-1">
                    <Calendar class="w-3 h-3" />
                    <span>{{ formatDate(release.date) }}</span>
                  </div>
                </div>
                <div class="text-xs text-muted-foreground flex items-center space-x-1">
                  <Clock class="w-3 h-3" />
                  <span>{{ getTimeAgo(release.date) }}</span>
                </div>
              </div>

              <!-- Release Card -->
              <Card
                :class="[
                  'timeline-card',
                  release.isLatest
                    ? 'border-primary/20 shadow-sm bg-primary/5'
                    : 'hover:border-primary/10 hover:shadow-lg'
                ]"
              >
                <div class="p-6">
                  <!-- Release Title -->
                  <h3 class="text-lg font-semibold mb-2 text-foreground">
                    {{ release.title }}
                  </h3>
                  <p class="text-sm text-muted-foreground mb-4">
                    {{ release.description }}
                  </p>

                  <!-- Release Content -->
                  <div class="prose prose-sm max-w-none dark:prose-invert">
                    <div v-html="release.content"></div>
                  </div>

                  <!-- Release Stats -->
                  <div class="flex items-center justify-between mt-6 pt-4 border-t border-border">
                    <div class="flex items-center space-x-4 text-xs text-muted-foreground">
                      <span v-if="release.features?.length" class="flex items-center space-x-1">
                        <Sparkles class="w-3 h-3" />
                        <span>{{ release.features.length }} features</span>
                      </span>
                      <span v-if="release.fixes?.length" class="flex items-center space-x-1">
                        <Bug class="w-3 h-3" />
                        <span>{{ release.fixes.length }} fixes</span>
                      </span>
                      <span v-if="release.technical?.length" class="flex items-center space-x-1">
                        <Wrench class="w-3 h-3" />
                        <span>{{ release.technical.length }} improvements</span>
                      </span>
                    </div>
                    <Badge
                      :variant="getReleaseTypeVariant(release.version)"
                      class="text-xs flex items-center space-x-1"
                    >
                      <Rocket v-if="getReleaseType(release.version) === 'Major Release'" class="w-3 h-3" />
                      <Wrench v-else class="w-3 h-3" />
                      <span>{{ getReleaseType(release.version) }}</span>
                    </Badge>
                  </div>
                </div>
              </Card>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="text-center py-8">
        <p class="text-sm text-muted-foreground">
          For technical support or feature requests, visit our
          <a
            href="https://github.com/yukazakiri/DccpHubv2/issues"
            target="_blank"
            class="text-primary hover:underline"
          >
            GitHub Issues
          </a>
        </p>
      </div>
    </div>
  </FacultyLayout>
</template>

<script setup>
import { Badge } from '@/Components/shadcn/ui/badge'
import { Card } from '@/Components/shadcn/ui/card'
import FacultyLayout from '@/Layouts/FacultyLayout.vue'
import {
    Calendar,
    Clock,
    Rocket,
    Sparkles,
    Wrench
} from 'lucide-vue-next'
import { computed } from 'vue'

// Props
const props = defineProps({
  releases: {
    type: Array,
    default: () => []
  },
  currentVersion: {
    type: String,
    default: 'v1.1.0'
  }
})

// Computed
const releases = computed(() => {
  return [
    {
      version: 'v1.1.0',
      date: '2025-08-10',
      isLatest: true,
      content: `
        <h2>🎓 Major Feature Release - Complete Academic Management System</h2>
        <p>This is a significant milestone release introducing comprehensive attendance management, faculty tools, and major UI/UX improvements that transform DCCPHub into a full-featured academic management platform.</p>

        <h3>✨ Major New Features</h3>

        <h4>📊 Complete Attendance Management System</h4>
        <ul>
          <li><strong>Real-time Attendance Tracking</strong>: Full attendance system with live statistics and analytics</li>
          <li><strong>Faculty Attendance Dashboard</strong>: Comprehensive dashboard for managing class attendance</li>
          <li><strong>Attendance Reports & Analytics</strong>: Detailed reporting with export capabilities (Excel, PDF, CSV)</li>
          <li><strong>Automated Alerts</strong>: Smart alerts for low attendance rates and missing students</li>
          <li><strong>Multiple Attendance Methods</strong>: Support for manual, QR code, and automated attendance tracking</li>
        </ul>

        <h4>👨‍🏫 Advanced Faculty Tools</h4>
        <ul>
          <li><strong>Missing Student Request System</strong>: Faculty can report missing students via modal forms for admin review and re-addition to class lists</li>
          <li><strong>Faculty Deadline Management</strong>: Complete deadline tracking and management system with priority levels</li>
          <li><strong>Enhanced Class Management</strong>: Improved class statistics, data handling, and student roster management</li>
          <li><strong>Grade Export System</strong>: CSV/Excel import and export capabilities for grade management</li>
          <li><strong>Faculty Notifications</strong>: Real-time notification system for important updates and requests</li>
        </ul>

        <h4>📱 Mobile-First UI/UX Overhaul</h4>
        <ul>
          <li><strong>Mobile Bottom Navigation</strong>: Enhanced mobile experience with intuitive bottom navigation bar</li>
          <li><strong>Responsive Layout System</strong>: Improved responsiveness with safe area insets for modern devices</li>
          <li><strong>Loading Screen Management</strong>: Better content loading feedback with progress indicators</li>
          <li><strong>Enhanced Navigation</strong>: Simplified navigation components using Inertia.js for smoother transitions</li>
        </ul>

        <h3>🛠️ Technical Improvements</h3>
        <ul>
          <li><strong>New Database Models</strong>: Added MissingStudentRequest and FacultyDeadline models with full CRUD operations</li>
          <li><strong>Filament Admin Integration</strong>: Complete admin panel resources for managing missing student requests</li>
          <li><strong>Enhanced Services</strong>: New ClassExportService for Excel/PDF exports and improved data processing</li>
          <li><strong>Real-time Data Integration</strong>: Enhanced dashboard with live data from backend APIs</li>
        </ul>

        <h3>🎯 Key Benefits</h3>
        <ul>
          <li><strong>For Faculty</strong>: Complete attendance management, student tracking, and administrative tools</li>
          <li><strong>For Administrators</strong>: Streamlined missing student request handling and faculty deadline management</li>
          <li><strong>For Students</strong>: Better mobile experience with improved navigation and real-time updates</li>
          <li><strong>For IT</strong>: Robust backend with proper data models and export capabilities</li>
        </ul>
      `
    },
    {
      version: 'v1.0.2',
      date: '2025-08-07',
      isLatest: false,
      content: `
        <h2>🎨 Icon & Visual Improvements</h2>
        <p>This release focuses on fixing Android app icon display issues and improving the overall visual experience across different Android versions.</p>

        <h3>✨ New Features</h3>
        <ul>
          <li><strong>Adaptive Icon Support</strong>: Complete adaptive icon implementation for Android 8.0+ devices with proper foreground and background layers</li>
          <li><strong>PWA Icon Set</strong>: Comprehensive set of Progressive Web App icons for various screen sizes and splash screens</li>
          <li><strong>Enhanced Analytics</strong>: Updated to use Umami Cloud analytics for better user tracking and insights</li>
        </ul>

        <h3>🐛 Bug Fixes</h3>
        <ul>
          <li><strong>App Drawer Icon Fix</strong>: Resolved critical Android app drawer icon display issues that were causing blank or distorted icons</li>
          <li><strong>Adaptive Icon Sizing</strong>: Fixed adaptive icon foreground sizing issues across different Android versions</li>
          <li><strong>Icon Background</strong>: Updated adaptive icon background color to white (#FFFFFF) for better visibility and consistency</li>
          <li><strong>Legacy Compatibility</strong>: Maintained backward compatibility with older Android versions (pre-8.0)</li>
        </ul>

        <h3>🎯 User Impact</h3>
        <ul>
          <li><strong>Better App Discovery</strong>: App now displays correctly in Android app drawers and home screens</li>
          <li><strong>Professional Appearance</strong>: Consistent icon appearance across all Android versions and launchers</li>
          <li><strong>Improved Branding</strong>: Enhanced visual identity with proper DCCP branding</li>
        </ul>
      `
    },
    {
      version: 'v1.0.1',
      date: '2025-08-04',
      isLatest: false,
      content: `
        <h2>🔧 Build System & Workflow Improvements</h2>
        <p>This release focuses on streamlining the build and release process for better APK distribution and GitHub integration.</p>

        <h3>✨ New Features</h3>
        <ul>
          <li><strong>Committed APK Workflow</strong>: Implemented a new build system that commits APKs directly to the repository for more reliable releases</li>
          <li><strong>Enhanced Build Script</strong>: Updated build script with interactive push confirmation and better error handling</li>
          <li><strong>Streamlined GitHub Actions</strong>: Modified GitHub workflow to use pre-built APKs instead of building on CI, reducing build times and improving reliability</li>
        </ul>

        <h3>🛠️ Technical Improvements</h3>
        <ul>
          <li><strong>Repository Structure</strong>: Added dedicated releases/ directory with proper tracking for APK files</li>
          <li><strong>Build Process</strong>: Improved build script with better APK management and git integration</li>
          <li><strong>Workflow Optimization</strong>: Enhanced GitHub Actions workflow triggers to work with both tags and manual dispatch</li>
          <li><strong>Version Control</strong>: Better integration between build process and version control system</li>
        </ul>

        <h3>🚀 What's Next</h3>
        <p>This release establishes a more reliable build and distribution pipeline, setting the foundation for faster and more consistent releases in the future.</p>
      `
    },
    {
      version: 'v1.0.0',
      date: '2025-08-04',
      isLatest: false,
      content: `
        <h2>🎉 Initial DCCPHub Mobile App Release</h2>
        <p>The first official release of the DCCPHub Mobile Application, bringing academic management to your mobile device.</p>

        <h3>✨ Core Features</h3>
        <ul>
          <li><strong>GitHub Releases Integration</strong>: Automated APK distribution via GitHub Releases</li>
          <li><strong>Google OAuth Authentication</strong>: Secure login optimized for mobile devices</li>
          <li><strong>Mobile-Optimized Interface</strong>: Custom DCCP branding with responsive design</li>
          <li><strong>Download Page</strong>: GitHub CDN primary with local fallback for APK downloads</li>
          <li><strong>Automated Build System</strong>: GitHub Actions for automated APK building and publishing</li>
        </ul>

        <h3>🔧 Technical Foundation</h3>
        <ul>
          <li>Built with Capacitor and Android Gradle Plugin</li>
          <li>Targets Android API 34 (Android 14)</li>
          <li>Minimum Android version: 7.0 (API 24)</li>
          <li>Custom DCCP branding and logo integration</li>
          <li>Release scripts for both automated and manual creation</li>
        </ul>
      `
    }
  ]
})

// Methods
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const getTimeAgo = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now - date)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

  if (diffDays === 1) return '1 day ago'
  if (diffDays < 7) return `${diffDays} days ago`
  if (diffDays < 30) return `${Math.ceil(diffDays / 7)} weeks ago`
  if (diffDays < 365) return `${Math.ceil(diffDays / 30)} months ago`
  return `${Math.ceil(diffDays / 365)} years ago`
}

const getReleaseType = (version) => {
  const [major, minor, patch] = version.replace('v', '').split('.').map(Number)

  if (major > 1 || (major === 1 && minor > 0)) return 'Major Release'
  if (patch > 0) return 'Patch Release'
  return 'Initial Release'
}

const getReleaseTypeVariant = (version) => {
  const [major, minor, patch] = version.replace('v', '').split('.').map(Number)

  if (major > 1 || (major === 1 && minor > 0)) return 'default'
  if (patch > 0) return 'secondary'
  return 'outline'
}
</script>

<style scoped>
/* Timeline Styles */
.timeline-line {
  background: linear-gradient(to bottom,
    hsl(var(--primary)) 0%,
    hsl(var(--primary) / 0.5) 50%,
    hsl(var(--border)) 100%
  );
}

/* Timeline Node Animations */
.timeline-node {
  transition: all 0.3s ease;
}

.timeline-node:hover {
  transform: scale(1.1);
}

/* Card Hover Effects */
.timeline-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.timeline-card:hover {
  transform: translateY(-2px);
}

/* Prose Styles */
.prose h2 {
  @apply text-xl font-semibold text-foreground mt-6 mb-3;
}

.prose h3 {
  @apply text-lg font-medium text-foreground mt-5 mb-3 flex items-center;
}

.prose h3::before {
  content: '';
  @apply w-1 h-4 bg-primary rounded-full mr-2 flex-shrink-0;
}

.prose h4 {
  @apply text-base font-medium text-foreground mt-4 mb-2 flex items-center;
}

.prose h4::before {
  content: '';
  @apply w-0.5 h-3 bg-primary/60 rounded-full mr-2 flex-shrink-0;
}

.prose p {
  @apply text-sm text-muted-foreground mb-3 leading-relaxed;
}

.prose ul {
  @apply text-sm text-muted-foreground space-y-2 mb-4 ml-4;
}

.prose li {
  @apply leading-relaxed relative pl-4;
}

.prose li::before {
  content: '•';
  @apply absolute left-0 text-primary font-bold;
}

.prose strong {
  @apply text-foreground font-medium;
}

/* Mobile Responsive Timeline */
@media (max-width: 640px) {
  .timeline-container {
    @apply pl-4;
  }

  .timeline-line {
    @apply left-4;
  }

  .timeline-content {
    @apply ml-4;
  }
}

/* Smooth Animations */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.timeline-item {
  animation: fadeInUp 0.6s ease-out;
}

.timeline-item:nth-child(2) { animation-delay: 0.1s; }
.timeline-item:nth-child(3) { animation-delay: 0.2s; }
.timeline-item:nth-child(4) { animation-delay: 0.3s; }
.timeline-item:nth-child(5) { animation-delay: 0.4s; }
</style>
