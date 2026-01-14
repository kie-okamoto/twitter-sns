<template>
  <header class="header">
    <div class="header__left">
      <!-- ✅ public/images/logo.png を参照 -->
      <img src="/images/logo.png" alt="Share ロゴ" class="header__logo" />
    </div>

    <nav class="header__nav">
      <NuxtLink to="/register" class="header__link">新規登録</NuxtLink>
      <NuxtLink to="/login" class="header__link">ログイン</NuxtLink>
    </nav>
  </header>
</template>

<style scoped>
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #0f1824;
  padding: 12px 48px;
}

.header__logo {
  width: 140px;
  height: auto;
  display: block;
}

.header__nav {
  display: flex;
  gap: 20px;
}

.header__link {
  color: #fff;
  font-size: 16px;
  text-decoration: none;
}

.header__link:hover {
  text-decoration: underline;
}
</style>
