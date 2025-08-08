<script setup>
import Button from '@/Components/shadcn/ui/button/Button.vue'
import { Icon } from '@iconify/vue'
import { Link } from '@inertiajs/vue3'
import { useColorMode } from '@vueuse/core'
import { ref } from 'vue'
import Sonner from '@/Components/shadcn/ui/sonner/Sonner.vue'

defineProps({
  canLogin: {
    type: Boolean,
  },
  canRegister: {
    type: Boolean,
  },
});

const mode = useColorMode({
  attribute: "class",
  modes: {
    light: "",
    dark: "dark",
  },
  initialValue: "light",
});

// Updated nav links for the redesigned header
const navLinks = [
  { label: "Home", href: "/", external: false },
  { label: "Enroll Online", href: route("enroll"), external: false },
];

const githubUrl = "https://github.com/yukizaki/dccphub"; // Keep for footer
const twitterUrl = "https://x.com/yukizaki?ref=larasonic";

const isMenuOpen = ref(false);

function toggleMenu() {
  isMenuOpen.value = !isMenuOpen.value
}
</script>

<template>
    <Sonner position="top-center" rich-colors close-button expand />
  <div class="min-h-screen flex flex-col">

    <!-- Gradient Header -->
    <header class="sticky top-0 z-50 w-full border-b border-border/40 bg-gradient-to-r from-background via-background to-muted/20 backdrop-blur supports-[backdrop-filter]:bg-background/80">
      <div class="container flex h-20 items-center max-w-screen-2xl justify-between">

        <!-- Logo & Nav Group -->
        <div class="flex items-center">
          <a
            class="flex items-center space-x-2 mr-10"
            href="/"
            :aria-label="$page.props.name"
          >
            <Icon icon="lucide:graduation-cap" class="h-7 w-7 text-primary" aria-hidden="true" />
            <span class="hidden text-lg font-semibold sm:inline-block">
              {{ $page.props.name }}
            </span>
          </a>
          <!-- Navigation -->
          <nav class="hidden md:flex items-center space-x-6 text-sm font-medium">
            <a
              v-for="link in navLinks"
              :key="link.href"
              :href="link.href"
              class="text-foreground/70 transition-colors hover:text-foreground hover:font-semibold"
              :target="link.external ? '_blank' : undefined"
              :rel="link.external ? 'noreferrer' : undefined"
            >
              {{ link.label }}
            </a>
          </nav>
        </div>

        <!-- Right Aligned Floating Actions -->
        <div class="flex items-center space-x-2 bg-background/70 border border-border/50 p-1.5 rounded-full shadow-sm">
          <!-- Theme Toggle -->
           <Button
            variant="ghost"
            size="icon"
            aria-label="Toggle Theme"
            @click="mode = mode === 'dark' ? 'light' : 'dark'"
            class="text-muted-foreground hover:text-foreground rounded-full w-8 h-8"
          >
            <Icon
              class="h-4 w-4"
              :icon="mode === 'dark' ? 'lucide:sun' : 'lucide:moon'"
            />
          </Button>

          <!-- Auth Buttons (Desktop) -->
          <div class="hidden sm:flex items-center space-x-1">
            <template v-if="!$page.props.auth.user">
              <Button :as="Link" href="/login" prefetch="mount" variant="ghost" size="sm" class="text-muted-foreground hover:text-foreground px-3">
                Login
              </Button>
              <Button :as="Link" href="/register" prefetch="mount" size="sm" variant="default" class="font-semibold rounded-full px-4">
                Register
              </Button>
            </template>
            <Button v-else :as="Link" href="/dashboard" prefetch="mount" size="sm" variant="default" class="font-semibold rounded-full px-4">
              Dashboard
            </Button>
          </div>
        </div>

         <!-- Mobile Menu Toggle -->
          <Button
            class="md:hidden ml-2 text-muted-foreground hover:text-foreground"
            variant="ghost"
            size="icon"
            aria-label="Toggle menu"
            @click="toggleMenu"
          >
            <Icon
              :icon="isMenuOpen ? 'lucide:x' : 'lucide:menu'"
              class="h-6 w-6"
              aria-hidden="true"
            />
          </Button>
      </div>

      <!-- Mobile Menu -->
      <div v-show="isMenuOpen" class="md:hidden absolute top-full left-0 right-0 border-t border-border/40 bg-background shadow-lg">
        <nav class="flex flex-col px-4 pb-4 pt-3">
          <!-- Mobile Nav Links -->
          <div class="space-y-1 pb-3 border-b border-border/40 mb-3">
             <a
               v-for="link in navLinks"
               :key="link.href"
               :href="link.href"
               class="block rounded-md px-3 py-2 text-base font-medium text-foreground/80 hover:bg-accent hover:text-accent-foreground"
               :target="link.external ? '_blank' : undefined"
               :rel="link.external ? 'noreferrer' : undefined"
               @click="toggleMenu"
             >
               {{ link.label }}
             </a>
          </div>
          <!-- Mobile Actions Group -->
          <div class="space-y-2">
             <template v-if="!$page.props.auth.user">
              <Button
                :as="Link" href="/login" class="w-full justify-center" variant="ghost" prefetch="mount" size="sm"
                @click="toggleMenu"
              >
                Login
              </Button>
              <Button
                :as="Link" href="/register" class="w-full justify-center font-semibold" variant="default" prefetch="mount" size="sm"
                @click="toggleMenu"
              >
                Register
              </Button>
            </template>
            <Button
              v-else :as="Link" href="/dashboard" class="w-full justify-center font-semibold" variant="default" size="sm"
              prefetch="mount"
              @click="toggleMenu"
            >
              Dashboard
            </Button>
             <!-- Mobile Theme Toggle -->
             <Button
                variant="outline"
                size="sm"
                class="w-full justify-center"
                aria-label="Toggle Theme"
                @click="mode = mode === 'dark' ? 'light' : 'dark'"
             >
                <Icon
                  class="h-4 w-4 mr-2"
                  :icon="mode === 'dark' ? 'lucide:sun' : 'lucide:moon'"
                />
                Toggle Theme
             </Button>
          </div>
        </nav>
      </div>
    </header>

    <main class="flex-1">
       <slot />
    </main>

    <!-- Footer -->
    <footer class="border-t border-border/40">
      <div class="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
          <p class="text-sm flex items-center gap-2 text-center sm:text-left">
            <Icon icon="lucide:rocket" class="size-6" aria-hidden="true" />
            Crafted by<a
              class="underline" :href="twitterUrl" target="_blank"
              rel="noopener noreferrer"
            >Pushpak.
            </a>
            <span>
              Hosted On <a
                class="underline" href="https://sevalla.com/?ref=larasonic" target="_blank"
                rel="noopener noreferrer"
              >
                Sevalla
              </a>
              ❤️
            </span>
          </p>
          <div class="flex gap-4">
            <Icon
              class="text-muted-foreground" :icon="mode === 'dark' ? 'lucide:sun' : 'lucide:moon'"
              @click="mode = mode === 'dark' ? 'light' : 'dark'"
            />

            <a
              :href="githubUrl" target="_blank" rel="noopener noreferrer"
              class="text-muted-foreground hover:text-foreground" aria-label="GitHub"
            >
              <Icon icon="mdi:github" class="h-5 w-5" aria-hidden="true" />
            </a>
            <a
              :href="twitterUrl" target="_blank" rel="noopener noreferrer"
              class="text-muted-foreground hover:text-foreground" aria-label="Twitter"
            >
              <Icon icon="ri:twitter-x-line" class="h-5 w-5" aria-hidden="true" />
            </a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>
