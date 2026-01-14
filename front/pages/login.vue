<template>
  <div class="auth-page">
    <form class="auth-form" @submit.prevent="onSubmit">
      <h2>ログイン</h2>

      <input
        v-model="email"
        type="email"
        placeholder="メールアドレス"
        required
        autocomplete="email"
      />
      <input
        v-model="password"
        type="password"
        placeholder="パスワード"
        required
        minlength="6"
        autocomplete="current-password"
      />

      <button type="submit" class="primary">ログイン</button>

      <p class="link">
        <NuxtLink to="/register">新規登録はこちら</NuxtLink>
      </p>
    </form>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'default', ssr: false })

const { login } = useAuth()
const email = ref('')
const password = ref('')

const onSubmit = async () => {
  await login(email.value, password.value)
  await navigateTo('/')
}
</script>

<style scoped>
.auth-page {
  min-height: calc(100vh - 64px);
  display: flex;
  justify-content: center;
  align-items: center;
  background: #0f1824;
}

.auth-form {
  width: 520px;
  background: #fff;
  color: #000;
  padding: 28px 36px;
  border-radius: 12px;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.22);
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.auth-form h2 {
  text-align: center;
  margin: 0 0 8px;
}

.auth-form input {
  padding: 12px 14px;
  border: 1px solid #8a8f98;
  border-radius: 10px;
  outline: none;
}

.primary {
  align-self: center;
  margin-top: 6px;
  padding: 10px 22px;
  border: 0;
  border-radius: 22px;
  background: linear-gradient(90deg, #6a11cb 0%, #2575fc 100%);
  color: #fff;
  font-weight: 700;
  cursor: pointer;
}

.primary:hover {
  opacity: 0.92;
}

.link {
  text-align: center;
  margin-top: 4px;
}

.link a {
  color: #2575fc;
  text-decoration: none;
}

.link a:hover {
  text-decoration: underline;
}
</style>
