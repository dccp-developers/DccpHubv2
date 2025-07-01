<script setup>
import { useSeoMetaTags } from "@/Composables/useSeoMetaTags.js";
import WebLayout from "@/Layouts/WebLayout.vue";
import HeroSection from "@/Components/LandingPage/HeroSection.vue";
import FeaturesSection from "@/Components/LandingPage/FeaturesSection.vue";
import PricingSection from "@/Components/LandingPage/PricingSection.vue";
import CtaSection from "@/Components/LandingPage/CtaSection.vue";
import { router } from "@inertiajs/vue3";
import { onMounted } from "vue";
import { route } from "ziggy-js";

const props = defineProps({
  canLogin: {
    type: Boolean,
  },
  canRegister: {
    type: Boolean,
  },
  seo: {
    type: Object,
    default: () => null,
  },
});

useSeoMetaTags(props.seo);

// Function to detect if user is on mobile
const isMobileDevice = () => {
  return (
    /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
      navigator.userAgent,
    ) || window.innerWidth <= 768
  );
};

// Redirect to login if on mobile
onMounted(() => {
  if (isMobileDevice()) {
    router.visit(route("login"));
  }
});

const features = [
  {
    icon: "📚",
    title: "Comprehensive Course Management",
    description:
      "Easily manage your courses, assignments, and grades with our intuitive interface designed for both faculty and students.",
    image: "https://via.placeholder.com/800x500/1a1a2e/FFFFFF?text=Course+Management",
  },
  {
    icon: "👩‍🏫",
    title: "Faculty Collaboration Tools",
    description:
      "Empower faculty members with tools for collaboration, communication, and resource sharing to enhance the learning experience.",
    image: "https://via.placeholder.com/800x500/0f3460/FFFFFF?text=Faculty+Collaboration",
  },
  {
    icon: "🗓️",
    title: "Streamlined Scheduling",
    description:
      "Access a centralized calendar for classes, exams, and events, ensuring you never miss an important date.",
    image: "https://via.placeholder.com/800x500/16213e/FFFFFF?text=Scheduling",
  },
  {
    icon: "💬",
    title: "Real-time Communication",
    description:
      "Stay connected with peers and instructors through integrated messaging and notification systems.",
    image: "https://via.placeholder.com/800x500/1a1a2e/FFFFFF?text=Communication",
  },
  {
    icon: "📊",
    title: "Performance Tracking",
    description:
      "Monitor your academic progress with detailed analytics and reports, helping you stay on track for success.",
    image: "https://via.placeholder.com/800x500/0f3460/FFFFFF?text=Performance+Tracking",
  },
  {
    icon: "🔒",
    title: "Secure Access",
    description:
      "Your data is protected with top-notch security measures, ensuring a safe environment for all users.",
    image: "https://via.placeholder.com/800x500/16213e/FFFFFF?text=Secure+Access",
  },
  {
    icon: "🌐",
    title: "Accessible Anywhere",
    description:
      "Access the portal from any device, whether you're at home, in class, or on the go, making learning flexible and convenient.",
    image: "https://via.placeholder.com/800x500/1a1a2e/FFFFFF?text=Accessible+Anywhere",
  },
  {
    icon: "🎓",
    title: "Community Engagement",
    description:
      "Join a vibrant community of learners and educators, fostering collaboration and support throughout your academic journey.",
    image: "https://via.placeholder.com/800x500/0f3460/FFFFFF?text=Community+Engagement",
  },
  {
    icon: "✨",
    title: "Continuous Improvement",
    description:
      "We are committed to evolving our platform based on user feedback, ensuring that DCCPHub meets the needs of our community.",
    image: "https://via.placeholder.com/800x500/16213e/FFFFFF?text=Continuous+Improvement",
  },
];

const pricingFeatures = [
  "Free access for all students and faculty",
  "Comprehensive support and resources",
  "Regular updates and new features",
  "User-friendly interface",
  "Dedicated community forums",
];
const sponsorLinks = {
  github: "https://github.com/sponsors/pushpak1300",
  x: "https://x.com/pushpak1300",
};

const faqItems = [
  {
    value: "item-1",
    title: "Is DCCPHub really free?",
    content:
      "Yes! DCCPHub is completely free for all students and faculty members, providing essential tools without any hidden fees.",
  },
  {
    value: "item-2",
    title: "How can I contribute?",
    content:
      "You can contribute by providing feedback, suggesting features, or participating in community discussions. Your input is invaluable!",
  },
  {
    value: "item-3",
    title: "Why should I use DCCPHub?",
    content:
      "DCCPHub simplifies your academic life, providing all the tools you need in one place, enhancing your learning experience and productivity.",
  },
];

const githubUrl = "https://github.com/dccp-developers/DccpHubv2";
</script>

<template>
  <WebLayout :can-login="canLogin" :can-register="canRegister">
    <HeroSection :github-url="githubUrl" />
    <FeaturesSection :features="features" :github-url="githubUrl" />
    <!-- <PricingSection
      :pricing-features="pricingFeatures"
      :sponsor-links="sponsorLinks"
      :faq-items="faqItems"
    /> -->
    <CtaSection :github-url="githubUrl" />
  </WebLayout>
</template>

<style scoped>
/* Styles removed for simplicity to resolve errors */
</style>
