<script setup>
import { ref, computed, onMounted } from 'vue';
import { Icon } from '@iconify/vue';
import Button from '@/Components/shadcn/ui/button/Button.vue';
import { Badge } from '@/Components/shadcn/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/Components/shadcn/ui/dialog';
import InstallPrompt from '@/Components/PWA/InstallPrompt.vue';

// Detect user's platform
const userAgent = ref('');
const isAndroid = computed(() => /Android/i.test(userAgent.value));
const isIOS = computed(() => /iPhone|iPad|iPod/i.test(userAgent.value));
const isMobile = computed(() => isAndroid.value || isIOS.value);

// PWA install prompt state
const showPWAPrompt = ref(false);
const canInstallPWA = ref(false);

onMounted(() => {
  userAgent.value = navigator.userAgent;

  // Check if PWA can be installed
  if ('serviceWorker' in navigator) {
    canInstallPWA.value = true;
  }

  // Listen for beforeinstallprompt event
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    canInstallPWA.value = true;
  });
});

// APK download function
const downloadAPK = () => {
  // Download via Laravel route for proper MIME type handling
  window.open('/storage/apk/DCCPHub_latest.apk', '_blank');
};

// Generate APK function (opens PWA Builder)
const generateAPK = () => {
  window.open('https://www.pwabuilder.com/', '_blank');
};

// Get platform-specific icon
const getPlatformIcon = () => {
  if (isAndroid.value) return 'logos:android-icon';
  if (isIOS.value) return 'logos:apple';
  return 'lucide:smartphone';
};

// Get platform-specific message
const getPlatformMessage = () => {
  if (isAndroid.value) {
    return {
      title: 'Android App Available',
      description: 'Download the native Android app or install as PWA',
      status: 'beta'
    };
  }
  if (isIOS.value) {
    return {
      title: 'iOS App Coming Soon',
      description: 'Install as PWA from Safari for now',
      status: 'coming-soon'
    };
  }
  return {
    title: 'Mobile App',
    description: 'Install as PWA for the best mobile experience',
    status: 'pwa-only'
  };
};

const platformInfo = computed(() => getPlatformMessage());
</script>

<template>
  <div class="bg-gradient-to-r from-primary/10 via-primary/5 to-primary/10 rounded-xl border border-primary/20 p-6 mb-8">
    <!-- Mobile App Status Header -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-3">
        <div class="p-2 rounded-lg bg-primary/10">
          <Icon :icon="getPlatformIcon()" class="h-6 w-6 text-primary" />
        </div>
        <div>
          <h3 class="font-semibold text-lg">{{ platformInfo.title }}</h3>
          <p class="text-sm text-muted-foreground">{{ platformInfo.description }}</p>
        </div>
      </div>

      <!-- Status Badge -->
      <Badge
        :variant="platformInfo.status === 'beta' ? 'secondary' : platformInfo.status === 'coming-soon' ? 'outline' : 'default'"
        class="text-xs"
      >
        <Icon
          :icon="platformInfo.status === 'beta' ? 'lucide:flask' : platformInfo.status === 'coming-soon' ? 'lucide:clock' : 'lucide:smartphone'"
          class="h-3 w-3 mr-1"
        />
        {{ platformInfo.status === 'beta' ? 'Beta' : platformInfo.status === 'coming-soon' ? 'Coming Soon' : 'PWA' }}
      </Badge>
    </div>

    <!-- Platform-specific content -->
    <div class="space-y-4">
      <!-- Android Options -->
      <div v-if="isAndroid" class="space-y-3">
        <!-- APK Download -->
        <div class="flex items-center justify-between p-4 bg-background rounded-lg border">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-md bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400">
              <Icon icon="lucide:download" class="h-4 w-4" />
            </div>
            <div>
              <p class="font-medium text-sm">Download APK (Beta)</p>
              <p class="text-xs text-muted-foreground">Native Android app - 11MB</p>
            </div>
          </div>
          <Button @click="downloadAPK" size="sm" class="gap-1">
            <Icon icon="lucide:download" class="h-3 w-3" />
            Download
          </Button>
        </div>

        <!-- Generate APK -->
        <div class="flex items-center justify-between p-4 bg-background rounded-lg border border-dashed">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-md bg-purple-100 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400">
              <Icon icon="lucide:wrench" class="h-4 w-4" />
            </div>
            <div>
              <p class="font-medium text-sm">Generate Fresh APK</p>
              <p class="text-xs text-muted-foreground">Create latest version using PWA Builder</p>
            </div>
          </div>
          <Button @click="generateAPK" variant="outline" size="sm" class="gap-1">
            <Icon icon="lucide:external-link" class="h-3 w-3" />
            Generate
          </Button>
        </div>

        <!-- PWA Install -->
        <div class="flex items-center justify-between p-4 bg-background rounded-lg border">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-md bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
              <Icon icon="lucide:chrome" class="h-4 w-4" />
            </div>
            <div>
              <p class="font-medium text-sm">Install from Chrome</p>
              <p class="text-xs text-muted-foreground">Progressive Web App</p>
            </div>
          </div>
          <InstallPrompt />
        </div>
      </div>

      <!-- iOS Options -->
      <div v-else-if="isIOS" class="space-y-3">
        <!-- Coming Soon Notice -->
        <div class="flex items-center gap-3 p-4 bg-background rounded-lg border">
          <div class="p-2 rounded-md bg-orange-100 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400">
            <Icon icon="lucide:clock" class="h-4 w-4" />
          </div>
          <div class="flex-1">
            <p class="font-medium text-sm">iOS App Coming Soon</p>
            <p class="text-xs text-muted-foreground">We're working on bringing DCCPHub to the App Store</p>
          </div>
        </div>

        <!-- PWA Install for iOS -->
        <div class="flex items-center justify-between p-4 bg-background rounded-lg border">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-md bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
              <Icon icon="lucide:safari" class="h-4 w-4" />
            </div>
            <div>
              <p class="font-medium text-sm">Install from Safari</p>
              <p class="text-xs text-muted-foreground">Add to Home Screen</p>
            </div>
          </div>
          <InstallPrompt />
        </div>
      </div>

      <!-- Desktop/Other Platforms -->
      <div v-else class="flex items-center justify-between p-4 bg-background rounded-lg border">
        <div class="flex items-center gap-3">
          <div class="p-2 rounded-md bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
            <Icon icon="lucide:monitor" class="h-4 w-4" />
          </div>
          <div>
            <p class="font-medium text-sm">Desktop Experience</p>
            <p class="text-xs text-muted-foreground">Full-featured web application</p>
          </div>
        </div>
        <Button as="a" href="/login" size="sm" class="gap-1">
          <Icon icon="lucide:log-in" class="h-3 w-3" />
          Get Started
        </Button>
      </div>
    </div>

    <!-- Beta Notice for Mobile -->
    <div v-if="isMobile && platformInfo.status === 'beta'" class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800 rounded-lg">
      <div class="flex items-start gap-2">
        <Icon icon="lucide:info" class="h-4 w-4 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" />
        <div class="text-sm">
          <p class="font-medium text-amber-800 dark:text-amber-200">Beta Version Available</p>
          <p class="text-amber-700 dark:text-amber-300">
            Mobile app is in beta. iOS coming soon. Desktop users can install from browser.
          </p>
        </div>
      </div>
    </div>

    <!-- APK Generation Notice -->
    <div v-if="isAndroid" class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800 rounded-lg">
      <div class="flex items-start gap-2">
        <Icon icon="lucide:smartphone" class="h-4 w-4 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" />
        <div class="text-sm">
          <p class="font-medium text-blue-800 dark:text-blue-200">APK Generation</p>
          <p class="text-blue-700 dark:text-blue-300">
            APK files are generated using PWA Builder technology. Click "Generate" to create the latest version with your current PWA.
          </p>
        </div>
      </div>
    </div>

    <!-- Installation Instructions Dialog -->
    <Dialog>
      <DialogTrigger as-child>
        <Button variant="ghost" size="sm" class="w-full mt-4 gap-2">
          <Icon icon="lucide:help-circle" class="h-4 w-4" />
          Installation Help
        </Button>
      </DialogTrigger>
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Installation Instructions</DialogTitle>
          <DialogDescription>
            Choose the best installation method for your device
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4">
          <!-- Android Instructions -->
          <div v-if="isAndroid">
            <h4 class="font-medium mb-2 flex items-center gap-2">
              <Icon icon="logos:android-icon" class="h-4 w-4" />
              Android Installation
            </h4>
            <div class="space-y-2 text-sm text-muted-foreground">
              <p><strong>Option 1: Download Beta APK</strong></p>
              <ol class="list-decimal list-inside space-y-1 ml-2">
                <li>Click "Download" to get the beta APK (~11MB)</li>
                <li>Enable "Install from unknown sources" in Settings</li>
                <li>Open the downloaded APK to install</li>
                <li>Launch DCCPHub from your app drawer</li>
              </ol>

              <p class="mt-3"><strong>Option 2: Generate Fresh APK</strong></p>
              <ol class="list-decimal list-inside space-y-1 ml-2">
                <li>Click "Generate" to open PWA Builder</li>
                <li>Enter: https://portal.dccp.edu.ph</li>
                <li>Select Android platform and configure settings</li>
                <li>Download the generated APK and install</li>
              </ol>

              <p class="mt-3"><strong>Option 3: PWA Install</strong></p>
              <ol class="list-decimal list-inside space-y-1 ml-2">
                <li>Open this site in Chrome</li>
                <li>Tap the "Install" button or menu → "Add to Home Screen"</li>
                <li>Confirm installation</li>
                <li>Launch from your home screen</li>
              </ol>
            </div>
          </div>

          <!-- iOS Instructions -->
          <div v-else-if="isIOS">
            <h4 class="font-medium mb-2 flex items-center gap-2">
              <Icon icon="logos:apple" class="h-4 w-4" />
              iOS Installation
            </h4>
            <div class="space-y-2 text-sm text-muted-foreground">
              <p><strong>PWA Installation (Safari only)</strong></p>
              <ol class="list-decimal list-inside space-y-1 ml-2">
                <li>Open this site in Safari</li>
                <li>Tap the Share button (square with arrow)</li>
                <li>Scroll down and tap "Add to Home Screen"</li>
                <li>Tap "Add" to confirm</li>
                <li>Launch from your home screen</li>
              </ol>

              <div class="mt-3 p-2 bg-blue-50 dark:bg-blue-900/10 rounded border border-blue-200 dark:border-blue-800">
                <p class="text-blue-800 dark:text-blue-200 text-xs">
                  <Icon icon="lucide:info" class="h-3 w-3 inline mr-1" />
                  Native iOS app coming to the App Store soon!
                </p>
              </div>
            </div>
          </div>

          <!-- Desktop Instructions -->
          <div v-else>
            <h4 class="font-medium mb-2 flex items-center gap-2">
              <Icon icon="lucide:monitor" class="h-4 w-4" />
              Desktop Access
            </h4>
            <p class="text-sm text-muted-foreground">
              Access DCCPHub directly through your web browser for the full desktop experience.
              No installation required!
            </p>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </div>
</template>
