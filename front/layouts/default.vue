<template>
  <div class="defaultLayout">
    <TheHeader />
    <main class="defaultLayout__main">
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
import TheHeader from '~/components/TheHeader.vue'
</script>
