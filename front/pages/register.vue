<!-- /home/y1109/twitter-sns/front/pages/register.vue -->
<template>
  <div class="auth-page">
    <form class="auth-form" @submit.prevent="onSubmit">
      <h2>新規登録</h2>

      <input
        v-model.trim="username"
        type="text"
        placeholder="ユーザーネーム"
        required
        maxlength="20"
        autocomplete="nickname"
      />

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
        placeholder="パスワード（6文字以上）"
        required
        minlength="6"
        autocomplete="new-password"
      />

      <p v-if="errorMessage" class="error">
        {{ errorMessage }}
      </p>

      <button type="submit" class="primary" :disabled="loading">
        {{ loading ? '登録中...' : '新規登録' }}
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'default', ssr: false })

import { getAuth, updateProfile } from 'firebase/auth'

const { register } = useAuth()

const username = ref('')
const email = ref('')
const password = ref('')

const loading = ref(false)
const errorMessage = ref('')

const sleep = (ms: number) => new Promise((r) => setTimeout(r, ms))

/** Firebaseのエラーコードをユーザー向け文言に変換 */
const toUserMessage = (code?: string) => {
  switch (code) {
    case 'auth/email-already-in-use':
      return 'このメールアドレスは既に登録されています。'
    case 'auth/invalid-email':
      return 'メールアドレスの形式が正しくありません。'
    case 'auth/weak-password':
      return 'パスワードが弱すぎます。6文字以上で設定してください。'
    case 'auth/missing-password':
      return 'パスワードを入力してください。'
    case 'auth/missing-email':
      return 'メールアドレスを入力してください。'
    default:
      return '新規登録に失敗しました。入力内容をご確認ください。'
  }
}

const onSubmit = async () => {
  errorMessage.value = ''

  const name = username.value.trim()
  const mail = email.value.trim()
  const pass = password.value

  // ===== フロント側バリデーション（要件） =====
  if (!name) {
    errorMessage.value = 'ユーザーネームを入力してください。'
    return
  }
  if (name.length > 20) {
    errorMessage.value = 'ユーザーネームは20文字以内で入力してください。'
    return
  }
  if (!mail) {
    errorMessage.value = 'メールアドレスを入力してください。'
    return
  }
  if (!pass) {
    errorMessage.value = 'パスワードを入力してください。'
    return
  }
  if (pass.length < 6) {
    errorMessage.value = 'パスワードは6文字以上で入力してください。'
    return
  }

  loading.value = true

  try {
    // デバッグログ（安全版：中身は出さない）
    console.log('username=', name)
    console.log('email=', mail)
    console.log('password length=', pass?.length)

    // 1) Firebase Auth 登録
    await register(mail, pass)

    // 2) currentUserが入るまで少し待つ（環境によって遅延するため）
    const auth = getAuth()
    for (let i = 0; i < 10; i++) {
      if (auth.currentUser) break
      await sleep(100)
    }

    // 3) displayName を保存
    if (auth.currentUser) {
      await updateProfile(auth.currentUser, { displayName: name })
      await auth.currentUser.reload()
    }

    // 4) 保険：localStorage にも保存（表示名が取れない場合のフォールバック用）
    if (process.client) {
      localStorage.setItem('sns_user_name', name)
    }

    await navigateTo('/')
  } catch (e: any) {
    // ✅ 開発者向け：コンソールには詳細
    console.error('[Register Error]', e)

    // ✅ ユーザー向け：短く分かりやすく
    errorMessage.value = toUserMessage(e?.code)
  } finally {
    loading.value = false
  }
}
</script>
