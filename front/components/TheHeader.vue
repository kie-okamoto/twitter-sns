<template>
  <header class="header">
    <!-- SHAREロゴ：ホームへ -->
    <NuxtLink to="/" class="header__brand" aria-label="ホームへ">
      <img src="/images/logo.png" alt="SHARE" class="header__logo" />
    </NuxtLink>

    <nav class="header__nav">
      <NuxtLink to="/register" class="header__link">新規登録</NuxtLink>
      <NuxtLink to="/login" class="header__link">ログイン</NuxtLink>
    </nav>
  </header>
</template>
