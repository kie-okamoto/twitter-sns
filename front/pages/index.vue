<!-- /home/y1109/twitter-sns/front/pages/index.vue -->
<template>
  <div class="feed">
    <!-- ========== 左サイドバー ========== -->
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

      <!-- ✅ 左メニュー：シェア（投稿作成）は残す -->
      <div class="share">
        <p class="share__title">シェア</p>
        <textarea v-model="newPost" class="share__input" placeholder="" />
        <button
          type="button"
          class="btn"
          :disabled="posting || !newPost.trim()"
          @click="createPost"
        >
          シェアする
        </button>
      </div>
    </aside>

    <!-- ========== メイン ========== -->
    <main class="main">
      <div class="homeTitle">ホーム</div>

      <section v-for="p in posts" :key="p.id" class="post">
        <div class="post__head">
          <strong class="post__user">
            {{ displayPostUserName(p) }}
          </strong>

          <!-- ✅ いいね -->
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

          <!-- ✅ 削除（自分のみ） -->
          <button
            v-if="canDelete(p)"
            type="button"
            class="icon"
            title="削除"
            @click="removePost(p)"
          >
            <img src="/icons/cross.png" alt="delete" />
          </button>

          <!-- ✅ コメント画面へ遷移（detail） -->
          <NuxtLink
            class="icon"
            :to="`/posts/${p.id}`"
            title="コメントを見る"
            aria-label="comments"
          >
            <img src="/icons/detail.png" alt="detail" />
          </NuxtLink>
        </div>

        <!-- ✅ index では投稿本文のみ表示（コメントは出さない） -->
        <div class="post__body">{{ p.content }}</div>
      </section>

      <p v-if="!loading && posts.length === 0" class="empty">
        投稿はまだありません
      </p>
    </main>
  </div>
</template>

<script setup lang="ts">
/** ✅ indexは feed レイアウト */
definePageMeta({ layout: 'feed', ssr: false })

import { getAuth } from 'firebase/auth'

type User = { id: number; name: string }

type Post = {
  id: number
  content: string
  user?: User
  user_name?: string
  user_id?: string
  likes_count?: number
  is_liked?: boolean
  // ✅ indexではコメントを表示しないので保持しない
}

const { requireAuth, logout } = useAuth()
const {
  public: { apiBase },
} = useRuntimeConfig()

const loading = ref(true)
const posting = ref(false)
const posts = ref<Post[]>([])
const newPost = ref('')

/** ✅ 投稿作成用の表示名（ここは currentUserName を使ってOK） */
const currentUserName = computed(() => {
  const auth = getAuth()
  return (
    auth.currentUser?.displayName ||
    (process.client ? localStorage.getItem('sns_user_name') : null) ||
    'user'
  )
})

const currentUid = computed(() => {
  const auth = getAuth()
  return auth.currentUser?.uid ?? null
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
 * ✅ 投稿者名表示（重要）
 * - currentUserName を fallback にしない（他人投稿が自分の名前になる事故防止）
 * - user_name が無い投稿は 'user' と表示
 */
const displayPostUserName = (p: Post) => {
  return p.user_name ?? p.user?.name ?? 'user'
}

onMounted(async () => {
  await requireAuth()

  const auth = getAuth()
  try {
    await auth.currentUser?.reload()
  } catch (_) {}

  if (process.client && auth.currentUser?.displayName) {
    localStorage.setItem('sns_user_name', auth.currentUser.displayName)
  }

  await loadPosts()
})

const loadPosts = async () => {
  loading.value = true
  try {
    const res = await authedFetch<any>('/posts')
    const raw = Array.isArray(res) ? res : (res?.data ?? [])

    // ✅ indexではコメントを表示しないので、comments は拾わない
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

const createPost = async () => {
  const content = newPost.value.trim()
  if (!content) return

  posting.value = true
  try {
    await authedFetch('/posts', {
      method: 'POST',
      body: { content, user_name: currentUserName.value },
    })
    newPost.value = ''
    await loadPosts()
  } catch (e) {
    console.error('POST /posts failed', e)
    alert('投稿に失敗しました。')
  } finally {
    posting.value = false
  }
}

const toggleLike = async (p: Post) => {
  try {
    if (p.is_liked) {
      await authedFetch('/likes', { method: 'DELETE', body: { post_id: p.id } })
      p.is_liked = false
      p.likes_count = Math.max(0, (p.likes_count ?? 0) - 1)
    } else {
      await authedFetch('/likes', { method: 'POST', body: { post_id: p.id } })
      p.is_liked = true
      p.likes_count = (p.likes_count ?? 0) + 1
    }
  } catch (e) {
    console.error('toggle like failed', e)
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
    await authedFetch(`/posts/${p.id}`, { method: 'DELETE' })
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
