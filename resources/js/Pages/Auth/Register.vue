<script setup lang="ts">
import InputError from "@/Components/InputError.vue";
import TextLink from "@/Components/TextLink.vue";
import { Alert, AlertDescription } from "@/Components/shadcn/ui/alert";
import { Button } from "@/Components/shadcn/ui/button";
import { Card, CardContent } from "@/Components/shadcn/ui/card";
import { Input } from "@/Components/shadcn/ui/input";
import { Label } from "@/Components/shadcn/ui/label";
import { Progress } from "@/Components/shadcn/ui/progress";
import {
    Stepper,
    StepperDescription,
    StepperIndicator,
    StepperItem,
    StepperSeparator,
    StepperTitle
} from "@/Components/shadcn/ui/stepper";
import AuthBase from "@/Layouts/AuthLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { CheckCircle2, Eye, EyeOff, LoaderCircle, Mail, School, User, UserCircle, UserCog } from "lucide-vue-next";
import { computed, ref } from "vue";
import { toast } from "vue-sonner";
import axios from "axios";

// Form state
const step = ref(1);
const userType = ref("");
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);
const isLoading = ref(false);

// Form data
const emailForm = useForm({
    email: "",
});
const idForm = useForm({
    id: "",
});
const finalForm = useForm({
    id: "",
    email: "",
    name: "",
    phone: "",
    password: "",
    password_confirmation: "",
    role: "",
    person_id: "",
    person_type: "",
});

// Computed properties
const progressPercentage = computed(() => {
    return ((step.value - 1) / 3) * 100;
});

const isProcessing = computed(() => {
    return emailForm.processing || idForm.processing || finalForm.processing || isLoading.value;
});

// Step descriptions
const stepInfo = [
    {
        title: "Choose Role",
        description: "Select whether you're a student or instructor",
        icon: UserCog
    },
    {
        title: "Email Verification",
        description: "Provide your email address",
        icon: Mail
    },
    {
        title: "ID Verification",
        description: "Enter your institutional ID",
        icon: UserCircle
    },
    {
        title: "Complete Profile",
        description: "Finalize your account details",
        icon: CheckCircle2
    }
];

// Methods
const nextStep = async () => {
    try {
        isLoading.value = true;

        if (step.value === 1) {
            if (!userType.value) {
                toast.error("Please select a user type.");
                return;
            }
            finalForm.role = userType.value;
            step.value++;
        } else if (step.value === 2) {
            if (!emailForm.email) {
                toast.error("Please enter your email.");
                return;
            }

            const response = await axios.post("/api/check-email", {
                email: emailForm.email,
                userType: userType.value,
            });
            if (response.data.success) {
                toast.success(response.data.success);
                finalForm.email = emailForm.email;
                step.value++;
            } else {
                toast.error(response.data.error);
            }
        } else if (step.value === 3) {
            const response = await axios.post("/api/check-id", {
                id: idForm.id,
                email: emailForm.email,
                userType: userType.value,
            });
            if (response.data.success) {
                toast.success(response.data.success);
                finalForm.id = idForm.id;
                finalForm.name = response.data.fullName;
                step.value++;
            } else {
                toast.error(response.data.error);
            }
        }
    } catch (error) {
        console.error("Error during API call:", error);
        toast.error(
            "An error occurred while processing your request. Please try again.",
        );
    } finally {
        isLoading.value = false;
    }
};

const previousStep = () => {
    if (step.value > 1) {
        step.value--;
    }
};

const submit = () => {
    finalForm.post(route("register"), {
        onFinish: () => finalForm.reset("password", "password_confirmation"),
    });
};

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const togglePasswordConfirmationVisibility = () => {
    showPasswordConfirmation.value = !showPasswordConfirmation.value;
};
</script>

<template>
    <AuthBase title="Create an account" description="Join our educational platform">
        <Head title="Register" />

        <!-- Progress indicator -->
        <div class="mb-6">
            <Progress :value="progressPercentage" class="h-2" />
            <div class="mt-1 text-xs text-right text-muted-foreground">
                Step {{ step }} of 4
            </div>
        </div>

        <!-- Stepper navigation -->
        <Stepper v-model="step" class="mb-6 justify-between" :linear="false">
            <StepperItem v-for="(info, index) in stepInfo" :key="index" :step="index + 1"
                         class="flex-col items-center">
                <StepperIndicator>
                    <component :is="info.icon" class="h-5 w-5" />
                </StepperIndicator>
                <div class="hidden sm:block text-center mt-2">
                    <StepperTitle class="text-xs">{{ info.title }}</StepperTitle>
                </div>
                <StepperSeparator v-if="index < stepInfo.length - 1" />
            </StepperItem>
        </Stepper>

        <!-- Current step information -->
        <div class="mb-6">
            <h2 class="text-xl font-semibold">{{ stepInfo[step-1].title }}</h2>
            <p class="text-sm text-muted-foreground">{{ stepInfo[step-1].description }}</p>
        </div>

        <Card class="mb-6 border-primary/10 bg-primary/5">
            <CardContent class="p-4">
                <Alert>
                    <component :is="stepInfo[step-1].icon" class="h-4 w-4 mr-2" />
                    <AlertDescription>
                        <span v-if="step === 1">
                            Choose your role to customize your experience. Students can access courses and assignments, while instructors can create and manage educational content.
                        </span>
                        <span v-else-if="step === 2">
                            We'll use your email for account verification and important notifications. Make sure to provide a valid email address.
                        </span>
                        <span v-else-if="step === 3">
                            Enter your institutional ID to verify your affiliation. This helps us connect you with the right courses and resources.
                        </span>
                        <span v-else>
                            Complete your profile information and create a secure password to finalize your account setup.
                        </span>
                    </AlertDescription>
                </Alert>
            </CardContent>
        </Card>

        <form @submit.prevent="step === 4 ? submit() : nextStep()" class="flex flex-col gap-6">
            <!-- Step 1: Role Selection -->
            <div v-if="step === 1" class="flex flex-col space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Card
                        :class="[`cursor-pointer transition-all duration-200 hover:border-primary`,
                                userType === 'student' ? 'border-primary bg-primary/5' : '']"
                        @click="userType = 'student'">
                        <CardContent class="p-6 flex items-center space-x-4">
                            <div class="bg-primary/10 p-3 rounded-full">
                                <School class="h-6 w-6 text-primary" />
                            </div>
                            <div>
                                <h3 class="font-medium">Student</h3>
                                <p class="text-sm text-muted-foreground">Access courses and assignments</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card
                        :class="[`cursor-pointer transition-all duration-200 hover:border-primary`,
                                userType === 'instructor' ? 'border-primary bg-primary/5' : '']"
                        @click="userType = 'instructor'">
                        <CardContent class="p-6 flex items-center space-x-4">
                            <div class="bg-primary/10 p-3 rounded-full">
                                <User class="h-6 w-6 text-primary" />
                            </div>
                            <div>
                                <h3 class="font-medium">Instructor</h3>
                                <p class="text-sm text-muted-foreground">Create and manage courses</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
                <input type="radio" v-model="userType" value="student" class="hidden" required />
                <input type="radio" v-model="userType" value="instructor" class="hidden" required />
            </div>

            <!-- Step 2: Email Verification -->
            <div v-if="step === 2" class="flex flex-col space-y-4">
                <div class="space-y-2">
                    <Label for="email" class="text-base">Email Address</Label>
                    <p class="text-sm text-muted-foreground">We'll send you a verification link to confirm your email</p>
                </div>
                <div class="relative">
                    <Mail class="absolute left-3 top-3 h-5 w-5 text-muted-foreground" />
                    <Input
                        id="email"
                        type="email"
                        required
                        v-model="emailForm.email"
                        placeholder="your.email@example.com"
                        class="pl-10"
                    />
                </div>
                <InputError :message="emailForm.errors.email" />
            </div>

            <!-- Step 3: ID Verification -->
            <div v-if="step === 3" class="flex flex-col space-y-4">
                <div class="space-y-2">
                    <Label for="id" class="text-base">Your Institutional ID</Label>
                    <p class="text-sm text-muted-foreground">Enter your ID to verify your affiliation</p>
                </div>
                <div class="relative">
                    <UserCircle class="absolute left-3 top-3 h-5 w-5 text-muted-foreground" />
                    <Input
                        id="id"
                        type="text"
                        required
                        v-model="idForm.id"
                        placeholder="Enter your ID number"
                        class="pl-10"
                    />
                </div>
                <InputError :message="idForm.errors.id" />
            </div>

            <!-- Step 4: Complete Profile -->
            <div v-if="step === 4" class="space-y-6">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="name" class="text-base">Full Name</Label>
                        <Input
                            id="name"
                            type="text"
                            required
                            v-model="finalForm.name"
                            placeholder="Your full name"
                        />
                        <InputError :message="finalForm.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="phone" class="text-base">Phone Number</Label>
                        <p class="text-xs text-muted-foreground">Optional, but recommended for account recovery</p>
                        <Input
                            id="phone"
                            type="text"
                            v-model="finalForm.phone"
                            placeholder="Your phone number"
                        />
                        <InputError :message="finalForm.errors.phone" />
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="password" class="text-base">Password</Label>
                        <div class="relative">
                            <Input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                v-model="finalForm.password"
                                placeholder="Create a secure password"
                                class="pr-10"
                            />
                            <button
                                type="button"
                                @click="togglePasswordVisibility"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5 text-muted-foreground hover:text-foreground focus:outline-none"
                            >
                                <Eye v-if="!showPassword" class="h-5 w-5" />
                                <EyeOff v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <p class="text-xs text-muted-foreground">Password must be at least 8 characters long</p>
                        <InputError :message="finalForm.errors.password" />
                    </div>

                    <div class="space-y-2">
                        <Label for="password_confirmation" class="text-base">Confirm Password</Label>
                        <div class="relative">
                            <Input
                                id="password_confirmation"
                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                required
                                v-model="finalForm.password_confirmation"
                                placeholder="Confirm your password"
                                class="pr-10"
                            />
                            <button
                                type="button"
                                @click="togglePasswordConfirmationVisibility"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5 text-muted-foreground hover:text-foreground focus:outline-none"
                            >
                                <Eye v-if="!showPasswordConfirmation" class="h-5 w-5" />
                                <EyeOff v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <InputError :message="finalForm.errors.password_confirmation" />
                    </div>
                </div>
            </div>

            <!-- Navigation buttons -->
            <div class="flex gap-4 mt-2">
                <Button
                    v-if="step > 1"
                    type="button"
                    variant="outline"
                    class="flex-1"
                    @click="previousStep()"
                    :disabled="isProcessing"
                >
                    Back
                </Button>
                <Button
                    type="submit"
                    class="flex-1"
                    :disabled="isProcessing"
                >
                    <LoaderCircle v-if="isProcessing" class="h-4 w-4 animate-spin mr-2" />
                    {{ step === 4 ? "Create Account" : "Continue" }}
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground mt-4">
                Already have an account?
                <TextLink :href="route('login')" class="underline underline-offset-4 text-primary">Log in</TextLink>
            </div>
        </form>
    </AuthBase>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s, transform 0.3s;
}
.fade-enter, .fade-leave-to {
    opacity: 0;
    transform: translateY(10px);
}
</style>
