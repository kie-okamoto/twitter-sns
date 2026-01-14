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
        <button class="menu__item -button" @click="onLogout">
          <img src="/icons/logout.png" alt="logout" />
          <span>ログアウト</span>
        </button>
      </nav>

      <div class="share">
        <p class="share__title">シェア</p>
        <textarea
          v-model="newPost"
          class="share__input"
          placeholder="いまどうしてる？"
        />
        <button class="btn" :disabled="posting" @click="createPost">
          シェアする
        </button>
      </div>
    </aside>

    <!-- ========== メイン ========== -->
    <main class="main">
      <header class="main__header">コメント</header>

      <section v-for="p in posts" :key="p.id" class="post">
        <!-- 投稿の1行目（タイトル行） -->
        <div class="post__head">
          <strong class="post__user">{{ p.user?.name ?? 'user' }}</strong>

          <button class="icon" @click="toggleLike(p)">
            <img src="/icons/heart.png" alt="like" />
            <span>{{ p.likes_count ?? 0 }}</span>
          </button>

          <button
            v-if="canDelete(p)"
            class="icon"
            title="削除"
            @click="removePost(p)"
          >
            <img src="/icons/cross.png" alt="delete" />
          </button>

          <button class="icon" title="シェア">
            <img src="/icons/feather.png" alt="share" />
          </button>
        </div>

        <!-- 本文 -->
        <div class="post__body">{{ p.content }}</div>

        <!-- コメント一覧テーブル風ヘッダ -->
        <div class="post__commentsHead">コメント</div>

        <!-- コメント一覧 -->
        <ul class="post__comments">
          <li v-for="c in p.comments ?? []" :key="c.id" class="comment">
            <div class="comment__user">{{ c.user?.name ?? c.user_name ?? 'user' }}</div>
            <div class="comment__body">{{ c.content }}</div>
          </li>
        </ul>

        <!-- コメント入力 -->
        <div class="commentForm">
          <input
            v-model="commentDraft[p.id]"
            class="commentForm__input"
            type="text"
            placeholder="コメントを入力"
          />
            <button class="btn -right" @click="addComment(p)">コメント</button>
        </div>
      </section>

      <!-- 何も無いとき -->
      <p v-if="!loading && posts.length === 0" class="empty">投稿はまだありません</p>
    </main>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'default', ssr: false })

import { getAuth } from 'firebase/auth'

type User = { id: number; name: string }
type Comment = { id: number; content: string; user?: User; user_name?: string }
type Post = {
  id: number
  content: string
  user?: User
  likes_count?: number
  is_liked?: boolean
  comments?: Comment[]
}

const { requireAuth, user, logout } = useAuth()
const { public: { apiBase } } = useRuntimeConfig()

const loading = ref(true)
const posting = ref(false)
const posts = ref<Post[]>([])
const newPost = ref('')
const commentDraft = reactive<Record<number, string>>({})

// Firebase ID トークンを付けた fetch ラッパー
const authedFetch = async <T = any>(
  path: string,
  opts: any = {}
): Promise<T> => {
  const auth = getAuth()
  const token = await auth.currentUser?.getIdToken().catch(() => undefined)

  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    ...(opts.headers || {})
  }
  if (token) headers['Authorization'] = `Bearer ${token}`

  return await $fetch<T>(`${apiBase}${path}`, { ...opts, headers })
}

// 初期ロード（認証→一覧）
onMounted(async () => {
  await requireAuth()
  await loadPosts()
})

/** 一覧取得 */
const loadPosts = async () => {
  loading.value = true
  try {
    const res = await authedFetch<any>('/posts')
    posts.value = Array.isArray(res) ? res : (res.data ?? [])
  } catch (e) {
    console.error('GET /posts failed', e)
  } finally {
    loading.value = false
  }
}

/** 投稿作成 */
const createPost = async () => {
  const content = newPost.value.trim()
  if (!content) return
  posting.value = true
  try {
    await authedFetch('/posts', {
      method: 'POST',
      body: { content },
    })
    newPost.value = ''
    await loadPosts()
  } catch (e: any) {
    console.error('POST /posts failed', {
      status: e?.response?.status,
      statusText: e?.response?.statusText,
      data: e?.response?._data
    })
    alert(
      `投稿に失敗しました\n` +
      `status=${e?.response?.status}\n` +
      `${JSON.stringify(e?.response?._data)}`
    )
  } finally {
    posting.value = false
  }
}

/** いいね（トグル） */
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

/** 削除権限（必要なら調整） */
const canDelete = (p: Post) => {
  return true
}

/** 投稿削除 */
const removePost = async (p: Post) => {
  if (!confirm('削除しますか？')) return
  try {
    await authedFetch(`/posts/${p.id}`, { method: 'DELETE' })
    posts.value = posts.value.filter(x => x.id !== p.id)
  } catch (e) {
    console.error('DELETE /posts failed', e)
  }
}

/** コメント追加 */
const addComment = async (p: Post) => {
  const content = (commentDraft[p.id] ?? '').trim()
  if (!content) return

  try {
    await authedFetch('/comments', {
      method: 'POST',
      body: {
        post_id: p.id,   // ★ どの投稿へのコメントか
        content,         // ★ コメント本文
      },
    })

    commentDraft[p.id] = ''
    await loadPosts()
  } catch (e: any) {
    console.error('POST /comments failed', {
      status: e?.response?.status,
      data: e?.response?._data,
    })
    alert(
      `コメント投稿に失敗しました\n` +
      `status=${e?.response?.status}\n` +
      `${JSON.stringify(e?.response?._data)}`
    )
  }
}


/** ログアウト */
const onLogout = async () => {
  await logout()
}
</script>

<style scoped>
/* ===== レイアウト ===== */
.feed{
  display:grid;
  grid-template-columns: 260px 1fr;
  min-height: calc(100vh - 64px); /* ヘッダ分差し引き */
  background:#0f1824;
  color:#fff;
}

/* ===== サイド ===== */
.aside{
  border-right: 2px solid rgba(255,255,255,.06);
  padding: 18px 16px 24px;
  display:flex;
  flex-direction:column;
  gap: 18px;
}
.brand img{ width: 130px; height:auto; display:block; }

.menu{ display:flex; flex-direction:column; gap:10px; }
.menu__item{
  display:flex; align-items:center; gap:10px;
  color:#fff; text-decoration:none;
  padding:10px 8px; border-radius:10px;
}
.menu__item img{ width:22px; height:22px; }
.menu__item:hover{ background:rgba(255,255,255,.06); }
.menu__item.-button{ background:none; border:none; cursor:pointer; text-align:left; }

.share{ margin-top:6px; }
.share__title{ margin:0 0 8px; }
.share__input{
  width:100%; height:140px; resize:none;
  background:#162231; color:#fff;
  border:1px solid #8a8f98; border-radius:10px;
  padding:10px 12px; outline:none;
}
.btn{
  margin-top:12px;
  padding:10px 18px; border:0; border-radius:22px;
  background: linear-gradient(90deg,#6a11cb 0%,#2575fc 100%);
  color:#fff; font-weight:700; cursor:pointer;
}
.btn:disabled{ opacity:.6; cursor:default; }
.btn.-right{ float:right; }

/* ===== メイン ===== */
.main{ padding: 12px 18px 40px; }
.main__header{
  font-size:22px; font-weight:700;
  border-bottom:1px solid rgba(255,255,255,.25);
  padding: 8px 6px 10px; margin-bottom:10px;
}

.post{
  background:#121c29; border:1px solid rgba(255,255,255,.15);
  border-radius:8px; margin-bottom:12px;
}

/* 上段（ユーザー名＋操作） */
.post__head{
  display:flex; align-items:center; gap:14px;
  padding:12px 10px 8px; border-bottom:1px solid rgba(255,255,255,.12);
}
.post__user{ font-size:18px; }
.icon{
  display:inline-flex; align-items:center; gap:6px;
  background:none; border:0; cursor:pointer; color:#fff;
  padding:4px 6px; border-radius:6px;
}
.icon:hover{ background:rgba(255,255,255,.06); }
.icon img{ width:20px; height:20px; }

/* 本文 */
.post__body{ padding:8px 10px 12px; }

/* コメントヘッダ */
.post__commentsHead{
  border-top:1px solid rgba(255,255,255,.12);
  border-bottom:1px solid rgba(255,255,255,.12);
  padding:8px 10px; text-align:center; opacity:.9;
}

/* コメント一覧 */
.post__comments{ list-style:none; margin:0; padding:8px 10px 10px; display:flex; flex-direction:column; gap:6px; }
.comment{ display:grid; grid-template-columns: 200px 1fr; column-gap: 12px; }
.comment__user{ opacity:.95; }
.comment__body{ background:transparent; border:0; color:#fff; }

/* コメント入力 */
.commentForm{ display:flex; align-items:center; gap:10px; padding: 8px 10px 14px; }
.commentForm__input{
  flex:1 1 auto; padding:10px 12px;
  border:1px solid #8a8f98; border-radius:10px; outline:none;
  background:transparent; color:#fff;
}

/* 空表示 */
.empty{ opacity:.8; padding: 16px 8px; }
</style>
