<script setup>
import Button from "@/Components/shadcn/ui/button/Button.vue";
import Terminal from "@/Components/Terminal.vue";

defineProps({
  githubUrl: {
    type: String,
    required: true,
  },
});
</script>

<template>
  <section class="border-t">
    <div class="container mx-auto px-4 py-16 sm:px-6 lg:px-8">
      <div class="rounded-2xl px-6 py-12 sm:p-16">
        <div class="mx-auto max-w-2xl text-center">
          <h2 class="text-3xl font-bold tracking-tight sm:text-6xl">
            Ready to Enhance Your Learning Experience?
          </h2>
          <p class="mx-auto mt-4 max-w-xl text-lg">
            Join DCCPHub today and unlock the full potential of your academic
            journey. 🚀
          </p>
          <div class="mt-8 flex justify-center gap-4">
            <Button
              as="a"
              :href="githubUrl"
              target="_blank"
              rel="noopener noreferrer"
            >
              View on GitHub
            </Button>
          </div>
        </div>
      </div>
      <div class="mx-auto w-full sm:w-2/3 items-center justify-center">
        <Terminal />
      </div>
    </div>
  </section>
</template>
