<!-- /home/y1109/twitter-sns/front/pages/index.vue -->
<template>
  <div class="feed">
    <aside class="aside">
      <div class="brand">
        <img src="/images/logo.png" alt="SHARE" />
      </div>

      <nav class="menu">
        <NuxtLink to="/" class="menu__item">
          <img src="/icons/home.png" alt="home" />
          <span>ホーム</span>
        </NuxtLink>

        <button type="button" class="menu__item -button" @click="onLogout">
          <img src="/icons/logout.png" alt="logout" />
          <span>ログアウト</span>
        </button>
      </nav>

      <div class="share">
        <p class="share__title">シェア</p>

        <form class="share__form" novalidate @submit.prevent="onCreatePost">
          <textarea v-model="newPost" class="share__input" placeholder="" />

          <p v-if="postSubmitCount > 0 && postErrors.content" class="error">
            {{ postErrors.content }}
          </p>

          <button type="submit" class="btn share__btn" :disabled="posting || postIsSubmitting">
            シェアする
          </button>
        </form>
      </div>
    </aside>

    <main class="main">
      <div class="homeTitle">ホーム</div>

      <section v-for="p in posts" :key="p.id" class="post">
        <div class="post__head">
          <strong class="post__user">{{ displayPostUserName(p) }}</strong>

          <button
            type="button"
            class="icon likeButton"
            :class="{ 'is-liked': !!p.is_liked }"
            @click="toggleLike(p)"
            aria-label="like"
            title="いいね"
          >
            <img class="likeButton__icon" src="/icons/heart.png" alt="like" />
            <span class="likeButton__count">{{ p.likes_count ?? 0 }}</span>
          </button>

          <button
            v-if="canDelete(p)"
            type="button"
            class="icon"
            title="削除"
            @click="removePost(p)"
          >
            <img src="/icons/cross.png" alt="delete" />
          </button>

          <NuxtLink class="icon" :to="`/posts/${p.id}`" title="コメントを見る" aria-label="comments">
            <img src="/icons/detail.png" alt="detail" />
          </NuxtLink>
        </div>

        <div class="post__body">{{ p.content }}</div>
      </section>

      <p v-if="!loading && posts.length === 0" class="empty">投稿はまだありません</p>
    </main>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'feed', ssr: false })

import { getAuth, onAuthStateChanged } from 'firebase/auth'
import { useForm } from 'vee-validate'
import * as yup from 'yup'

type Post = {
  id: number
  content: string
  user_name?: string | null
  user_id?: string | null
  likes_count?: number
  is_liked?: boolean
}

const { requireAuth, logout } = useAuth()
const {
  public: { apiBase },
} = useRuntimeConfig()

const loading = ref(true)
const posting = ref(false)
const posts = ref<Post[]>([])

// ===== 投稿フォーム =====
const postSchema = yup.object({
  content: yup
    .string()
    .transform((v) => (typeof v === 'string' ? v.trim() : v))
    .required('投稿内容を入力してください。')
    .max(120, '投稿内容は120文字以内で入力してください。'),
})

const {
  errors: postErrors,
  handleSubmit: handlePostSubmit,
  defineField: definePostField,
  isSubmitting: postIsSubmitting,
  submitCount: postSubmitCount,
  resetForm,
} = useForm({
  validationSchema: postSchema,
  validateOnBlur: false,
  validateOnChange: false,
  validateOnInput: false,
  validateOnModelUpdate: false,
})

const [newPost] = definePostField('content')

// ===== token取得 =====
const getIdTokenOrNull = async (): Promise<string | null> => {
  const auth = getAuth()
  const user =
    auth.currentUser ??
    (await new Promise<ReturnType<typeof auth.currentUser>>((resolve) => {
      const unsub = onAuthStateChanged(auth, (u) => {
        unsub()
        resolve(u)
      })
    }))
  if (!user) return null
  try {
    return await user.getIdToken(true)
  } catch {
    return null
  }
}

const authedFetch = async <T = any>(
  path: string,
  opts: any = {},
  cfg: { required?: boolean } = {}
): Promise<T> => {
  const token = await getIdTokenOrNull()
  if (cfg.required && !token) throw new Error('NO_TOKEN')

  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    ...(opts.headers || {}),
  }
  if (token) headers['Authorization'] = `Bearer ${token}`

  return await $fetch<T>(`${apiBase}${path}`, { ...opts, headers })
}

const currentUid = computed(() => getAuth().currentUser?.uid ?? null)

const displayPostUserName = (p: Post) => p.user_name ?? 'user'

onMounted(async () => {
  await requireAuth()

  const auth = getAuth()
  try {
    await auth.currentUser?.reload()
  } catch (_) {}

  await loadPosts()
})

const loadPosts = async () => {
  loading.value = true
  try {
    const res = await authedFetch<any>('/posts')
    const raw = Array.isArray(res) ? res : res?.data ?? []
    posts.value = raw.map((p: any) => ({
      id: p.id,
      content: p.content,
      user_id: p.user_id,
      user_name: p.user_name,
      likes_count: p.likes_count ?? 0,
      is_liked: p.is_liked ?? false,
    }))
  } catch (e) {
    console.error('GET /posts failed', e)
  } finally {
    loading.value = false
  }
}

const onCreatePost = handlePostSubmit(async (values) => {
  if (posting.value) return
  posting.value = true
  try {
    await authedFetch(
      '/posts',
      { method: 'POST', body: { content: values.content } },
      { required: true }
    )
    resetForm({ values: { content: '' } })
    await loadPosts()
  } catch (e: any) {
    console.error('POST /posts failed', e)
    alert(String(e?.message) === 'NO_TOKEN' ? '再ログインしてください。' : '投稿に失敗しました。')
  } finally {
    posting.value = false
  }
})

const toggleLike = async (p: Post) => {
  try {
    if (p.is_liked) {
      await authedFetch(`/likes/${p.id}`, { method: 'DELETE' }, { required: true })
      p.is_liked = false
      p.likes_count = Math.max(0, (p.likes_count ?? 0) - 1)
    } else {
      await authedFetch('/likes', { method: 'POST', body: { post_id: p.id } }, { required: true })
      p.is_liked = true
      p.likes_count = (p.likes_count ?? 0) + 1
    }
  } catch (e) {
    console.error('toggle like failed', e)
    alert('いいねに失敗しました。再ログインしてください。')
  }
}

const canDelete = (p: Post) => {
  const uid = currentUid.value
  if (!uid) return false
  return p.user_id === uid
}

const removePost = async (p: Post) => {
  if (!confirm('削除しますか？')) return
  try {
    await authedFetch(`/posts/${p.id}`, { method: 'DELETE' }, { required: true })
    posts.value = posts.value.filter((x) => x.id !== p.id)
  } catch (e) {
    console.error('DELETE /posts failed', e)
    alert('削除に失敗しました。')
  }
}

const onLogout = async () => {
  await logout()
}
</script>
