
import { getAuth } from 'firebase/auth'

export const useApi = () => {
  const { public: { apiBase } } = useRuntimeConfig()

  const request = async <T>(path: string, opts: RequestInit = {}) => {
    const auth = getAuth()
    const token = await auth.currentUser?.getIdToken().catch(() => undefined)

    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      ...(opts.headers || {}),
    }
    if (token) headers['Authorization'] = `Bearer ${token}`

    const res = await fetch(`${apiBase}${path}`, { ...opts, headers })
    const text = await res.text().catch(() => '')

    if (!res.ok) {
      throw new Error(`[${res.status}] ${res.statusText} :: ${text}`)
    }
    return (text && res.headers.get('content-type')?.includes('json'))
      ? JSON.parse(text) as T
      : (null as T)
  }

  return {
    get: <T>(p: string) => request<T>(p),
    post: <T>(p: string, body?: any) =>
      request<T>(p, { method: 'POST', body: body ? JSON.stringify(body) : undefined }),
    del:  <T>(p: string) => request<T>(p, { method: 'DELETE' }),
  }
}
