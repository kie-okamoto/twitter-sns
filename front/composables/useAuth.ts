import {
  getAuth,
  onAuthStateChanged,
  signOut,
  signInWithEmailAndPassword,
  createUserWithEmailAndPassword,
  type User,
} from 'firebase/auth'

export const useAuth = () => {
  // Firebase Auth インスタンス取得
  const auth = getAuth()

  // Nuxt のグローバル状態としてユーザーを保持
  const user = useState<User | null>('auth-user', () => auth.currentUser)

  // クライアントでログイン状態をリアルタイムに監視
  if (process.client) {
    onAuthStateChanged(auth, (u) => (user.value = u))
  }

  // 新規登録
  const register = async (email: string, password: string) => {
    try {
      await createUserWithEmailAndPassword(auth, email, password)
      console.log('[Auth] register success:', email)
    } catch (err: any) {
      console.error('[Auth] register failed:', err.code)
      alert('登録に失敗しました: ' + err.message)
    }
  }

  // ログイン
  const login = async (email: string, password: string) => {
    try {
      await signInWithEmailAndPassword(auth, email, password)
      console.log('[Auth] login success:', email)
    } catch (err: any) {
      console.error('[Auth] login failed:', err.code)
      alert('ログインに失敗しました: ' + err.message)
    }
  }

  // ログアウト
  const logout = async () => {
    try {
      await signOut(auth)
      user.value = null
      console.log('[Auth] logout success')
      await navigateTo('/login')
    } catch (err: any) {
      console.error('[Auth] logout failed:', err.code)
      alert('ログアウトに失敗しました: ' + err.message)
    }
  }

  // 認証が確定するまで一度待つ（初回ロード時の揺れ防止）
  const requireAuth = async () => {
    if (!process.client) return
    await new Promise<void>((resolve) => {
      const stop = onAuthStateChanged(auth, (u) => {
        user.value = u
        stop()
        resolve()
      })
    })
    if (!auth.currentUser) {
      console.warn('[Auth] user not logged in, redirecting...')
      await navigateTo('/login')
    }
  }

  return { user, register, login, logout, requireAuth }
}
