<!-- /home/y1109/twitter-sns/front/pages/login.vue -->
<template>
  <div class="auth-page">
    <form class="auth-form" novalidate @submit.prevent="onSubmit">
      <h2>ログイン</h2>

      <!-- メールアドレス -->
      <input
        v-model.trim="email"
        type="text"
        placeholder="メールアドレス"
        autocomplete="email"
        :aria-invalid="submitCount > 0 && !!errors.email"
      />
      <p v-if="submitCount > 0 && errors.email" class="auth-error">
        {{ errors.email }}
      </p>

      <!-- パスワード -->
      <input
        v-model="password"
        type="password"
        placeholder="パスワード"
        autocomplete="current-password"
        :aria-invalid="submitCount > 0 && !!errors.password"
      />
      <p v-if="submitCount > 0 && errors.password" class="auth-error">
        {{ errors.password }}
      </p>

      <!-- 認証エラー（バリデーションとは別） -->
      <p v-if="authErrorMessage" class="auth-error">
        {{ authErrorMessage }}
      </p>

      <button
        type="submit"
        class="auth-primary"
        :disabled="loading || isSubmitting"
      >
        {{ loading || isSubmitting ? 'ログイン中...' : 'ログイン' }}
      </button>

      <p class="link">
        <NuxtLink to="/register">新規登録はこちら</NuxtLink>
      </p>
    </form>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'default', ssr: false })

import { ref } from 'vue'
import { useForm } from 'vee-validate'
import * as yup from 'yup'

const { login } = useAuth()

// ✅ ログインは「入力必須」だけ
const schema = yup.object({
  email: yup
    .string()
    .required('メールアドレスを入力してください。')
    .email('有効なメールアドレス形式で入力してください。'),
  password: yup.string().required('パスワードを入力してください。'),
})

const { errors, handleSubmit, defineField, isSubmitting, submitCount } = useForm({
  validationSchema: schema,
  // ✅ 入力中に勝手に検証しない（ボタン押下時のみ）
  validateOnBlur: false,
  validateOnChange: false,
  validateOnInput: false,
  validateOnModelUpdate: false,
})

// v-model 用（defineField の返り値は Ref なのでそのまま v-model で使える）
const [email] = defineField('email')
const [password] = defineField('password')

const loading = ref(false)
const authErrorMessage = ref('')

// ✅ バリデーション通過後にだけ実行される submit
const onSubmit = handleSubmit(async (values) => {
  authErrorMessage.value = ''
  loading.value = true

  try {
    await login(values.email, values.password)
    await navigateTo('/')
  } catch (e: any) {
    authErrorMessage.value = 'メールアドレスまたはパスワードが正しくありません。'
  } finally {
    loading.value = false
  }
})
</script>
