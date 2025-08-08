<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-background rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
      <!-- Header -->
      <div class="flex items-center justify-between p-6 border-b border-border">
        <h3 class="text-lg font-semibold text-foreground">Attendance Settings</h3>
        <Button variant="ghost" size="sm" @click="$emit('close')">
          <XMarkIcon class="w-5 h-5" />
        </Button>
      </div>

      <!-- Content -->
      <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
        <AttendanceSetupForm
          :class-data="classData"
          :methods="methods"
          :policies="policies"
          :initial-settings="currentSettings"
          @setup-complete="handleUpdate"
          @cancel="$emit('close')"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { Button } from '@/Components/ui/button.js'
import AttendanceSetupForm from './AttendanceSetupForm.vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  classData: Object,
  currentSettings: Object,
  methods: Array,
  policies: Array
})

const emit = defineEmits(['close', 'settings-updated'])

const handleUpdate = (settings) => {
  emit('settings-updated', settings)
}
</script>
