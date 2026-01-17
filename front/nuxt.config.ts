// /home/y1109/twitter-sns/front/nuxt.config.ts
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',

  devtools: { enabled: true },

  css: ['@/assets/css/auth.css', '@/assets/css/feed.css'],


  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000/api',
      FIREBASE_API_KEY: process.env.NUXT_PUBLIC_FIREBASE_API_KEY,
      FIREBASE_AUTH_DOMAIN: process.env.NUXT_PUBLIC_FIREBASE_AUTH_DOMAIN,
      FIREBASE_PROJECT_ID: process.env.NUXT_PUBLIC_FIREBASE_PROJECT_ID,
      FIREBASE_APP_ID: process.env.NUXT_PUBLIC_FIREBASE_APP_ID,
      FIREBASE_STORAGE_BUCKET: process.env.NUXT_PUBLIC_FIREBASE_STORAGE_BUCKET,
      FIREBASE_MESSAGING_SENDER_ID:
        process.env.NUXT_PUBLIC_FIREBASE_MESSAGING_SENDER_ID,
    },
  },

  app: {
  head: {
    title: 'Twitter風SNSアプリ',
    meta: [
      { name: 'description', content: 'Firebase認証 × Laravel API のSNSアプリ' },
      { name: 'viewport', content: 'width=device-width, initial-scale=1.0' },
    ],
    link: [{ rel: 'icon', href: '/favicon.ico' }],
  },
},


  build: {
    transpile: ['firebase'],
  },

  routeRules: {
    '/': { ssr: false },
    '/login': { ssr: false },
    '/register': { ssr: false },
  },

  typescript: {
    strict: true,
    typeCheck: false,
  },
})
