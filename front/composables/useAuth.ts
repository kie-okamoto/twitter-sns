// /home/y1109/twitter-sns/front/composables/useAuth.ts
import {
  getAuth,
  onAuthStateChanged,
  signOut,
  signInWithEmailAndPassword,
  createUserWithEmailAndPassword,
  type User,
} from 'firebase/auth'

export const useAuth = () => {

  const nuxtApp = useNuxtApp()
  const auth = (nuxtApp.$auth as ReturnType<typeof getAuth> | undefined) ?? getAuth()

  const user = useState<User | null>('auth-user', () => auth.currentUser)

  const listenerSet = useState<boolean>('auth-listener-set', () => false)
  if (process.client && !listenerSet.value) {
    listenerSet.value = true
    onAuthStateChanged(auth, (u) => (user.value = u))
  }

  // ✅ 新規登録
  const register = async (email: string, password: string) => {
    try {
      return await createUserWithEmailAndPassword(auth, email, password)
    } catch (err: any) {
      console.error('[Auth] register failed:', err?.code, err?.message)
      throw err
    }
  }

  const login = async (email: string, password: string) => {
    try {
      await signInWithEmailAndPassword(auth, email, password)
    } catch (err: any) {
      console.error('[Auth] login failed:', err?.code, err?.message)
      throw err
    }
  }

  const logout = async () => {
    try {
      await signOut(auth)
      user.value = null
      await navigateTo('/login')
    } catch (err: any) {
      console.error('[Auth] logout failed:', err?.code, err?.message)
      throw err
    }
  }

  const requireAuth = async () => {
    if (!process.client) return

    // ✅ 初回ロードの揺れ防止（状態確定まで待つ）
    await new Promise<void>((resolve) => {
      const stop = onAuthStateChanged(auth, (u) => {
        user.value = u
        stop()
        resolve()
      })
    })

    if (!auth.currentUser) {
      await navigateTo('/login')
    }
  }

  return { user, register, login, logout, requireAuth }
}
