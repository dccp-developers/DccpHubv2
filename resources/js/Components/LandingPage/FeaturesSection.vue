<script setup>
import Button from "@/Components/shadcn/ui/button/Button.vue";
import { Icon } from "@iconify/vue";
import { computed } from "vue";

const props = defineProps({
  heading: {
    type: String,
    default: "Features of DCCPHub 🌟",
  },
  description: {
    type: String,
    default: "Discover the powerful features designed to enhance your educational experience.",
  },
  features: {
    type: Array,
    required: true,
  },
  githubUrl: {
    type: String,
    required: true,
  },
});

// Group features into pairs for the grid layout
const featureGroups = computed(() => {
  // Ensure we have at least 4 features by adding placeholders if needed
  const paddedFeatures = [...props.features];
  while (paddedFeatures.length < 4) {
    paddedFeatures.push({
      icon: "📚",
      title: "Feature Coming Soon",
      description: "We're working on adding more exciting features to enhance your experience.",
      image: "https://shadcnblocks.com/images/block/placeholder-1.svg",
    });
  }

  // Take the first 4 features
  const mainFeatures = paddedFeatures.slice(0, 4);

  return {
    feature1: mainFeatures[0],
    feature2: mainFeatures[1],
    feature3: mainFeatures[2],
    feature4: mainFeatures[3],
  };
});
</script>

<template>
  <section id="features" class="py-24 md:py-32">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="mb-16 md:mb-24 flex flex-col items-center gap-6">
        <h2 class="text-center text-3xl font-semibold tracking-tight lg:max-w-3xl lg:text-5xl">
          {{ heading }}
        </h2>
        <p class="text-center text-lg font-medium text-muted-foreground md:max-w-4xl lg:text-xl">
          {{ description }}
        </p>
      </div>

      <div class="relative flex justify-center">
        <div class="border-muted relative flex w-full flex-col border md:w-4/5 lg:w-full">
          <div class="relative flex flex-col lg:flex-row">
            <div class="border-muted flex flex-col justify-between border-b border-solid p-10 lg:w-3/5 lg:border-r lg:border-b-0">
              <div>
                <div class="mb-4 text-4xl">{{ featureGroups.feature1.icon }}</div>
                <h3 class="text-xl font-semibold">{{ featureGroups.feature1.title }}</h3>
                <p class="text-muted-foreground">{{ featureGroups.feature1.description }}</p>
              </div>
              <img
                :src="featureGroups.feature1.image || 'https://shadcnblocks.com/images/block/placeholder-1.svg'"
                :alt="featureGroups.feature1.title"
                class="mt-8 aspect-[1.5] h-full w-full object-cover lg:aspect-[2.4]"
              />
            </div>
            <div class="flex flex-col justify-between p-10 lg:w-2/5">
              <div>
                <div class="mb-4 text-4xl">{{ featureGroups.feature2.icon }}</div>
                <h3 class="text-xl font-semibold">{{ featureGroups.feature2.title }}</h3>
                <p class="text-muted-foreground">{{ featureGroups.feature2.description }}</p>
              </div>
              <img
                :src="featureGroups.feature2.image || 'https://shadcnblocks.com/images/block/placeholder-2.svg'"
                :alt="featureGroups.feature2.title"
                class="mt-8 aspect-[1.45] h-full w-full object-cover"
              />
            </div>
          </div>
          <div class="border-muted relative flex flex-col border-t border-solid lg:flex-row">
            <div class="border-muted flex flex-col justify-between border-b border-solid p-10 lg:w-2/5 lg:border-r lg:border-b-0">
              <div>
                <div class="mb-4 text-4xl">{{ featureGroups.feature3.icon }}</div>
                <h3 class="text-xl font-semibold">{{ featureGroups.feature3.title }}</h3>
                <p class="text-muted-foreground">{{ featureGroups.feature3.description }}</p>
              </div>
              <img
                :src="featureGroups.feature3.image || 'https://shadcnblocks.com/images/block/placeholder-1.svg'"
                :alt="featureGroups.feature3.title"
                class="mt-8 aspect-[1.45] h-full w-full object-cover"
              />
            </div>
            <div class="flex flex-col justify-between p-10 lg:w-3/5">
              <div>
                <div class="mb-4 text-4xl">{{ featureGroups.feature4.icon }}</div>
                <h3 class="text-xl font-semibold">{{ featureGroups.feature4.title }}</h3>
                <p class="text-muted-foreground">{{ featureGroups.feature4.description }}</p>
              </div>
              <img
                :src="featureGroups.feature4.image || 'https://shadcnblocks.com/images/block/placeholder-2.svg'"
                :alt="featureGroups.feature4.title"
                class="mt-8 aspect-[1.5] h-full w-full object-cover lg:aspect-[2.4]"
              />
            </div>
          </div>
        </div>
      </div>

      <div class="mt-12 flex flex-col sm:flex-row justify-center gap-4">
        <Button
          as="a"
          href="https://docs.dccphub.com"
          target="_blank"
          rel="noopener noreferrer"
          size="lg"
          class="gap-2"
        >
          <Icon icon="lucide:book-open" class="size-4" aria-hidden="true" />
          Documentation
        </Button>
        <Button
          variant="secondary"
          as="a"
          :href="`${githubUrl}/discussions/categories/roadmap`"
          target="_blank"
          rel="noopener noreferrer"
          size="lg"
          class="gap-2"
        >
          <Icon icon="lucide:construction" class="size-4" aria-hidden="true" />
          Roadmap
        </Button>
      </div>
    </div>
  </section>
</template>
