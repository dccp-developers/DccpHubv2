<script setup>
import InputError from "@/Components/InputError.vue";
import AuthenticationCardLogo from "@/Components/LogoRedirect.vue";
import Button from "@/Components/shadcn/ui/button/Button.vue";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/Components/shadcn/ui/card";
import Checkbox from "@/Components/shadcn/ui/checkbox/Checkbox.vue";
import Input from "@/Components/shadcn/ui/input/Input.vue";
import Label from "@/Components/shadcn/ui/label/Label.vue";
import Sonner from "@/Components/shadcn/ui/sonner/Sonner.vue";
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from "@/Components/shadcn/ui/tabs";
import SocialLoginButton from "@/Components/SocialLoginButton.vue";
import MobileSocialLoginButton from "@/Components/MobileSocialLoginButton.vue";
import { useSeoMetaTags } from "@/Composables/useSeoMetaTags.js";
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { useLocalStorage } from "@vueuse/core";
import { computed, inject, onMounted, ref } from "vue";
import { toast } from "vue-sonner";

const props = defineProps({
  canResetPassword: Boolean,
  status: String,
  availableOauthProviders: Object,
});

const page = usePage();
const route = inject("route");
const activeTab = useLocalStorage("login-active-tab", "password");
const showPassword = ref(false);

// Form state
const passwordForm = useForm({
  email: "",
  password: "",
  remember: true,
});

const loginLinkForm = useForm({
  email: "",
});

// Computed
const hasOauthProviders = computed(
  () => Object.keys(props.availableOauthProviders || {}).length > 0,
);

const isProcessing = computed(
  () => passwordForm.processing || loginLinkForm.processing,
);

// Methods
function handlePasswordLogin() {
  passwordForm
    .transform((data) => ({
      ...data,
      remember: data.remember ? "on" : "",
    }))
    .post(route("login"), {
      onFinish: () => passwordForm.reset("password"),
    });
}

function handleLoginLink() {
  loginLinkForm.post(route("login-link.store"), {
    onSuccess: () => {
      loginLinkForm.reset();
      if (page.props.flash.success) {
        toast.success(page.props.flash.success);
      }
    },
    onError: (errors) => {
      // Show flash error if present
      if (page.props.flash.error) {
        toast.error(page.props.flash.error);
      }
      // Check for 429 error (Too Many Requests)
      if (loginLinkForm.response && loginLinkForm.response.status === 429) {
        toast.error("Too many requests. Please wait before trying again.");
      }
    },
  });
}

function togglePasswordVisibility() {
  showPassword.value = !showPassword.value;
}

// Mobile app detection
const isMobileApp = computed(() => {
  if (typeof window === 'undefined') return false

  // Check for Capacitor
  if (window.Capacitor && window.Capacitor.isNativePlatform()) {
    return true
  }

  // Check user agent
  const userAgent = navigator.userAgent || ''
  return userAgent.includes('DCCPHub-Mobile-App') || userAgent.includes('Capacitor')
})

// Lifecycle
onMounted(() => {
  if (page.props.flash.error) {
    toast.error(page.props.flash.error);
  }

  if (page.props.flash.success) {
    toast.success(page.props.flash.success);
  }
});

// SEO
useSeoMetaTags({
  title: "Log in",
});
</script>

<template>
  <Sonner position="top-center" />

  <div
    class="flex min-h-screen flex-col items-center justify-center bg-linear-to-b from-background/50 to-background px-4 py-8"
  >
    <Card
      class="w-full max-w-md shadow-lg transition-all duration-300 hover:shadow-xl"
    >
      <!-- Header -->
      <CardHeader>
        <CardTitle class="flex justify-center">
          <AuthenticationCardLogo />
        </CardTitle>
        <CardDescription class="text-center text-2xl font-light">
          Welcome Back
        </CardDescription>
      </CardHeader>

      <CardContent>
        <!-- Status Message -->
        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
          {{ status }}
        </div>

        <!-- Login Tabs -->
        <Tabs v-model="activeTab" class="w-full">
          <TabsList class="grid w-full grid-cols-2 rounded-lg p-1">
            <TabsTrigger value="password"> Password </TabsTrigger>
            <TabsTrigger value="login-link"> Login Link </TabsTrigger>
          </TabsList>

          <div class="mt-6">
            <!-- Password Login -->
            <TabsContent value="password" class="space-y-4">
              <form class="space-y-4" @submit.prevent="handlePasswordLogin">
                <div class="grid gap-2">
                  <Label for="email">Email</Label>
                  <Input
                    id="email"
                    v-model="passwordForm.email"
                    type="email"
                    placeholder="name@example.com"
                    required
                    autofocus
                    autocomplete="username"
                  />
                  <InputError :message="passwordForm.errors.email" />
                </div>

                <div class="grid gap-2">
                  <div class="flex items-center justify-between">
                    <Label for="password">Password</Label>
                    <Link
                      v-if="canResetPassword"
                      :href="route('password.request')"
                      class="text-sm text-muted-foreground hover:text-primary hover:underline underline-offset-4"
                    >
                      Forgot password?
                    </Link>
                  </div>
                  <div class="relative">
                    <Input
                      id="password"
                      v-model="passwordForm.password"
                      :type="showPassword ? 'text' : 'password'"
                      required
                      autocomplete="current-password"
                    />
                    <button
                      type="button"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground focus:outline-none"
                      @click="togglePasswordVisibility"
                    >
                      <svg
                        v-if="showPassword"
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="lucide lucide-eye-off"
                      >
                        <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                        <path
                          d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"
                        ></path>
                        <path
                          d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"
                        ></path>
                        <line x1="2" x2="22" y1="2" y2="22"></line>
                      </svg>
                      <svg
                        v-else
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="lucide lucide-eye"
                      >
                        <path
                          d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"
                        ></path>
                        <circle cx="12" cy="12" r="3"></circle>
                      </svg>
                    </button>
                  </div>
                  <InputError :message="passwordForm.errors.password" />
                </div>

                <div class="flex items-center space-x-2">
                  <Checkbox
                    id="remember"
                    v-model:checked="passwordForm.remember"
                    name="remember"
                  />
                  <label for="remember" class="text-sm text-muted-foreground">
                    Remember me
                  </label>
                </div>

                <Button
                  type="submit"
                  class="w-full"
                  :class="{ 'opacity-75': passwordForm.processing }"
                  :disabled="isProcessing"
                >
                  {{ passwordForm.processing ? "Signing in..." : "Sign in" }}
                </Button>
              </form>
            </TabsContent>

            <!-- Login Link -->
            <TabsContent value="login-link" class="space-y-4">
              <div class="text-sm text-muted-foreground">
                We'll send you a login link for password-free sign in.
              </div>
              <form class="space-y-4" @submit.prevent="handleLoginLink">
                <div class="grid gap-2">
                  <Label for="login-link-email">Email</Label>
                  <Input
                    id="login-link-email"
                    v-model="loginLinkForm.email"
                    type="email"
                    required
                    placeholder="name@example.com"
                  />
                  <InputError :message="loginLinkForm.errors.email" />
                </div>

                <Button
                  type="submit"
                  class="w-full"
                  :class="{ 'opacity-75': loginLinkForm.processing }"
                  :disabled="isProcessing"
                >
                  {{
                    loginLinkForm.processing ? "Sending..." : "Send Login Link"
                  }}
                </Button>
              </form>
            </TabsContent>
          </div>
        </Tabs>

        <!-- OAuth Section -->
        <div v-if="hasOauthProviders" class="mt-6">
          <div class="relative">
            <div class="absolute inset-0 flex items-center">
              <span class="w-full border-t" />
            </div>
            <div class="relative flex justify-center text-xs uppercase">
              <span class="bg-background px-2 text-muted-foreground">
                Or continue with
              </span>
            </div>
          </div>

          <div class="mt-6 grid gap-2">
            <MobileSocialLoginButton
              v-if="isMobileApp"
              v-for="provider in availableOauthProviders"
              :key="provider.slug"
              :provider="provider"
              :disabled="isProcessing"
            />
            <SocialLoginButton
              v-else
              v-for="provider in availableOauthProviders"
              :key="provider.slug"
              :provider="provider"
              :disabled="isProcessing"
            />
          </div>
        </div>

        <!-- Sign Up Link -->
        <div class="mt-6 text-center text-sm text-muted-foreground">
          Don't have an account?
          <Link
            :href="route('register')"
            class="font-medium text-primary hover:underline underline-offset-4"
          >
            Sign up
          </Link>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
