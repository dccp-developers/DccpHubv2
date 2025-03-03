<script setup lang="ts">
import type { HTMLAttributes } from "vue";

defineOptions({
  inheritAttrs: false,
});

interface Props {
  className?: HTMLAttributes["class"];
}

defineProps<Props>();
</script>

<template>
  <img
    src="/android-chrome-512x512.png"
    :class="className"
    v-bind="$attrs"
    alt="logo"
  />
</template>
