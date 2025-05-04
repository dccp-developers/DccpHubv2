<script setup>
import { Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue' // Assuming Iconify is used for icons
</script>

<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-blue-50 via-white to-green-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 px-4 py-8">
    <!-- Optional: Logo or School Name Header -->
    <div class="mb-8 text-center">
       <Link href="/" class="inline-flex items-center gap-2">
            <!-- Replace with your actual logo component or image -->
            <Icon icon="lucide:graduation-cap" class="h-8 w-8 text-primary" aria-hidden="true" />
            <span class="text-xl font-bold text-gray-800 dark:text-gray-200">
              {{ $page.props.name || 'School Portal' }} Enrollment
            </span>
        </Link>
    </div>

    <!-- Main Content Area -->
    <main class="w-full"> <!-- Removed padding, content will handle it -->
      <slot />
    </main>

    <!-- Optional: Minimal Footer -->
     <footer class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
        &copy; {{ new Date().getFullYear() }} {{ $page.props.name || 'Your School Name' }}. All rights reserved.
        <!-- Maybe add a link back to the main site or help page -->
        <Link href="/" class="ml-2 underline hover:text-primary">Back to Home</Link>
    </footer>
  </div>
</template>
