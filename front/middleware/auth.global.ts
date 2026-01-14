import { getAuth } from 'firebase/auth'

export default defineNuxtRouteMiddleware((to) => {
  // ログイン不要ページ
  const publicPaths = new Set(['/login', '/register'])
  if (publicPaths.has(to.path)) return

  const auth = getAuth()
  if (!auth.currentUser) {
    return navigateTo('/login')
  }
})
