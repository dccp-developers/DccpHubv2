<template>
  <Button variant="ghost" size="icon" @click="toggleTheme" class="h-9 w-9">
    <SunIcon v-if="isDark" class="h-4 w-4" />
    <MoonIcon v-else class="h-4 w-4" />
    <span class="sr-only">Toggle theme</span>
  </Button>
</template>

<script setup>
import { Button } from './button.js'
import { SunIcon, MoonIcon } from '@heroicons/vue/24/outline'
import { useTheme } from '@/composables/useTheme.js'

const { isDark, toggleTheme } = useTheme()
</script>
