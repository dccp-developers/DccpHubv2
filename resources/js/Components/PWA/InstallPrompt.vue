<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import Button from "@/Components/shadcn/ui/button/Button.vue";
import { Icon } from "@iconify/vue";

interface BeforeInstallPromptEvent extends Event {
  prompt(): Promise<void>;
  userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
}

const deferredPrompt = ref<BeforeInstallPromptEvent | null>(null);
const isInstallable = ref(false);
const isInstalled = ref(false);
const isIOS = ref(false);
const showIOSInstructions = ref(false);

// Check if app is already installed
const checkIfInstalled = () => {
  // Check if running in standalone mode (installed PWA)
  if (window.matchMedia('(display-mode: standalone)').matches) {
    isInstalled.value = true;
    return true;
  }
  
  // Check if running in PWA mode on mobile
  if (window.navigator.standalone === true) {
    isInstalled.value = true;
    return true;
  }
  
  return false;
};

// Detect iOS devices
const detectIOS = () => {
  const userAgent = window.navigator.userAgent.toLowerCase();
  return /iphone|ipad|ipod/.test(userAgent);
};

// Install the PWA
const installPWA = async () => {
  if (!deferredPrompt.value) return;
  
  try {
    await deferredPrompt.value.prompt();
    const choiceResult = await deferredPrompt.value.userChoice;
    
    if (choiceResult.outcome === 'accepted') {
      console.log('PWA installation accepted');
      isInstallable.value = false;
      deferredPrompt.value = null;
    }
  } catch (error) {
    console.error('Error installing PWA:', error);
  }
};

// Show iOS installation instructions
const showIOSInstallInstructions = () => {
  showIOSInstructions.value = true;
};

// Hide iOS installation instructions
const hideIOSInstallInstructions = () => {
  showIOSInstructions.value = false;
};

const installButtonText = computed(() => {
  if (isIOS.value) {
    return 'Install App (iOS)';
  }
  return 'Install App';
});

const installButtonIcon = computed(() => {
  if (isIOS.value) {
    return 'lucide:smartphone';
  }
  return 'lucide:download';
});

onMounted(() => {
  // Check if already installed
  if (checkIfInstalled()) {
    return;
  }
  
  // Detect iOS
  isIOS.value = detectIOS();
  
  // Listen for the beforeinstallprompt event
  window.addEventListener('beforeinstallprompt', (e: Event) => {
    e.preventDefault();
    deferredPrompt.value = e as BeforeInstallPromptEvent;
    isInstallable.value = true;
  });
  
  // Listen for app installed event
  window.addEventListener('appinstalled', () => {
    console.log('PWA was installed');
    isInstalled.value = true;
    isInstallable.value = false;
    deferredPrompt.value = null;
  });
  
  // For iOS, show install option if not in standalone mode
  if (isIOS.value && !window.navigator.standalone) {
    isInstallable.value = true;
  }
});
</script>

<template>
  <div v-if="!isInstalled && isInstallable" class="relative">
    <!-- Install Button -->
    <Button 
      @click="isIOS ? showIOSInstallInstructions() : installPWA()"
      size="lg" 
      class="gap-2 shadow-lg shadow-primary/20"
      variant="default"
    >
      <Icon :icon="installButtonIcon" class="h-4 w-4" />
      {{ installButtonText }}
    </Button>
    
    <!-- iOS Installation Instructions Modal -->
    <div 
      v-if="showIOSInstructions" 
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
      @click="hideIOSInstallInstructions"
    >
      <div 
        class="bg-background rounded-lg p-6 max-w-sm w-full shadow-xl"
        @click.stop
      >
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">Install DCCPHub</h3>
          <button 
            @click="hideIOSInstallInstructions"
            class="p-1 rounded-md hover:bg-muted"
          >
            <Icon icon="lucide:x" class="h-5 w-5" />
          </button>
        </div>
        
        <div class="space-y-4 text-sm">
          <p class="text-muted-foreground">
            To install DCCPHub on your iOS device:
          </p>
          
          <ol class="space-y-3 list-decimal list-inside">
            <li class="flex items-start gap-2">
              <Icon icon="lucide:share" class="h-4 w-4 mt-0.5 text-primary" />
              <span>Tap the <strong>Share</strong> button in Safari</span>
            </li>
            <li class="flex items-start gap-2">
              <Icon icon="lucide:plus-square" class="h-4 w-4 mt-0.5 text-primary" />
              <span>Select <strong>"Add to Home Screen"</strong></span>
            </li>
            <li class="flex items-start gap-2">
              <Icon icon="lucide:check" class="h-4 w-4 mt-0.5 text-primary" />
              <span>Tap <strong>"Add"</strong> to confirm</span>
            </li>
          </ol>
          
          <div class="mt-4 p-3 bg-primary/10 rounded-md">
            <p class="text-xs text-primary">
              <Icon icon="lucide:info" class="h-3 w-3 inline mr-1" />
              The app will appear on your home screen like a native app!
            </p>
          </div>
        </div>
        
        <div class="mt-6 flex justify-end">
          <Button @click="hideIOSInstallInstructions" variant="outline">
            Got it!
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
