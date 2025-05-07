<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
    <div class="bg-white dark:bg-gray-900 border border-primary/30 rounded-lg shadow-lg p-8 max-w-lg w-full text-center">
      <h2 class="text-2xl font-bold mb-4 text-primary">Enrollment Approved!</h2>
      <p class="mb-6 text-muted-foreground">Your enrollment request has been approved. Please continue with the rest of the enrollment process below.</p>
      <slot />
      <button
        class="mt-6 px-6 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-primary/90 transition"
        @click="$emit('continue')"
      >
        Continue
      </button>
    </div>
  </div>
</template>

<script setup>
// No props needed for now
</script>

<style scoped>
/* Add any custom styles if needed */
</style> 