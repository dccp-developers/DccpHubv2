<template>
  <FacultyLayout>
    <div class="max-w-4xl mx-auto px-4 py-8">
      <!-- Simple Header -->
      <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
          DCCPHub Mobile App Changelog
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
          Release history and updates
        </p>
      </div>

      <!-- Simple Content Container -->
      <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg">
        <div class="p-6 md:p-8">
          <div v-html="changelogHtml" class="changelog-content"></div>
        </div>
      </div>
    </div>
  </FacultyLayout>
</template>

<script setup>
import FacultyLayout from '@/Layouts/FacultyLayout.vue'

// Props
const props = defineProps({
  changelogHtml: {
    type: String,
    required: true,
  },
})
</script>

<style scoped>
.changelog-content {
  @apply text-gray-900 dark:text-gray-100;
  line-height: 1.6;
}

/* Headings */
.changelog-content h1 {
  @apply text-2xl font-bold mb-6 text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2;
}

.changelog-content h2 {
  @apply text-xl font-semibold mb-4 mt-8 text-gray-800 dark:text-gray-200;
}

.changelog-content h3 {
  @apply text-lg font-medium mb-3 mt-6 text-gray-700 dark:text-gray-300;
}

.changelog-content h4 {
  @apply text-base font-medium mb-2 mt-4 text-gray-700 dark:text-gray-300;
}

/* Paragraphs */
.changelog-content p {
  @apply mb-4 text-gray-700 dark:text-gray-300;
}

/* Lists */
.changelog-content ul {
  @apply mb-4 pl-6;
}

.changelog-content ol {
  @apply mb-4 pl-6;
}

.changelog-content li {
  @apply mb-2 text-gray-700 dark:text-gray-300;
}

/* Strong text */
.changelog-content strong {
  @apply font-semibold text-gray-900 dark:text-white;
}

/* Code */
.changelog-content code {
  @apply bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded text-sm font-mono text-gray-800 dark:text-gray-200;
}

.changelog-content pre {
  @apply bg-gray-100 dark:bg-gray-800 p-4 rounded border border-gray-200 dark:border-gray-700 overflow-x-auto mb-4;
}

.changelog-content pre code {
  @apply bg-transparent p-0;
}

/* Blockquotes */
.changelog-content blockquote {
  @apply border-l-4 border-blue-500 pl-4 italic text-gray-600 dark:text-gray-400 mb-4 bg-gray-50 dark:bg-gray-800 py-2;
}

/* Horizontal rules */
.changelog-content hr {
  @apply my-8 border-gray-300 dark:border-gray-600;
}

/* Tables */
.changelog-content table {
  @apply w-full border-collapse border border-gray-300 dark:border-gray-600 mb-4;
}

.changelog-content th,
.changelog-content td {
  @apply border border-gray-300 dark:border-gray-600 px-3 py-2 text-left;
}

.changelog-content th {
  @apply bg-gray-100 dark:bg-gray-800 font-semibold;
}

/* Links */
.changelog-content a {
  @apply text-blue-600 dark:text-blue-400 hover:underline;
}

/* Emojis and special characters */
.changelog-content :deep(.emoji) {
  @apply inline-block;
}
</style>
