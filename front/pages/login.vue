<!-- /home/y1109/twitter-sns/front/pages/login.vue -->
<template>
  <div class="auth-page">
    <form class="auth-form" @submit.prevent="onSubmit">
      <h2>ログイン</h2>

      <input
        v-model.trim="email"
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

      <p v-if="errorMessage" class="error">
        {{ errorMessage }}
      </p>

      <button type="submit" class="primary" :disabled="loading">
        {{ loading ? 'ログイン中...' : 'ログイン' }}
      </button>

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

const loading = ref(false)
const errorMessage = ref('')

const onSubmit = async () => {
  errorMessage.value = ''
  loading.value = true

  try {
    // useAuth の login は失敗時に throw する想定
    await login(email.value, password.value)
    await navigateTo('/')
  } catch (e: any) {
    errorMessage.value = 'メールアドレスまたはパスワードが正しくありません。'
  } finally {
    loading.value = false
  }
}
</script>
