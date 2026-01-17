<!-- /home/y1109/twitter-sns/front/pages/posts/[id].vue -->
<template>
  <div class="feed">
    <!-- ========== 左サイドバー（index と同じ） ========== -->
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

      <!-- ✅ コメント画面では投稿欄は不要（仕様に合わせて削除） -->
    </aside>

    <!-- ========== メイン ========== -->
    <main class="main">
      <div class="homeTitle">コメント</div>

      <!-- 投稿1件 -->
      <section v-if="post" class="post">
        <div class="post__head">
          <strong class="post__user">
            {{ post.user_name ?? 'user' }}
          </strong>

          <!-- いいね数（表示のみ） -->
          <div class="icon" title="いいね数">
            <img src="/icons/heart.png" alt="like" />
            <span>{{ post.likes_count ?? 0 }}</span>
          </div>

          <!-- 戻る（ホームへ） -->
          <NuxtLink to="/" class="icon" title="ホームに戻る" aria-label="back">
            <img src="/icons/detail.png" alt="back" />
          </NuxtLink>
        </div>

        <div class="post__body">{{ post.content }}</div>

        <!-- コメント一覧 -->
        <ul class="post__comments">
          <li v-for="c in post.comments ?? []" :key="c.id" class="comment">
            <div class="comment__user">{{ c.user_name ?? 'user' }}</div>
            <div class="comment__body">{{ c.content }}</div>
          </li>
        </ul>

        <!-- ✅ コメントフォーム（この画面だけ） -->
        <div class="commentForm">
          <input
            v-model="comment"
            class="commentForm__input"
            type="text"
            placeholder="コメントを入力"
            @keydown.enter.prevent="submitComment"
          />

          <button
            type="button"
            class="btn -right"
            :disabled="sending || !comment.trim()"
            @click="submitComment"
          >
            コメント
          </button>
        </div>
      </section>

      <p v-else class="empty">
        {{ loading ? '読み込み中...' : '投稿が見つかりません' }}
      </p>
    </main>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'feed', ssr: false })

import { getAuth } from 'firebase/auth'

type Comment = {
  id: number
  content: string
  user_name?: string
}

type Post = {
  id: number
  content: string
  user_name?: string
  likes_count?: number
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

const comment = ref('')
const sending = ref(false)

/** ✅ ログイン中ユーザー名（コメント投稿用） */
const currentUserName = computed(() => {
  const auth = getAuth()
  return (
    auth.currentUser?.displayName ||
    (process.client ? localStorage.getItem('sns_user_name') : null) ||
    'user'
  )
})

const authedFetch = async <T = any>(path: string, opts: any = {}): Promise<T> => {
  const auth = getAuth()
  const token = await auth.currentUser?.getIdToken().catch(() => undefined)

  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    ...(opts.headers || {}),
  }
  if (token) headers['Authorization'] = `Bearer ${token}`

  return await $fetch<T>(`${apiBase}${path}`, { ...opts, headers })
}

/**
 * ✅ まずは API 追加なしで動かすため、GET /posts から該当投稿を探す
 * （将来的には GET /posts/{id} を作るのが理想）
 */
const loadPost = async () => {
  loading.value = true
  try {
    const res = await authedFetch<any>('/posts')
    const raw = Array.isArray(res) ? res : (res?.data ?? [])
    const found = raw.find((p: any) => Number(p.id) === postId.value)

    post.value = found
      ? {
          id: found.id,
          content: found.content,
          user_name: found.user_name,
          likes_count: found.likes_count ?? 0,
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

  // displayName を localStorage に保存している運用なら、ここで同期しておくと安定
  const auth = getAuth()
  try {
    await auth.currentUser?.reload()
  } catch (_) {}

  if (process.client && auth.currentUser?.displayName) {
    localStorage.setItem('sns_user_name', auth.currentUser.displayName)
  }

  await loadPost()
})

const submitComment = async () => {
  const content = comment.value.trim()
  if (!content) return
  if (sending.value) return
  sending.value = true

  try {
    await authedFetch('/comments', {
      method: 'POST',
      body: {
        post_id: postId.value,
        content,
        user_name: currentUserName.value, // ✅ これ重要（表示名が揃う）
      },
    })

    comment.value = ''
    await loadPost()
  } catch (e) {
    console.error('POST /comments failed', e)
    alert('コメントの送信に失敗しました。')
  } finally {
    sending.value = false
  }
}

const onLogout = async () => {
  await logout()
}
</script>
