<script setup>
import PricingCard from "@/Components/PricingCard.vue";
import Accordion from "@/Components/shadcn/ui/accordion/Accordion.vue";
import AccordionContent from "@/Components/shadcn/ui/accordion/AccordionContent.vue";
import AccordionItem from "@/Components/shadcn/ui/accordion/AccordionItem.vue";
import AccordionTrigger from "@/Components/shadcn/ui/accordion/AccordionTrigger.vue";
import Button from "@/Components/shadcn/ui/button/Button.vue";
import { Icon } from "@iconify/vue";
import { Link } from "@inertiajs/vue3";

defineProps({
  pricingFeatures: {
    type: Array,
    required: true,
  },
  sponsorLinks: {
    type: Object,
    required: true,
  },
  faqItems: {
    type: Array,
    required: true,
  },
});
</script>

<template>
  <section id="pricing" class="border-t">
    <div class="container mx-auto px-4 py-16 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mx-auto max-w-3xl text-center">
        <h2 class="text-center text-2xl font-bold tracking-tight sm:text-4xl">
          Always Free for Students and Faculty 🎓
        </h2>
        <p class="mx-auto mt-4 max-w-2xl text-center text-muted-foreground">
          DCCPHub is committed to providing essential tools for free, ensuring
          accessibility for all.
        </p>
      </div>

      <!-- Pricing Card -->
      <PricingCard
        class="mx-auto mt-16"
        :features="pricingFeatures"
        :price="0"
        plan="What's included?"
        billing-period="Free Forever"
      >
        <template #action>
          <Button :as="Link" :href="route('dashboard')"> Get Started </Button>
        </template>
        <template #footer>
          <div
            class="flex w-full flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
          >
            <p class="text-sm">Want to support the development?</p>
            <div class="flex gap-4">
              <Button
                variant="outline"
                as="a"
                :href="sponsorLinks.github"
                target="_blank"
              >
                <Icon
                  icon="mdi:github"
                  class="mr-2 size-4"
                  aria-hidden="true"
                />
                Sponsor
              </Button>
              <Button
                variant="outline"
                as="a"
                :href="sponsorLinks.x"
                target="_blank"
              >
                <Icon
                  icon="ri:twitter-x-line"
                  class="mr-2 size-4"
                  aria-hidden="true"
                />
                Follow Us
              </Button>
            </div>
          </div>
        </template>
      </PricingCard>
      <!-- FAQ Section -->
      <div class="mx-auto mt-16 text-center">
        <h2 class="text-2xl font-bold">Frequently Asked Questions</h2>
        <Accordion
          type="single"
          class="mt-8 w-full text-left"
          collapsible
          default-value="item-1"
        >
          <AccordionItem
            v-for="item in faqItems"
            :key="item.value"
            :value="item.value"
          >
            <AccordionTrigger class="text-lg">
              {{ item.title }}
            </AccordionTrigger>
            <AccordionContent>
              {{ item.content }}
            </AccordionContent>
          </AccordionItem>
        </Accordion>
      </div>
    </div>
  </section>
</template>
