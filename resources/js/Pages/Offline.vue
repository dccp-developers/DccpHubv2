<template>
  <div class="min-h-screen flex flex-col items-center justify-center p-4 text-center">
    <div class="max-w-md mx-auto">
      <h1 class="text-4xl font-bold mb-6">You're offline</h1>
      <div class="mb-8">
        <Icon name="heroicons:wifi-slash" class="w-24 h-24 mx-auto mb-4" />
        <p class="text-lg mb-4">
          It seems you're currently offline. Please check your internet connection and try again.
        </p>
        <p class="text-sm opacity-75">
          Some features of {{ appName }} are available offline if you've visited them before.
        </p>
      </div>
      <Button @click="tryReconnect" class="mx-auto">
        Try again
      </Button>
    </div>
  </div>
</template>

<script setup>
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/shadcn/ui/button';
import { computed } from 'vue';

const appName = computed(() => {
  return import.meta.env.VITE_APP_NAME || 'DCCPHub';
});

const tryReconnect = () => {
  window.location.reload();
};
</script>
