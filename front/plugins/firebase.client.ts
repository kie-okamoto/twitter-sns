// /plugins/firebase.client.ts
import { initializeApp, getApps } from 'firebase/app'
import { getAuth } from 'firebase/auth'

export default defineNuxtPlugin(() => {
  const rc = useRuntimeConfig()
  const pub = rc.public as Record<string, string | undefined>
  const env = import.meta.env as Record<string, string | undefined>
  const pick = (k: string) =>
    (pub[k] ?? env[`NUXT_PUBLIC_${k}`] ?? '').toString().trim()

  const firebaseConfig = {
    apiKey: pick('FIREBASE_API_KEY'),
    authDomain: pick('FIREBASE_AUTH_DOMAIN'),
    projectId: pick('FIREBASE_PROJECT_ID'),
    appId: pick('FIREBASE_APP_ID'),
    storageBucket: pick('FIREBASE_STORAGE_BUCKET'),
    messagingSenderId: pick('FIREBASE_MESSAGING_SENDER_ID'),
  }

  // ここで“例外は投げない”。ログだけ出す
  const missing = Object.entries(firebaseConfig)
    .filter(([, v]) => !v)
    .map(([k]) => k)
  if (missing.length) {
    console.error('[Firebase] Missing env keys:', missing)
    // プラグインは動かさず終了（アプリは落とさない）
    return
  }

  const apiKeyOk = /^AIza[0-9A-Za-z_\-]{35}$/.test(firebaseConfig.apiKey!)
  if (!apiKeyOk) {
    console.error('[Firebase] apiKey format invalid:', firebaseConfig.apiKey)
    return
  }

  const app = getApps().length ? getApps()[0] : initializeApp(firebaseConfig)
  const auth = getAuth(app)
  return { provide: { auth } }
})
