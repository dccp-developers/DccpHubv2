<template>
  <form @submit.prevent="submitSetup" class="space-y-6">
    <!-- Basic Settings -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-foreground mb-2">
          Start Date <span class="text-destructive">*</span>
        </label>
        <input
          type="date"
          v-model="form.start_date"
          required
          class="w-full px-3 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
        />
      </div>
      
      <div>
        <label class="block text-sm font-medium text-foreground mb-2">
          End Date (Optional)
        </label>
        <input
          type="date"
          v-model="form.end_date"
          :min="form.start_date"
          class="w-full px-3 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
        />
      </div>
    </div>

    <!-- Attendance Method -->
    <div>
      <label class="block text-sm font-medium text-foreground mb-3">
        Attendance Method <span class="text-destructive">*</span>
      </label>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="method in availableMethods"
          :key="method.value"
          @click="form.attendance_method = method.value"
          :class="[
            'p-4 border rounded-lg cursor-pointer transition-all',
            form.attendance_method === method.value
              ? 'border-primary bg-primary/5'
              : 'border-border hover:border-primary/50'
          ]"
        >
          <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
              <component :is="getMethodIcon(method.icon)" class="w-6 h-6 text-primary" />
            </div>
            <div class="flex-1">
              <h4 class="font-medium text-foreground">{{ method.label }}</h4>
              <p class="text-sm text-muted-foreground mt-1">{{ method.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Attendance Policy -->
    <div>
      <label class="block text-sm font-medium text-foreground mb-3">
        Attendance Policy <span class="text-destructive">*</span>
      </label>
      <div class="space-y-3">
        <div
          v-for="policy in policies"
          :key="policy.value"
          @click="form.attendance_policy = policy.value"
          :class="[
            'p-4 border rounded-lg cursor-pointer transition-all',
            form.attendance_policy === policy.value
              ? 'border-primary bg-primary/5'
              : 'border-border hover:border-primary/50'
          ]"
        >
          <div class="flex items-start space-x-3">
            <input
              type="radio"
              :value="policy.value"
              v-model="form.attendance_policy"
              class="mt-1"
            />
            <div class="flex-1">
              <h4 class="font-medium text-foreground">{{ policy.label }}</h4>
              <p class="text-sm text-muted-foreground mt-1">{{ policy.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Timing Settings -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-foreground mb-2">
          Grace Period (minutes)
        </label>
        <input
          type="number"
          v-model.number="form.grace_period_minutes"
          min="0"
          max="60"
          class="w-full px-3 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
        />
        <p class="text-xs text-muted-foreground mt-1">
          How many minutes late is still considered "present"
        </p>
      </div>
      
      <div>
        <label class="block text-sm font-medium text-foreground mb-2">
          Auto Mark Absent (minutes)
        </label>
        <input
          type="number"
          v-model.number="form.auto_mark_absent_minutes"
          min="1"
          class="w-full px-3 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
        />
        <p class="text-xs text-muted-foreground mt-1">
          Automatically mark students absent after X minutes (optional)
        </p>
      </div>
    </div>

    <!-- Check-in Time Settings (for methods that require student action) -->
    <div v-if="requiresStudentAction" class="space-y-4">
      <h4 class="font-medium text-foreground">Check-in Time Window</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-foreground mb-2">
            Check-in Start Time
          </label>
          <input
            type="time"
            v-model="form.checkin_start_time"
            class="w-full px-3 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
          />
          <p class="text-xs text-muted-foreground mt-1">
            When students can start checking in
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-foreground mb-2">
            Check-in End Time
          </label>
          <input
            type="time"
            v-model="form.checkin_end_time"
            class="w-full px-3 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
          />
          <p class="text-xs text-muted-foreground mt-1">
            When check-in window closes
          </p>
        </div>
      </div>
    </div>

    <!-- Self Check-in Settings -->
    <div v-if="form.attendance_method === 'self_checkin' || form.attendance_method === 'hybrid'" class="space-y-4">
      <h4 class="font-medium text-foreground">Self Check-in Settings</h4>
      <div class="space-y-3">
        <label class="flex items-center space-x-3">
          <input
            type="checkbox"
            v-model="form.require_confirmation"
            class="rounded border-border"
          />
          <span class="text-sm text-foreground">Require teacher confirmation for self check-ins</span>
        </label>

        <label class="flex items-center space-x-3">
          <input
            type="checkbox"
            v-model="form.show_class_list"
            class="rounded border-border"
          />
          <span class="text-sm text-foreground">Show students who have checked in</span>
        </label>
      </div>
    </div>

    <!-- Notification Settings -->
    <div class="space-y-4">
      <h4 class="font-medium text-foreground">Notification Settings</h4>
      <div class="space-y-3">
        <label class="flex items-center space-x-3">
          <input
            type="checkbox"
            v-model="form.notify_absent_students"
            class="rounded border-border"
          />
          <span class="text-sm text-foreground">Notify absent students</span>
        </label>
        
        <label class="flex items-center space-x-3">
          <input
            type="checkbox"
            v-model="form.notify_late_students"
            class="rounded border-border"
          />
          <span class="text-sm text-foreground">Notify late students</span>
        </label>
        
        <label class="flex items-center space-x-3">
          <input
            type="checkbox"
            v-model="form.send_daily_summary"
            class="rounded border-border"
          />
          <span class="text-sm text-foreground">Send daily attendance summary</span>
        </label>
      </div>
    </div>

    <!-- Notes -->
    <div>
      <label class="block text-sm font-medium text-foreground mb-2">
        Notes (Optional)
      </label>
      <textarea
        v-model="form.notes"
        rows="3"
        maxlength="1000"
        class="w-full px-3 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
        placeholder="Any additional notes about attendance tracking for this class..."
      ></textarea>
    </div>

    <!-- Submit Button -->
    <div class="flex justify-end space-x-3">
      <Button type="button" variant="outline" @click="$emit('cancel')">
        Cancel
      </Button>
      <Button type="submit" :disabled="loading">
        <ClipboardDocumentListIcon class="w-4 h-4 mr-2" />
        {{ loading ? 'Setting up...' : 'Setup Attendance Tracking' }}
      </Button>
    </div>
  </form>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Button } from '@/Components/ui/button.js'
import {
  ClipboardDocumentListIcon,
  QrCodeIcon,
  MapPinIcon,
  FingerPrintIcon,
  IdentificationIcon,
  FaceSmileIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  classData: Object,
  methods: Array,
  policies: Array,
  initialSettings: Object
})

const emit = defineEmits(['setup-complete', 'cancel'])

const loading = ref(false)

const form = ref({
  is_enabled: props.initialSettings?.is_enabled ?? true,
  start_date: props.initialSettings?.start_date ?? new Date().toISOString().split('T')[0],
  end_date: props.initialSettings?.end_date ?? '',
  attendance_method: props.initialSettings?.attendance_method ?? 'manual',
  attendance_policy: props.initialSettings?.attendance_policy ?? 'present_by_default',
  grace_period_minutes: props.initialSettings?.grace_period_minutes ?? 15,
  auto_mark_absent_minutes: props.initialSettings?.auto_mark_absent_minutes ?? null,
  allow_late_checkin: props.initialSettings?.allow_late_checkin ?? true,
  checkin_start_time: props.initialSettings?.checkin_start_time ?? '',
  checkin_end_time: props.initialSettings?.checkin_end_time ?? '',
  require_confirmation: props.initialSettings?.require_confirmation ?? false,
  show_class_list: props.initialSettings?.show_class_list ?? true,
  notify_absent_students: props.initialSettings?.notify_absent_students ?? false,
  notify_late_students: props.initialSettings?.notify_late_students ?? false,
  send_daily_summary: props.initialSettings?.send_daily_summary ?? false,
  notes: props.initialSettings?.notes ?? ''
})

const availableMethods = computed(() => {
  return props.methods || []
})

const requiresStudentAction = computed(() => {
  const method = availableMethods.value.find(m => m.value === form.value.attendance_method)
  return method?.requires_student_action ?? false
})

const getMethodIcon = (iconName) => {
  const icons = {
    'clipboard-document-list': ClipboardDocumentListIcon,
    'qr-code': QrCodeIcon,
    'key': IdentificationIcon,
    'hand-raised': FingerPrintIcon,
    'user-group': FaceSmileIcon
  }
  return icons[iconName] || ClipboardDocumentListIcon
}



const submitSetup = async () => {
  loading.value = true
  
  try {
    emit('setup-complete', form.value)
  } finally {
    loading.value = false
  }
}
</script>
