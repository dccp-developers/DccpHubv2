<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { AspectRatio } from "@/Components/shadcn/ui/aspect-ratio";
import { Avatar, AvatarFallback, AvatarImage } from "@/Components/shadcn/ui/avatar";
import Button from "@/Components/shadcn/ui/button/Button.vue";
import { Icon } from "@iconify/vue";

interface Screenshot {
  title: string;
  description: string;
  image: string;
  icon: string;
}

interface HeroProps {
  heading?: string;
  subheading?: string;
  description?: string;
  button?: {
    text: string;
    url: string;
  };
  secondaryButton?: {
    text: string;
    url: string;
  };
  screenshots?: Screenshot[];
  githubUrl?: string;
}

const props = withDefaults(defineProps<HeroProps>(), {
  heading: "Your Complete Campus Experience",
  subheading: "All-in-one digital platform",
  description: "DCCPHub brings your entire academic journey into one seamless platform. Access courses, track grades, manage schedules, and connect with peers - everything you need for academic success.",
  button: () => ({
    text: "Start Your Journey",
    url: "https://dccphub.com/dashboard",
  }),
  secondaryButton: () => ({
    text: "Take a Tour",
    url: "#features",
  }),
  screenshots: () => ([
    {
      title: "Dashboard",
      description: "Get a quick overview of your academic progress, upcoming assignments, and important announcements.",
      image: "https://via.placeholder.com/800x500/1a1a2e/FFFFFF?text=Dashboard+View",
      icon: "lucide:layout-dashboard"
    },
    {
      title: "Course Management",
      description: "Access all your course materials, syllabi, and resources in one organized location.",
      image: "https://via.placeholder.com/800x500/0f3460/FFFFFF?text=Course+Management",
      icon: "lucide:book-open"
    },
    {
      title: "Grade Tracker",
      description: "Monitor your academic performance with detailed grade breakdowns and progress charts.",
      image: "https://via.placeholder.com/800x500/16213e/FFFFFF?text=Grade+Tracker",
      icon: "lucide:bar-chart-2"
    },
    {
      title: "Schedule Planner",
      description: "Manage your class schedule, study sessions, and extracurricular activities effortlessly.",
      image: "https://via.placeholder.com/800x500/1a1a2e/FFFFFF?text=Schedule+Planner",
      icon: "lucide:calendar"
    }
  ]),
  githubUrl: "https://github.com/pushpak1300/dccphub",
});

const activeScreenshot = ref(0);
const isAnimating = ref(false);

const changeScreenshot = (index: number) => {
  if (isAnimating.value || index === activeScreenshot.value) return;
  
  isAnimating.value = true;
  activeScreenshot.value = index;
  
  setTimeout(() => {
    isAnimating.value = false;
  }, 500);
};

// Auto-rotate screenshots
onMounted(() => {
  const interval = setInterval(() => {
    if (!isAnimating.value) {
      const nextIndex = (activeScreenshot.value + 1) % props.screenshots.length;
      changeScreenshot(nextIndex);
    }
  }, 5000);
  
  return () => clearInterval(interval);
});
</script>

<template>
  <section class="relative overflow-hidden py-16 md:py-24 bg-gradient-to-br from-background to-primary/5">
    <!-- Decorative elements -->
    <div class="absolute inset-0 z-0">
      <div class="absolute top-0 left-0 w-full h-full bg-grid-pattern opacity-5"></div>
      <div class="absolute top-20 left-10 w-64 h-64 rounded-full bg-primary/10 blur-3xl"></div>
      <div class="absolute bottom-20 right-10 w-80 h-80 rounded-full bg-primary/10 blur-3xl"></div>
    </div>
    
    <div class="container relative z-10 mx-auto px-4 sm:px-6">
      <!-- Header content -->
      <div class="text-center max-w-3xl mx-auto mb-16">
        <div class="inline-flex items-center px-3 py-1 rounded-full bg-primary/10 text-primary text-sm font-medium mb-4">
          <Icon icon="lucide:graduation-cap" class="mr-1.5 h-4 w-4" />
          <span>{{ props.subheading }}</span>
        </div>
        
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold tracking-tight bg-gradient-to-br from-foreground to-primary/70 bg-clip-text text-transparent mb-6">
          {{ props.heading }}
        </h1>
        
        <p class="text-lg text-muted-foreground max-w-2xl mx-auto mb-8">
          {{ props.description }}
        </p>
        
        <div class="flex flex-wrap gap-4 justify-center">
          <Button as="a" :href="props.button.url" size="lg" class="gap-2 shadow-lg shadow-primary/20">
            <Icon icon="lucide:log-in" class="h-4 w-4" />
            {{ props.button.text }}
          </Button>
          
          <Button as="a" :href="props.secondaryButton.url" variant="outline" size="lg" class="gap-2">
            <Icon icon="lucide:play" class="h-4 w-4" />
            {{ props.secondaryButton.text }}
          </Button>
        </div>
      </div>
      
      <!-- Screenshot showcase -->
      <div class="relative max-w-5xl mx-auto">
        <!-- Main screenshot display -->
        <div class="relative bg-background rounded-xl border shadow-2xl overflow-hidden">
          <!-- Browser-like header -->
          <div class="flex items-center justify-between border-b px-4 py-3 bg-muted/30">
            <div class="flex items-center gap-2">
              <Icon icon="lucide:graduation-cap" class="h-5 w-5 text-primary" />
              <span class="font-semibold">DCCPHub Portal</span>
            </div>
            <div class="flex gap-1">
              <div class="h-3 w-3 rounded-full bg-red-500"></div>
              <div class="h-3 w-3 rounded-full bg-yellow-500"></div>
              <div class="h-3 w-3 rounded-full bg-green-500"></div>
            </div>
          </div>
          
          <!-- Screenshot container -->
          <div class="relative aspect-[16/9] overflow-hidden">
            <div class="absolute inset-0 transition-opacity duration-500"
                 v-for="(screenshot, index) in props.screenshots"
                 :key="index"
                 :class="index === activeScreenshot ? 'opacity-100' : 'opacity-0'">
              <img :src="screenshot.image" :alt="screenshot.title" class="w-full h-full object-cover" />
            </div>
          </div>
        </div>
        
        <!-- Screenshot navigation -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
          <button 
            v-for="(screenshot, index) in props.screenshots" 
            :key="index"
            @click="changeScreenshot(index)"
            class="relative p-4 rounded-lg border transition-all duration-200 text-left"
            :class="index === activeScreenshot ? 'bg-primary/10 border-primary' : 'bg-background hover:bg-muted/20'"
          >
            <div class="flex items-center gap-3 mb-2">
              <div class="p-2 rounded-md bg-primary/10 text-primary">
                <Icon :icon="screenshot.icon" class="h-5 w-5" />
              </div>
              <h3 class="font-medium text-sm">{{ screenshot.title }}</h3>
            </div>
            <p class="text-xs text-muted-foreground line-clamp-2">{{ screenshot.description }}</p>
            
            <!-- Active indicator -->
            <div v-if="index === activeScreenshot" class="absolute bottom-0 left-0 w-full h-1 bg-primary"></div>
          </button>
        </div>
        
        <!-- GitHub badge -->
        <a :href="props.githubUrl" target="_blank" rel="noopener noreferrer" 
           class="absolute -top-4 -right-4 md:top-4 md:right-4 flex items-center gap-2 px-3 py-2 rounded-full bg-background border shadow-md text-sm font-medium hover:bg-muted/20 transition-colors">
          <Icon icon="mdi:github" class="h-4 w-4" />
          <span class="hidden md:inline">Star on GitHub</span>
        </a>
      </div>
      
      <!-- Trust badges -->
      <div class="mt-16 text-center">
        <p class="text-sm text-muted-foreground mb-6">Trusted by students and faculty at</p>
        <div class="flex flex-wrap justify-center gap-8 opacity-70">
          <div v-for="i in 4" :key="i" class="h-8">
            <div class="w-24 h-8 bg-foreground/20 rounded-md"></div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.bg-grid-pattern {
  background-image: 
    linear-gradient(to right, rgba(var(--primary-rgb), 0.1) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(var(--primary-rgb), 0.1) 1px, transparent 1px);
  background-size: 20px 20px;
}
</style>
