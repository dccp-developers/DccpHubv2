<script setup>
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import FacultyLayout from '@/Layouts/FacultyLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/shadcn/ui/tabs'
import { Badge } from '@/Components/ui/badge.js'
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/shadcn/ui/avatar'
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue'
import LinkedAccountsForm from '@/Pages/Profile/Partials/LinkedAccountsForm.vue'
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue'
import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue'
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue'
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue'
import {
  UserIcon,
  ShieldCheckIcon,
  KeyIcon,
  LinkIcon,
  ComputerDesktopIcon,
  TrashIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  confirmsTwoFactorAuthentication: {
    type: Boolean,
    default: false,
  },
  sessions: {
    type: Array,
    default: () => [],
  },
  availableOauthProviders: {
    type: Object,
    default: () => {},
  },
  activeOauthProviders: {
    type: Array,
    default: () => [],
  },
})

// Get the current user from Inertia page props
const page = usePage()
const user = computed(() => page.props.auth.user)

// Determine if the user is faculty based on role
const isFaculty = computed(() => {
  return user.value && user.value.role === 'faculty'
})

// Choose the appropriate layout component
const LayoutComponent = computed(() => {
  return isFaculty.value ? FacultyLayout : AppLayout
})

// Active tab state
const activeTab = ref('profile')

// User initials for avatar fallback
const userInitials = computed(() => {
  if (!user.value?.name) return 'U'
  return user.value.name
    .split(' ')
    .map(word => word.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

// User role display
const userRoleDisplay = computed(() => {
  if (!user.value?.role) return 'User'
  return user.value.role.charAt(0).toUpperCase() + user.value.role.slice(1)
})

// Account status
const accountStatus = computed(() => {
  return user.value?.is_active ? 'Active' : 'Inactive'
})

// Security score calculation
const securityScore = computed(() => {
  let score = 0
  let total = 0

  // Email verification
  total += 20
  if (user.value?.email_verified_at) score += 20

  // Two-factor authentication
  total += 30
  if (user.value?.two_factor_enabled) score += 30

  // Profile photo
  total += 10
  if (user.value?.profile_photo_url) score += 10

  // Connected accounts
  total += 20
  if (props.activeOauthProviders?.length > 0) score += 20

  // Active sessions management
  total += 20
  if (props.sessions?.length <= 2) score += 20 // Fewer sessions = better security

  return Math.round((score / total) * 100)
})

const securityScoreColor = computed(() => {
  const score = securityScore.value
  if (score >= 80) return 'text-green-600 dark:text-green-400'
  if (score >= 60) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-red-600 dark:text-red-400'
})

// Handle image loading errors
const handleImageError = (event) => {
  console.warn('Profile photo failed to load:', user.value?.profile_photo_url)
  // The AvatarFallback will automatically show when the image fails to load
}
</script>

<template>
  <component :is="LayoutComponent" title="Profile Settings">
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 class="text-xl font-semibold leading-tight text-foreground">
            Profile Settings
          </h2>
          <p class="text-sm text-muted-foreground mt-1">
            Manage your account settings and preferences
          </p>
        </div>

        <!-- Security Score Badge (Desktop) -->
        <div class="hidden sm:flex items-center space-x-2">
          <div class="flex items-center space-x-2 px-3 py-1.5 bg-muted rounded-full">
            <ShieldCheckIcon class="h-4 w-4 text-muted-foreground" />
            <span class="text-xs font-medium text-muted-foreground">Security Score:</span>
            <span :class="['text-xs font-bold', securityScoreColor]">{{ securityScore }}%</span>
          </div>
        </div>
      </div>
    </template>

    <div class="space-y-6">
      <!-- Profile Header Card -->
      <Card class="border-0 shadow-sm bg-gradient-to-r from-primary/5 to-primary/10">
        <CardContent class="p-6">
          <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
            <!-- Avatar -->
            <div class="relative">
              <Avatar class="h-20 w-20 sm:h-24 sm:w-24 ring-4 ring-background shadow-lg">
                <AvatarImage
                  :src="user?.profile_photo_url"
                  :alt="user?.name"
                  class="object-cover"
                  @error="handleImageError"
                />
                <AvatarFallback class="text-lg font-semibold bg-primary text-primary-foreground">
                  {{ userInitials }}
                </AvatarFallback>
              </Avatar>

              <!-- Status indicator -->
              <div class="absolute -bottom-1 -right-1 h-6 w-6 rounded-full border-2 border-background flex items-center justify-center"
                   :class="user?.is_active ? 'bg-green-500' : 'bg-gray-400'">
                <CheckCircleIcon v-if="user?.is_active" class="h-3 w-3 text-white" />
                <ExclamationTriangleIcon v-else class="h-3 w-3 text-white" />
              </div>
            </div>

            <!-- User Info -->
            <div class="flex-1 min-w-0">
              <div class="flex flex-col sm:flex-row sm:items-center gap-2 mb-2">
                <h3 class="text-xl font-semibold text-foreground truncate">{{ user?.name }}</h3>
                <Badge :variant="user?.is_active ? 'default' : 'secondary'" class="w-fit">
                  {{ accountStatus }}
                </Badge>
              </div>

              <p class="text-sm text-muted-foreground mb-2">{{ user?.email }}</p>

              <div class="flex flex-wrap items-center gap-2">
                <Badge variant="outline" class="text-xs">
                  {{ userRoleDisplay }}
                </Badge>

                <Badge v-if="user?.email_verified_at" variant="outline" class="text-xs text-green-600 border-green-200">
                  <CheckCircleIcon class="h-3 w-3 mr-1" />
                  Verified
                </Badge>

                <Badge v-if="user?.two_factor_enabled" variant="outline" class="text-xs text-blue-600 border-blue-200">
                  <ShieldCheckIcon class="h-3 w-3 mr-1" />
                  2FA Enabled
                </Badge>
              </div>
            </div>

            <!-- Security Score (Mobile) -->
            <div class="sm:hidden w-full">
              <div class="flex items-center justify-between p-3 bg-background rounded-lg border">
                <div class="flex items-center space-x-2">
                  <ShieldCheckIcon class="h-4 w-4 text-muted-foreground" />
                  <span class="text-sm font-medium">Security Score</span>
                </div>
                <span :class="['text-sm font-bold', securityScoreColor]">{{ securityScore }}%</span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Main Content Tabs -->
      <Tabs v-model="activeTab" class="w-full">
        <TabsList class="grid w-full grid-cols-2 sm:grid-cols-6 h-auto p-1 bg-muted/50">
          <TabsTrigger
            value="profile"
            class="flex flex-col sm:flex-row items-center gap-1 sm:gap-2 py-2 px-2 sm:px-4 text-xs sm:text-sm data-[state=active]:bg-background"
          >
            <UserIcon class="h-4 w-4" />
            <span class="hidden sm:inline">Profile</span>
            <span class="sm:hidden">Info</span>
          </TabsTrigger>

          <TabsTrigger
            v-if="$page.props.jetstream.canManageTwoFactorAuthentication"
            value="security"
            class="flex flex-col sm:flex-row items-center gap-1 sm:gap-2 py-2 px-2 sm:px-4 text-xs sm:text-sm data-[state=active]:bg-background"
          >
            <ShieldCheckIcon class="h-4 w-4" />
            <span class="hidden sm:inline">Security</span>
            <span class="sm:hidden">2FA</span>
          </TabsTrigger>

          <TabsTrigger
            v-if="$page.props.jetstream.canUpdatePassword"
            value="password"
            class="flex flex-col sm:flex-row items-center gap-1 sm:gap-2 py-2 px-2 sm:px-4 text-xs sm:text-sm data-[state=active]:bg-background"
          >
            <KeyIcon class="h-4 w-4" />
            <span class="hidden sm:inline">Password</span>
            <span class="sm:hidden">Pass</span>
          </TabsTrigger>

          <TabsTrigger
            v-if="Object.keys(availableOauthProviders).length"
            value="accounts"
            class="flex flex-col sm:flex-row items-center gap-1 sm:gap-2 py-2 px-2 sm:px-4 text-xs sm:text-sm data-[state=active]:bg-background"
          >
            <LinkIcon class="h-4 w-4" />
            <span class="hidden sm:inline">Accounts</span>
            <span class="sm:hidden">Links</span>
          </TabsTrigger>

          <TabsTrigger
            v-if="sessions.length > 0"
            value="sessions"
            class="flex flex-col sm:flex-row items-center gap-1 sm:gap-2 py-2 px-2 sm:px-4 text-xs sm:text-sm data-[state=active]:bg-background"
          >
            <ComputerDesktopIcon class="h-4 w-4" />
            <span class="hidden sm:inline">Sessions</span>
            <span class="sm:hidden">Devices</span>
          </TabsTrigger>

          <TabsTrigger
            v-if="$page.props.jetstream.hasAccountDeletionFeatures"
            value="danger"
            class="flex flex-col sm:flex-row items-center gap-1 sm:gap-2 py-2 px-2 sm:px-4 text-xs sm:text-sm data-[state=active]:bg-background text-destructive"
          >
            <TrashIcon class="h-4 w-4" />
            <span class="hidden sm:inline">Danger</span>
            <span class="sm:hidden">Delete</span>
          </TabsTrigger>
        </TabsList>

        <!-- Tab Contents -->
        <div class="mt-6">
          <!-- Profile Information Tab -->
          <TabsContent value="profile" class="mt-0">
            <Card v-if="$page.props.jetstream.canUpdateProfileInformation">
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <UserIcon class="h-5 w-5" />
                  Profile Information
                </CardTitle>
                <CardDescription>
                  Update your account's profile information and email address.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <UpdateProfileInformationForm :user="$page.props.auth.user" />
              </CardContent>
            </Card>
          </TabsContent>

          <!-- Security Tab -->
          <TabsContent value="security" class="mt-0">
            <Card v-if="$page.props.jetstream.canManageTwoFactorAuthentication">
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <ShieldCheckIcon class="h-5 w-5" />
                  Two-Factor Authentication
                </CardTitle>
                <CardDescription>
                  Add additional security to your account using two-factor authentication.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <TwoFactorAuthenticationForm :requires-confirmation="confirmsTwoFactorAuthentication" />
              </CardContent>
            </Card>
          </TabsContent>

          <!-- Password Tab -->
          <TabsContent value="password" class="mt-0">
            <Card v-if="$page.props.jetstream.canUpdatePassword">
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <KeyIcon class="h-5 w-5" />
                  Update Password
                </CardTitle>
                <CardDescription>
                  Ensure your account is using a long, random password to stay secure.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <UpdatePasswordForm />
              </CardContent>
            </Card>
          </TabsContent>

          <!-- Linked Accounts Tab -->
          <TabsContent value="accounts" class="mt-0">
            <Card v-if="Object.keys(availableOauthProviders).length">
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <LinkIcon class="h-5 w-5" />
                  Linked Accounts
                </CardTitle>
                <CardDescription>
                  Manage and remove your linked social media accounts.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <LinkedAccountsForm
                  :available-providers="availableOauthProviders"
                  :active-providers="activeOauthProviders"
                />
              </CardContent>
            </Card>
          </TabsContent>

          <!-- Browser Sessions Tab -->
          <TabsContent value="sessions" class="mt-0">
            <Card v-if="sessions.length > 0">
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <ComputerDesktopIcon class="h-5 w-5" />
                  Browser Sessions
                </CardTitle>
                <CardDescription>
                  Manage and log out your active sessions on other browsers and devices.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <LogoutOtherBrowserSessionsForm :sessions="sessions" />
              </CardContent>
            </Card>
          </TabsContent>

          <!-- Danger Zone Tab -->
          <TabsContent value="danger" class="mt-0">
            <Card v-if="$page.props.jetstream.hasAccountDeletionFeatures" class="border-destructive/20">
              <CardHeader>
                <CardTitle class="flex items-center gap-2 text-destructive">
                  <TrashIcon class="h-5 w-5" />
                  Delete Account
                </CardTitle>
                <CardDescription>
                  Permanently delete your account and all of its data. This action cannot be undone.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <DeleteUserForm />
              </CardContent>
            </Card>
          </TabsContent>
        </div>
      </Tabs>
    </div>
  </component>
</template>
