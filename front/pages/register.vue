<!-- /home/y1109/twitter-sns/front/pages/register.vue -->
<template>
  <div class="auth-page">
    <form class="auth-form" novalidate @submit.prevent="onSubmit">
      <h2>新規登録</h2>

      <!-- ユーザーネーム -->
      <input
        v-model.trim="username"
        type="text"
        placeholder="ユーザーネーム"
        autocomplete="nickname"
        :aria-invalid="showErrors && !!errors.username"
      />
      <p v-if="showErrors && errors.username" class="auth-error">
        {{ errors.username }}
      </p>

      <!-- メールアドレス -->
      <input
        v-model.trim="email"
        type="email"
        placeholder="メールアドレス"
        autocomplete="email"
        :aria-invalid="showErrors && !!errors.email"
      />
      <p v-if="showErrors && errors.email" class="auth-error">
        {{ errors.email }}
      </p>

      <!-- パスワード -->
      <input
        v-model="password"
        type="password"
        placeholder="パスワード（6文字以上）"
        autocomplete="new-password"
        :aria-invalid="showErrors && !!errors.password"
      />
      <p v-if="showErrors && errors.password" class="auth-error">
        {{ errors.password }}
      </p>

      <!-- Firebase認証エラー（バリデーションとは別） -->
      <p v-if="authErrorMessage" class="auth-error">
        {{ authErrorMessage }}
      </p>

      <button
        type="submit"
        class="auth-primary"
        :disabled="loading || isSubmitting"
      >
        {{ loading || isSubmitting ? '登録中...' : '新規登録' }}
      </button>

      <p class="link">
        <NuxtLink to="/login">ログインはこちら</NuxtLink>
      </p>
    </form>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'default', ssr: false })

import { ref } from 'vue'
import { useForm } from 'vee-validate'
import * as yup from 'yup'
import { getAuth, updateProfile } from 'firebase/auth'

const { register } = useAuth()

// ✅ submit後にだけバリデーション表示したい（入力中は表示しない）
const showErrors = ref(false)

const schema = yup.object({
  username: yup
    .string()
    .required('ユーザーネームを入力してください。')
    .max(20, 'ユーザーネームは20文字以内で入力してください。'),
  email: yup
    .string()
    .required('メールアドレスを入力してください。')
    .email('有効なメールアドレス形式で入力してください。'),
  password: yup
    .string()
    .required('パスワードを入力してください。')
    .min(6, 'パスワードは6文字以上で入力してください。'),
})

const { errors, handleSubmit, defineField, isSubmitting } = useForm({
  validationSchema: schema,
  validateOnBlur: false,
  validateOnChange: false,
  validateOnInput: false,
  validateOnModelUpdate: false,
})

const [username] = defineField('username')
const [email] = defineField('email')
const [password] = defineField('password')

const loading = ref(false)
const authErrorMessage = ref('')

const sleep = (ms: number) => new Promise((r) => setTimeout(r, ms))

/** Firebaseのエラーコードをユーザー向け文言に変換 */
const toUserMessage = (code?: string) => {
  switch (code) {
    case 'auth/email-already-in-use':
      return 'このメールアドレスは既に登録されています。'
    case 'auth/invalid-email':
      return '有効なメールアドレス形式で入力してください。'
    case 'auth/weak-password':
      return 'パスワードは6文字以上で入力してください。'
    default:
      return '新規登録に失敗しました。入力内容をご確認ください。'
  }
}

const onSubmit = async () => {
  showErrors.value = true
  authErrorMessage.value = ''
  return submit()
}

const submit = handleSubmit(async (values) => {
  loading.value = true

  try {
    // 1) Firebase Auth 登録
    await register(values.email, values.password)

    // 2) currentUserが入るまで少し待つ（環境によって遅延するため）
    const auth = getAuth()
    for (let i = 0; i < 10; i++) {
      if (auth.currentUser) break
      await sleep(100)
    }

    // 3) displayName を保存（Firebase側のユーザー名）
    if (auth.currentUser) {
      await updateProfile(auth.currentUser, { displayName: values.username })
      await auth.currentUser.reload()
    }

    if (process.client) {
      localStorage.setItem('sns_user_name', values.username)
    }

    await navigateTo('/')
  } catch (e: any) {
    console.error('[Register Error]', e)
    authErrorMessage.value = toUserMessage(e?.code)
  } finally {
    loading.value = false
  }
})
</script>
