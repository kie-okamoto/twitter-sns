<!-- /home/y1109/twitter-sns/front/pages/posts/[id].vue -->
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
    </aside>

    <main class="main">
      <div class="homeTitle">コメント</div>

      <p v-if="!post" class="empty">
        {{ loading ? '読み込み中...' : '投稿が見つかりません' }}
      </p>

      <template v-else>
        <section class="messageBox">
          <div class="post__head">
            <strong class="post__user">{{ post.user_name ?? 'user' }}</strong>

            <div class="icon" title="いいね数">
              <img src="/icons/heart.png" alt="like" />
              <span>{{ post.likes_count ?? 0 }}</span>
            </div>

            <button v-if="canDelete(post)" type="button" class="icon" title="削除" @click="removePost(post)">
              <img src="/icons/cross.png" alt="delete" />
            </button>
          </div>

          <div class="post__body">{{ post.content }}</div>
        </section>

        <section class="commentBox">
          <div class="commentBox__title">コメント</div>

          <ul class="post__comments">
            <li v-for="c in post.comments ?? []" :key="c.id" class="comment">
              <div class="comment__user">{{ c.user_name ?? 'user' }}</div>
              <div class="comment__body">{{ c.content }}</div>
            </li>
          </ul>

          <form class="commentForm" novalidate @submit.prevent="onSubmitComment">
            <input
              v-model="comment"
              class="commentForm__input"
              type="text"
              placeholder="コメントを入力"
              :aria-invalid="commentSubmitCount > 0 && !!commentErrors.content"
              @keydown.enter.prevent="onSubmitComment"
            />

            <button type="submit" class="btn commentForm__btn" :disabled="sending || commentIsSubmitting">
              コメント
            </button>
          </form>

          <p v-if="commentSubmitCount > 0 && commentErrors.content" class="feed-error">
            {{ commentErrors.content }}
          </p>
        </section>
      </template>
    </main>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'feed', ssr: false })

import { getAuth, onAuthStateChanged } from 'firebase/auth'
import { useForm } from 'vee-validate'
import * as yup from 'yup'

type Comment = { id: number; content: string; user_name?: string | null }
type Post = {
  id: number
  content: string
  user_id?: string | null
  user_name?: string | null
  likes_count?: number
  comments_count?: number
  comments?: Comment[]
}

const route = useRoute()
const postId = computed(() => Number(route.params.id))

const { requireAuth, logout } = useAuth()
const {
  public: { apiBase },
} = useRuntimeConfig()

const loading = ref(true)
const post = ref<Post | null>(null)
const sending = ref(false)

// ===== コメントフォーム =====
const commentSchema = yup.object({
  content: yup
    .string()
    .transform((v) => (typeof v === 'string' ? v.trim() : v))
    .required('コメント内容を入力してください。')
    .max(120, 'コメント内容は120文字以内で入力してください。'),
})

const {
  errors: commentErrors,
  handleSubmit: handleCommentSubmit,
  defineField: defineCommentField,
  isSubmitting: commentIsSubmitting,
  submitCount: commentSubmitCount,
  resetForm,
} = useForm({
  validationSchema: commentSchema,
  validateOnBlur: false,
  validateOnChange: false,
  validateOnInput: false,
  validateOnModelUpdate: false,
})

const [comment] = defineCommentField('content')

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

const loadPost = async () => {
  loading.value = true
  try {
    const res = await authedFetch<any>('/posts')
    const raw = Array.isArray(res) ? res : res?.data ?? []
    const found = raw.find((p: any) => Number(p.id) === postId.value)

    post.value = found
      ? {
          id: found.id,
          content: found.content,
          user_id: found.user_id,
          user_name: found.user_name,
          likes_count: found.likes_count ?? 0,
          comments_count: found.comments_count ?? 0,
          comments: Array.isArray(found.comments) ? found.comments : [],
        }
      : null
  } catch (e) {
    console.error('loadPost failed', e)
    post.value = null
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await requireAuth()

  const auth = getAuth()
  try {
    await auth.currentUser?.reload()
  } catch (_) {}

  await loadPost()
})

const onSubmitComment = handleCommentSubmit(async (values) => {
  if (sending.value) return
  sending.value = true

  try {
    await authedFetch(
      '/comments',
      {
        method: 'POST',
        body: {
          post_id: postId.value,
          content: values.content,
        },
      },
      { required: true }
    )

    resetForm({ values: { content: '' } })
    await loadPost()
  } catch (e: any) {
    console.error('POST /comments failed', e)
    alert(String(e?.message) === 'NO_TOKEN' ? '再ログインしてください。' : 'コメントの送信に失敗しました。')
  } finally {
    sending.value = false
  }
})

const canDelete = (p: Post) => {
  const uid = currentUid.value
  if (!uid) return false
  return p.user_id === uid
}

const removePost = async (p: Post) => {
  if (!confirm('削除しますか？')) return
  try {
    await authedFetch(`/posts/${p.id}`, { method: 'DELETE' }, { required: true })
    await navigateTo('/')
  } catch (e) {
    console.error('DELETE /posts failed', e)
    alert('削除に失敗しました。')
  }
}

const onLogout = async () => {
  await logout()
}
</script>
