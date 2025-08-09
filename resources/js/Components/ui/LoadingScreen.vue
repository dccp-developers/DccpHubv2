<template>
  <Transition
    enter-active-class="transition-all duration-200 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition-all duration-200 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="show"
      :class="[
        contentOnly
          ? 'absolute inset-0 z-10 flex items-center justify-center bg-background/90 backdrop-blur-sm rounded-xl'
          : 'fixed inset-0 z-50 flex items-center justify-center bg-background/80 backdrop-blur-sm'
      ]"
    >
      <div class="flex flex-col items-center space-y-4">
        <!-- Loading Spinner -->
        <div class="relative">
          <div
            :class="[
              'rounded-full border-4 border-muted',
              contentOnly ? 'h-8 w-8' : 'h-12 w-12'
            ]"
          ></div>
          <div
            :class="[
              'absolute top-0 rounded-full border-4 border-primary border-t-transparent animate-spin',
              contentOnly ? 'h-8 w-8' : 'h-12 w-12'
            ]"
          ></div>
        </div>

        <!-- Loading Text -->
        <div class="text-center space-y-2">
          <h3 :class="contentOnly ? 'text-base font-semibold' : 'text-lg font-semibold'">{{ title }}</h3>
          <p :class="contentOnly ? 'text-xs text-muted-foreground' : 'text-sm text-muted-foreground'">{{ description }}</p>
        </div>

        <!-- Progress Bar (optional) -->
        <div
          v-if="showProgress"
          :class="[
            'bg-muted rounded-full h-2',
            contentOnly ? 'w-48' : 'w-64'
          ]"
        >
          <div
            class="bg-primary h-2 rounded-full transition-all duration-300 ease-out"
            :style="{ width: `${progress}%` }"
          ></div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Loading...',
  },
  description: {
    type: String,
    default: 'Please wait while we load your content.',
  },
  showProgress: {
    type: Boolean,
    default: false,
  },
  progress: {
    type: Number,
    default: 0,
  },
  contentOnly: {
    type: Boolean,
    default: false,
  },
});
</script>
