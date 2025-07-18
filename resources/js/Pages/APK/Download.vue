<template>
  <AppLayout title="Download DCCPHub APK">
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-gray-100">
        Download DCCPHub Mobile App
      </h2>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-xl dark:bg-gray-800 sm:rounded-lg">
          <div class="p-6 lg:p-8">
            <div class="flex items-center">
              <svg class="h-12 w-12 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
              </svg>
              <h1 class="ml-4 text-2xl font-medium text-gray-900 dark:text-white">
                DCCPHub Mobile App
              </h1>
            </div>

            <p class="mt-6 text-gray-500 dark:text-gray-400 leading-relaxed">
              Download the DCCPHub mobile app to access your academic information on the go. 
              The app provides all the features of the web portal in a convenient mobile format.
            </p>

            <!-- APK Status Section -->
            <div class="mt-8">
              <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-2 text-gray-600 dark:text-gray-400">Loading APK information...</span>
              </div>

              <div v-else-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex">
                  <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                  </svg>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Error loading APK information</h3>
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ error }}</p>
                  </div>
                </div>
              </div>

              <div v-else-if="apkStatus && apkStatus.latest_apk" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6">
                <div class="flex items-start">
                  <svg class="h-6 w-6 text-green-400 mt-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                  </svg>
                  <div class="ml-3 flex-1">
                    <h3 class="text-lg font-medium text-green-800 dark:text-green-200">APK Ready for Download</h3>
                    <div class="mt-2 text-sm text-green-700 dark:text-green-300">
                      <p><strong>File:</strong> {{ apkStatus.latest_apk.filename }}</p>
                      <p><strong>Size:</strong> {{ apkStatus.latest_apk.size_human }}</p>
                      <p><strong>Created:</strong> {{ formatDate(apkStatus.latest_apk.created_at) }}</p>
                    </div>
                    <div class="mt-4">
                      <button
                        @click="downloadAPK(apkStatus.latest_apk)"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                      >
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Download APK
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div v-else class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex">
                  <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                  </svg>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">No APK Available</h3>
                    <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                      The mobile app is currently being built. Please check back later or contact support.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Generate APK Section -->
            <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-8">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Generate New APK</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                If you need a fresh APK build, you can generate one using the button below. This process may take several minutes.
              </p>
              
              <button
                @click="generateAPK"
                :disabled="generating"
                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 disabled:opacity-50"
              >
                <svg v-if="generating" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                {{ generating ? 'Generating...' : 'Generate APK' }}
              </button>
            </div>

            <!-- Installation Instructions -->
            <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-8">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Installation Instructions</h3>
              <div class="space-y-4 text-sm text-gray-600 dark:text-gray-400">
                <div class="flex items-start">
                  <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center text-xs font-medium mr-3 mt-0.5">1</span>
                  <p>Download the APK file to your Android device</p>
                </div>
                <div class="flex items-start">
                  <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center text-xs font-medium mr-3 mt-0.5">2</span>
                  <p>Enable "Install from unknown sources" in your Android settings (Settings > Security > Unknown Sources)</p>
                </div>
                <div class="flex items-start">
                  <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center text-xs font-medium mr-3 mt-0.5">3</span>
                  <p>Open the downloaded APK file and follow the installation prompts</p>
                </div>
                <div class="flex items-start">
                  <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center text-xs font-medium mr-3 mt-0.5">4</span>
                  <p>Launch DCCPHub from your app drawer and enjoy!</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'

const loading = ref(true)
const generating = ref(false)
const error = ref(null)
const apkStatus = ref(null)

const getAPKStatus = async () => {
  try {
    loading.value = true
    error.value = null
    
    const response = await axios.get('/apk/status')
    apkStatus.value = response.data
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load APK status'
    console.error('Error fetching APK status:', err)
  } finally {
    loading.value = false
  }
}

const downloadAPK = (apkFile) => {
  window.open(apkFile.download_url, '_blank')
}

const generateAPK = async () => {
  try {
    generating.value = true
    error.value = null
    
    const response = await axios.post('/apk/generate')
    
    if (response.data.success) {
      // Refresh status after generation
      await getAPKStatus()
    } else {
      error.value = response.data.message || 'APK generation failed'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to generate APK'
    console.error('Error generating APK:', err)
  } finally {
    generating.value = false
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString()
}

onMounted(() => {
  getAPKStatus()
})
</script>
