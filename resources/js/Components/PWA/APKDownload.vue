<script setup lang="ts">
import { ref, onMounted } from 'vue';
import Button from "@/Components/shadcn/ui/button/Button.vue";
import { Icon } from "@iconify/vue";
import axios from 'axios';

interface APKFile {
  filename: string;
  size: number;
  created_at: string;
  download_url: string;
}

interface APKStatus {
  success: boolean;
  apk_files: APKFile[];
  latest_apk: APKFile | null;
}

const isGenerating = ref(false);
const isLoading = ref(false);
const apkStatus = ref<APKStatus | null>(null);
const error = ref<string | null>(null);

// Format file size
const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// Get APK status
const getAPKStatus = async () => {
  try {
    isLoading.value = true;
    error.value = null;
    
    const response = await axios.get('/apk/status');
    apkStatus.value = response.data;
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to get APK status';
    console.error('Error getting APK status:', err);
  } finally {
    isLoading.value = false;
  }
};

// Generate APK
const generateAPK = async () => {
  try {
    isGenerating.value = true;
    error.value = null;
    
    const response = await axios.post('/apk/generate');
    
    if (response.data.success) {
      // Refresh status after generation
      await getAPKStatus();
    } else {
      error.value = response.data.message || 'APK generation failed';
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to generate APK';
    console.error('Error generating APK:', err);
  } finally {
    isGenerating.value = false;
  }
};

// Download APK
const downloadAPK = (apkFile: APKFile) => {
  window.open(apkFile.download_url, '_blank');
};

onMounted(() => {
  getAPKStatus();
});
</script>

<template>
  <div class="space-y-4">
    <!-- APK Download Section -->
    <div class="bg-background/50 backdrop-blur-sm border rounded-lg p-6">
      <div class="flex items-center gap-3 mb-4">
        <div class="p-2 rounded-md bg-green-500/10 text-green-600">
          <Icon icon="lucide:smartphone" class="h-5 w-5" />
        </div>
        <div>
          <h3 class="font-semibold text-lg">Download Android App</h3>
          <p class="text-sm text-muted-foreground">Get the native Android app for the best experience</p>
        </div>
      </div>

      <!-- Error Message -->
      <div v-if="error" class="mb-4 p-3 bg-destructive/10 border border-destructive/20 rounded-md">
        <div class="flex items-center gap-2 text-destructive">
          <Icon icon="lucide:alert-circle" class="h-4 w-4" />
          <span class="text-sm">{{ error }}</span>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex items-center justify-center py-8">
        <div class="flex items-center gap-2 text-muted-foreground">
          <Icon icon="lucide:loader-2" class="h-4 w-4 animate-spin" />
          <span>Loading APK status...</span>
        </div>
      </div>

      <!-- APK Available -->
      <div v-else-if="apkStatus?.latest_apk" class="space-y-4">
        <div class="flex items-center justify-between p-4 bg-muted/30 rounded-md">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-md bg-primary/10 text-primary">
              <Icon icon="lucide:package" class="h-5 w-5" />
            </div>
            <div>
              <p class="font-medium">DCCPHub.apk</p>
              <p class="text-sm text-muted-foreground">
                {{ formatFileSize(apkStatus.latest_apk.size) }} • 
                {{ new Date(apkStatus.latest_apk.created_at).toLocaleDateString() }}
              </p>
            </div>
          </div>
          <Button 
            @click="downloadAPK(apkStatus.latest_apk!)"
            size="sm"
            class="gap-2"
          >
            <Icon icon="lucide:download" class="h-4 w-4" />
            Download
          </Button>
        </div>

        <div class="flex gap-2">
          <Button 
            @click="generateAPK"
            :disabled="isGenerating"
            variant="outline"
            size="sm"
            class="gap-2"
          >
            <Icon 
              :icon="isGenerating ? 'lucide:loader-2' : 'lucide:refresh-cw'" 
              :class="isGenerating ? 'animate-spin' : ''"
              class="h-4 w-4" 
            />
            {{ isGenerating ? 'Generating...' : 'Generate New' }}
          </Button>
        </div>
      </div>

      <!-- No APK Available -->
      <div v-else class="text-center py-8">
        <div class="mb-4">
          <Icon icon="lucide:package-x" class="h-12 w-12 mx-auto text-muted-foreground" />
        </div>
        <p class="text-muted-foreground mb-4">No APK available yet</p>
        <Button 
          @click="generateAPK"
          :disabled="isGenerating"
          class="gap-2"
        >
          <Icon 
            :icon="isGenerating ? 'lucide:loader-2' : 'lucide:package-plus'" 
            :class="isGenerating ? 'animate-spin' : ''"
            class="h-4 w-4" 
          />
          {{ isGenerating ? 'Generating APK...' : 'Generate APK' }}
        </Button>
      </div>

      <!-- Installation Instructions -->
      <div class="mt-6 p-4 bg-blue-500/10 border border-blue-500/20 rounded-md">
        <div class="flex items-start gap-3">
          <Icon icon="lucide:info" class="h-5 w-5 text-blue-600 mt-0.5" />
          <div class="text-sm">
            <p class="font-medium text-blue-600 mb-2">Installation Instructions:</p>
            <ol class="space-y-1 text-blue-600/80 list-decimal list-inside">
              <li>Download the APK file</li>
              <li>Enable "Install from unknown sources" in your Android settings</li>
              <li>Open the downloaded APK file to install</li>
              <li>Launch DCCPHub from your app drawer</li>
            </ol>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
