<!-- /pages/register.vue -->
<template>
  <div class="auth-page">
    <form class="auth-form" @submit.prevent="onSubmit">
      <h2>新規登録</h2>

      <input
        v-model="username"
        type="text"
        placeholder="ユーザー ネーム"
        required
        autocomplete="nickname"
      />
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
        autocomplete="new-password"
      />

      <button type="submit" class="primary">新規登録</button>
    </form>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'default', ssr: false })

const { register } = useAuth()
const username = ref('')
const email = ref('')
const password = ref('')

// ユーザー名を Firebase の displayName に反映（任意）
import { getAuth, updateProfile } from 'firebase/auth'
const auth = getAuth()

const onSubmit = async () => {
  await register(email.value, password.value)
  if (auth.currentUser && username.value.trim()) {
    try {
      await updateProfile(auth.currentUser, { displayName: username.value.trim() })
    } catch (e) {
      console.warn('[Auth] updateProfile failed:', e)
    }
  }
  await navigateTo('/')
}
</script>

<style scoped>
/* 画面全体（添付デザインのダーク背景） */
.auth-page{
  min-height:calc(100vh - 64px); /* ヘッダー分を除外 */
  display:flex;
  justify-content:center;
  align-items:center;
  background:#0f1824;
}

/* 白いカードフォーム */
.auth-form{
  width:520px;
  background:#fff;
  color:#000;
  padding:28px 36px;
  border-radius:12px;
  box-shadow:0 6px 18px rgba(0,0,0,.22);
  display:flex;
  flex-direction:column;
  gap:14px;
}

.auth-form h2{
  text-align:center;
  margin:0 0 8px;
}

/* 入力フィールド */
.auth-form input{
  padding:12px 14px;
  border:1px solid #8a8f98;
  border-radius:10px;
  outline:none;
}

/* 送信ボタン（紫グラデ・丸ボタン） */
.primary{
  align-self:center;
  margin-top:6px;
  padding:10px 22px;
  border:0;
  border-radius:22px;
  background:linear-gradient(90deg,#6a11cb 0%,#2575fc 100%);
  color:#fff;
  font-weight:700;
  cursor:pointer;
}
.primary:hover{ opacity:.92; }
</style>
