<template>
  <Card v-if="sessionData" class="mb-6">
    <CardContent class="p-6">
      <!-- Method Header -->
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-3">
          <div class="p-2 bg-primary/10 rounded-lg">
            <component :is="getMethodIcon()" class="w-6 h-6 text-primary" />
          </div>
          <div>
            <h3 class="font-semibold text-foreground">{{ getMethodLabel() }}</h3>
            <p class="text-sm text-muted-foreground">{{ sessionData.message }}</p>
          </div>
        </div>
        
        <div v-if="sessionData.expires_at" class="text-right">
          <div class="text-sm font-medium text-foreground">Expires</div>
          <div class="text-sm text-muted-foreground">{{ formatExpiration(sessionData.expires_at) }}</div>
        </div>
      </div>

      <!-- Method-specific Content -->
      <div class="space-y-4">
        <!-- Manual Method -->
        <div v-if="sessionData.method === 'manual'" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex items-start space-x-3">
            <ClipboardDocumentListIcon class="w-5 h-5 text-blue-600 mt-0.5" />
            <div>
              <h4 class="font-medium text-blue-900">Manual Roll Call</h4>
              <p class="text-sm text-blue-700 mt-1">{{ sessionData.instructions }}</p>
            </div>
          </div>
        </div>

        <!-- QR Code Method -->
        <div v-if="sessionData.method === 'qr_code'" class="bg-green-50 border border-green-200 rounded-lg p-4">
          <div class="flex items-start justify-between">
            <div class="flex items-start space-x-3">
              <QrCodeIcon class="w-5 h-5 text-green-600 mt-0.5" />
              <div>
                <h4 class="font-medium text-green-900">QR Code Attendance</h4>
                <p class="text-sm text-green-700 mt-1">{{ sessionData.instructions }}</p>
              </div>
            </div>
            
            <div v-if="sessionData.qr_code_url" class="text-center">
              <div class="bg-white p-3 rounded-lg border border-green-300">
                <QRCodeVue3 
                  :value="sessionData.qr_code_url"
                  :width="120"
                  :height="120"
                  :dots-options="{ color: '#16a34a' }"
                />
              </div>
              <p class="text-xs text-green-600 mt-2">Students scan this code</p>
            </div>
          </div>
        </div>

        <!-- Attendance Code Method -->
        <div v-if="sessionData.method === 'attendance_code'" class="bg-purple-50 border border-purple-200 rounded-lg p-4">
          <div class="flex items-start justify-between">
            <div class="flex items-start space-x-3">
              <KeyIcon class="w-5 h-5 text-purple-600 mt-0.5" />
              <div>
                <h4 class="font-medium text-purple-900">Attendance Code</h4>
                <p class="text-sm text-purple-700 mt-1">{{ sessionData.instructions }}</p>
              </div>
            </div>
            
            <div v-if="sessionData.attendance_code" class="text-center">
              <div class="bg-white px-6 py-4 rounded-lg border border-purple-300">
                <div class="text-3xl font-bold text-purple-900 tracking-wider">
                  {{ sessionData.attendance_code }}
                </div>
              </div>
              <p class="text-xs text-purple-600 mt-2">Share this code with students</p>
            </div>
          </div>
        </div>

        <!-- Self Check-in Method -->
        <div v-if="sessionData.method === 'self_checkin'" class="bg-orange-50 border border-orange-200 rounded-lg p-4">
          <div class="flex items-start space-x-3">
            <HandRaisedIcon class="w-5 h-5 text-orange-600 mt-0.5" />
            <div class="flex-1">
              <h4 class="font-medium text-orange-900">Student Self Check-in</h4>
              <p class="text-sm text-orange-700 mt-1">{{ sessionData.instructions }}</p>
              
              <div v-if="sessionData.checkin_window" class="mt-3 flex items-center space-x-4">
                <div class="text-sm">
                  <span class="font-medium">Window:</span>
                  {{ sessionData.checkin_window.start }} - {{ sessionData.checkin_window.end }}
                </div>
                <div :class="[
                  'px-2 py-1 rounded-full text-xs font-medium',
                  sessionData.checkin_window.is_valid 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-red-100 text-red-800'
                ]">
                  {{ sessionData.checkin_window.is_valid ? 'Open' : 'Closed' }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Hybrid Method -->
        <div v-if="sessionData.method === 'hybrid'" class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
          <div class="flex items-start space-x-3">
            <UserGroupIcon class="w-5 h-5 text-indigo-600 mt-0.5" />
            <div class="flex-1">
              <h4 class="font-medium text-indigo-900">Hybrid Attendance</h4>
              <p class="text-sm text-indigo-700 mt-1">{{ sessionData.instructions }}</p>
              
              <div v-if="sessionData.checkin_window" class="mt-3 flex items-center space-x-4">
                <div class="text-sm">
                  <span class="font-medium">Student Check-in Window:</span>
                  {{ sessionData.checkin_window.start }} - {{ sessionData.checkin_window.end }}
                </div>
                <div :class="[
                  'px-2 py-1 rounded-full text-xs font-medium',
                  sessionData.checkin_window.is_valid 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-red-100 text-red-800'
                ]">
                  {{ sessionData.checkin_window.is_valid ? 'Open' : 'Closed' }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-between pt-4 border-t border-border">
          <div class="flex items-center space-x-2">
            <Button 
              v-if="canRefresh" 
              size="sm" 
              variant="outline" 
              @click="$emit('refresh-method')"
            >
              <ArrowPathIcon class="w-4 h-4 mr-2" />
              Refresh {{ getRefreshLabel() }}
            </Button>
          </div>
          
          <div class="text-sm text-muted-foreground">
            {{ getStatusText() }}
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import { Card, CardContent } from '@/Components/ui/card.js'
import { Button } from '@/Components/ui/button.js'
import QRCodeVue3 from 'qrcode-vue3'
import {
  ClipboardDocumentListIcon,
  QrCodeIcon,
  KeyIcon,
  HandRaisedIcon,
  UserGroupIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  sessionData: Object,
  methodSettings: Object
})

const emit = defineEmits(['refresh-method'])

const getMethodIcon = () => {
  const icons = {
    manual: ClipboardDocumentListIcon,
    qr_code: QrCodeIcon,
    attendance_code: KeyIcon,
    self_checkin: HandRaisedIcon,
    hybrid: UserGroupIcon
  }
  return icons[props.sessionData?.method] || ClipboardDocumentListIcon
}

const getMethodLabel = () => {
  const labels = {
    manual: 'Manual Roll Call',
    qr_code: 'QR Code Attendance',
    attendance_code: 'Attendance Code',
    self_checkin: 'Student Self Check-in',
    hybrid: 'Hybrid Attendance'
  }
  return labels[props.sessionData?.method] || 'Attendance'
}

const canRefresh = computed(() => {
  return props.sessionData?.method === 'qr_code' || props.sessionData?.method === 'attendance_code'
})

const getRefreshLabel = () => {
  return props.sessionData?.method === 'qr_code' ? 'QR Code' : 'Code'
}

const formatExpiration = (timestamp) => {
  return new Date(timestamp).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusText = () => {
  if (props.sessionData?.teacher_action_required && props.sessionData?.student_action_required) {
    return 'Both teacher and student actions required'
  } else if (props.sessionData?.teacher_action_required) {
    return 'Teacher action required'
  } else if (props.sessionData?.student_action_required) {
    return 'Student action required'
  }
  return 'Ready for attendance'
}
</script>
