<!-- /home/y1109/twitter-sns/front/app.vue -->
<template>
  <!-- ✅ レイアウトを有効化して各ページを包む -->
  <NuxtLayout>
    <NuxtPage />
  </NuxtLayout>

  <!-- 任意：ページ遷移時の細いローディングバー -->
  <NuxtLoadingIndicator />
</template>

<script setup lang="ts">
// 追加ロジック不要
</script>

<style>
/* ベースリセット（背景色は各ページで指定するため body 背景は外す） */
*,
*::before,
*::after { box-sizing: border-box; }

html, body, #__nuxt {
  height: 100%;
  margin: 0;
}

body {
  font-family: 'Noto Sans JP', system-ui, -apple-system, Segoe UI, Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;
  color: #333;
  line-height: 1.5;
}

/* ルート要素（任意） */
#__nuxt {
  display: flex;
  flex-direction: column;
}
</style>
