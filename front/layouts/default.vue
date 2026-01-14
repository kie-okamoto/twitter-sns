<!-- /home/y1109/twitter-sns/front/layouts/default.vue -->
<template>
  <div>
    <TheHeader /> <!-- ✅ コンポーネントを呼び出す -->
    <main>
      <slot /> <!-- 各ページの内容を表示 -->
    </main>
  </div>
</template>

<script setup lang="ts">
import TheHeader from '~/components/TheHeader.vue'
</script>
